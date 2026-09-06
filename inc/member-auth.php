<?php
/**
 * Member Login — passwordless (magic link + email OTP), directory-gated.
 *
 * Self-contained: own tables, own hooks, own assets. The only shared-code
 * touch is the require_once line in functions.php.
 *
 * Security model: see MEMBER_LOGIN_DESIGN.md §"Security".
 * - Challenges (magic link / OTP) and sessions live in dedicated tables.
 * - Tokens are 256-bit CSPRNG; only their HMAC-SHA256 hash is stored.
 * - Session cookie is a split selector/verifier pair (verifier never stored raw).
 * - OTP: 6 digits, 5 attempts, 15-minute expiry.
 * - Sends rate-limited 3 / 15 min per IP and per email.
 * - Uniform responses — a non-member email is indistinguishable from a member one.
 * - Every send / login / failure / logout is written to the audit log.
 */

defined('ABSPATH') || exit;

define('SJIOC_MEMBER_AUTH_SCHEMA', 1);          // bump to re-run dbDelta
define('SJIOC_MEMBER_ASSET_VER', '1.0.0');      // bump on member.css / member.js edits

/* ─────────────────────────────────────────────────────────────
   CONFIG HELPERS
───────────────────────────────────────────────────────────── */

/** HMAC key for hashing tokens and the session verifier. */
function sjioc_member_auth_salt(): string {
    if (defined('SJIOC_MEMBER_AUTH_SALT') && SJIOC_MEMBER_AUTH_SALT) {
        return (string) SJIOC_MEMBER_AUTH_SALT;
    }
    return wp_salt('auth');
}

/** HTTPS-aware even behind Azure's TLS-terminating front end (X-Forwarded-Proto). */
function sjioc_member_is_https(): bool {
    if (is_ssl()) return true;
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strtolower(explode(',', $proto)[0]) === 'https';
}

/** True on local/dev — enables on-screen + error_log delivery of links/codes. */
function sjioc_member_is_dev(): bool {
    if (defined('WP_DEBUG') && WP_DEBUG) return true;
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($host, '.local') || str_ends_with($host, '.test');
}

function sjioc_member_tbl(string $which): string {
    global $wpdb;
    return $wpdb->prefix . 'sjioc_member_' . $which; // challenges | sessions | auth_log
}

function sjioc_member_ip(): string {
    return substr(sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function sjioc_member_ua(): string {
    return substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

/* ─────────────────────────────────────────────────────────────
   SCHEMA — provisioned on wp-admin load, no theme reactivation
───────────────────────────────────────────────────────────── */

add_action('admin_init', 'sjioc_member_auth_maybe_install');

function sjioc_member_auth_maybe_install(): void {
    if ((int) get_option('sjioc_member_auth_schema') === SJIOC_MEMBER_AUTH_SCHEMA) {
        sjioc_member_auth_gc();
        return;
    }
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $charset   = $wpdb->get_charset_collate();
    $challenge = sjioc_member_tbl('challenges');
    $sessions  = sjioc_member_tbl('sessions');
    $log       = sjioc_member_tbl('auth_log');

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$challenge} (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        member_id     INT UNSIGNED    NOT NULL,
        email         VARCHAR(100)    NOT NULL,
        kind          VARCHAR(8)      NOT NULL,
        selector      CHAR(24)        NOT NULL,
        verifier_hash CHAR(64)        NOT NULL,
        attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
        expires_at    DATETIME        NOT NULL,
        consumed_at   DATETIME        DEFAULT NULL,
        ip            VARCHAR(45)     DEFAULT NULL,
        created_at    DATETIME        NOT NULL,
        PRIMARY KEY (id),
        KEY selector (selector),
        KEY expires_at (expires_at)
    ) {$charset};");

    dbDelta("CREATE TABLE {$sessions} (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        member_id     INT UNSIGNED    NOT NULL,
        selector      CHAR(24)        NOT NULL,
        verifier_hash CHAR(64)        NOT NULL,
        issued_at     DATETIME        NOT NULL,
        expires_at    DATETIME        NOT NULL,
        last_seen     DATETIME        NOT NULL,
        ip            VARCHAR(45)     DEFAULT NULL,
        user_agent    VARCHAR(255)    DEFAULT NULL,
        revoked_at    DATETIME        DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY selector (selector),
        KEY member_id (member_id),
        KEY expires_at (expires_at)
    ) {$charset};");

    dbDelta("CREATE TABLE {$log} (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        member_id  INT UNSIGNED    DEFAULT NULL,
        email      VARCHAR(100)    DEFAULT NULL,
        event      VARCHAR(40)     NOT NULL,
        detail     VARCHAR(255)    DEFAULT NULL,
        ip         VARCHAR(45)     DEFAULT NULL,
        user_agent VARCHAR(255)    DEFAULT NULL,
        created_at DATETIME        NOT NULL,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY created_at (created_at)
    ) {$charset};");

    update_option('sjioc_member_auth_schema', SJIOC_MEMBER_AUTH_SCHEMA, false);
}

/** Housekeeping — runs at most once/day. */
function sjioc_member_auth_gc(): void {
    if (get_transient('sjioc_member_auth_gc')) return;
    set_transient('sjioc_member_auth_gc', 1, DAY_IN_SECONDS);

    global $wpdb;
    $c = sjioc_member_tbl('challenges');
    $s = sjioc_member_tbl('sessions');
    $l = sjioc_member_tbl('auth_log');
    // All stored timestamps are UTC (see gmdate() writes below) — compare in UTC.
    $wpdb->query("DELETE FROM {$c} WHERE expires_at < (UTC_TIMESTAMP() - INTERVAL 1 DAY)");
    $wpdb->query("DELETE FROM {$s} WHERE expires_at < (UTC_TIMESTAMP() - INTERVAL 7 DAY)
                  OR (revoked_at IS NOT NULL AND revoked_at < (UTC_TIMESTAMP() - INTERVAL 7 DAY))");
    $wpdb->query("DELETE FROM {$l} WHERE created_at < (UTC_TIMESTAMP() - INTERVAL 180 DAY)");
}

/* ─────────────────────────────────────────────────────────────
   AUDIT LOG
───────────────────────────────────────────────────────────── */

function sjioc_member_log(string $event, array $args = []): void {
    global $wpdb;
    $wpdb->insert(sjioc_member_tbl('auth_log'), [
        'member_id'  => isset($args['member_id']) ? (int) $args['member_id'] : null,
        'email'      => isset($args['email']) ? substr((string) $args['email'], 0, 100) : null,
        'event'      => substr($event, 0, 40),
        'detail'     => isset($args['detail']) ? substr((string) $args['detail'], 0, 255) : null,
        'ip'         => sjioc_member_ip(),
        'user_agent' => sjioc_member_ua(),
        'created_at' => gmdate('Y-m-d H:i:s'),
    ]);
}

/* ─────────────────────────────────────────────────────────────
   DIRECTORY LOOKUP + GREETING
───────────────────────────────────────────────────────────── */

/** Resolve an email to the primary active directory member (lowest member_seq). */
function sjioc_member_by_email(string $email): ?object {
    $email = trim($email);
    if ($email === '' || !is_email($email)) return null;
    global $wpdb;
    $t = $wpdb->prefix . 'sjioc_members';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT id, first_name, last_name, gender, marital_status, email
           FROM {$t}
          WHERE LOWER(email) = LOWER(%s) AND is_active = 1
          ORDER BY member_seq ASC
          LIMIT 1",
        $email
    )) ?: null;
}

function sjioc_member_by_id(int $id): ?object {
    global $wpdb;
    $t = $wpdb->prefix . 'sjioc_members';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT id, first_name, last_name, gender, marital_status, email
           FROM {$t} WHERE id = %d AND is_active = 1 LIMIT 1",
        $id
    )) ?: null;
}

function sjioc_member_title(string $gender, string $marital): string {
    $g = strtoupper($gender);
    if ($g === 'M') return 'Mr.';
    if ($g === 'F') return in_array(strtoupper($marital), ['M', 'W'], true) ? 'Mrs.' : 'Ms.';
    return '';
}

/** "Mr. John Smith" — for the dashboard greeting. */
function sjioc_member_greeting(object $m): string {
    $parts = array_filter([
        sjioc_member_title((string) $m->gender, (string) $m->marital_status),
        trim((string) $m->first_name . ' ' . (string) $m->last_name),
    ]);
    return trim(implode(' ', $parts));
}

/* ─────────────────────────────────────────────────────────────
   SESSIONS
───────────────────────────────────────────────────────────── */

function sjioc_member_cookie_name(): string {
    return sjioc_member_is_https() ? '__Host-sjioc_member' : 'sjioc_member';
}

function sjioc_member_set_cookie(string $name, string $value, int $expire): void {
    setcookie($name, $value, [
        'expires'  => $expire,
        'path'     => '/',
        'domain'   => '',
        'secure'   => sjioc_member_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

function sjioc_member_clear_cookie(string $name): void {
    setcookie($name, '', [
        'expires' => time() - 3600, 'path' => '/', 'domain' => '',
        'secure'  => sjioc_member_is_https(), 'httponly' => true, 'samesite' => 'Lax',
    ]);
    unset($_COOKIE[$name]);
}

/** Issue a 24-hour session for a member and set the cookie. */
function sjioc_member_start_session(int $member_id): void {
    global $wpdb;
    $selector = bin2hex(random_bytes(12));
    $verifier = bin2hex(random_bytes(32));
    $now      = time();

    $wpdb->insert(sjioc_member_tbl('sessions'), [
        'member_id'     => $member_id,
        'selector'      => $selector,
        'verifier_hash' => hash_hmac('sha256', $verifier, sjioc_member_auth_salt()),
        'issued_at'     => gmdate('Y-m-d H:i:s', $now),
        'expires_at'    => gmdate('Y-m-d H:i:s', $now + DAY_IN_SECONDS),
        'last_seen'     => gmdate('Y-m-d H:i:s', $now),
        'ip'            => sjioc_member_ip(),
        'user_agent'    => sjioc_member_ua(),
    ]);

    sjioc_member_set_cookie(sjioc_member_cookie_name(), $selector . '.' . $verifier, $now + DAY_IN_SECONDS);
}

/** The current logged-in member, or null. Cached per request. */
function sjioc_current_member(): ?object {
    static $resolved = false;
    static $member   = null;
    if ($resolved) return $member;
    $resolved = true;

    $raw = (string) ($_COOKIE[sjioc_member_cookie_name()] ?? '');
    if ($raw === '' || !str_contains($raw, '.')) return null;
    [$selector, $verifier] = array_pad(explode('.', $raw, 2), 2, '');
    if (strlen($selector) !== 24 || $verifier === '') return null;

    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . sjioc_member_tbl('sessions') . "
          WHERE selector = %s AND revoked_at IS NULL LIMIT 1",
        $selector
    ));
    if (!$row) return null;
    if (strtotime($row->expires_at . ' UTC') < time()) return null;

    $expected = hash_hmac('sha256', $verifier, sjioc_member_auth_salt());
    if (!hash_equals($row->verifier_hash, $expected)) return null;

    $m = sjioc_member_by_id((int) $row->member_id);
    if (!$m) return null;

    // Throttled last_seen refresh (once / 10 min).
    if (strtotime($row->last_seen . ' UTC') < time() - 600) {
        $wpdb->update(sjioc_member_tbl('sessions'),
            ['last_seen' => gmdate('Y-m-d H:i:s')], ['id' => $row->id]);
    }

    $m->session_id = (int) $row->id;
    return $member = $m;
}

function sjioc_member_end_session(): void {
    global $wpdb;
    $raw = (string) ($_COOKIE[sjioc_member_cookie_name()] ?? '');
    if (str_contains($raw, '.')) {
        [$selector] = explode('.', $raw, 2);
        $wpdb->update(sjioc_member_tbl('sessions'),
            ['revoked_at' => gmdate('Y-m-d H:i:s')], ['selector' => $selector]);
    }
    sjioc_member_clear_cookie(sjioc_member_cookie_name());
}

/* ─────────────────────────────────────────────────────────────
   PAGE URLS  (resolved by template, so the admin's slug is free)
───────────────────────────────────────────────────────────── */

function sjioc_member_page_url(string $which): string {
    $tpl      = 'page-member-' . ($which === 'dashboard' ? 'dashboard' : 'login') . '.php';
    $fallback = home_url('/member-' . ($which === 'dashboard' ? 'dashboard' : 'login') . '/');

    $cache = get_transient('sjioc_member_page_urls');
    if (is_array($cache) && !empty($cache[$which])) return $cache[$which];

    $pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => $tpl, 'number' => 1]);
    $url   = $pages ? get_permalink($pages[0]->ID) : $fallback;

    $cache = is_array($cache) ? $cache : [];
    $cache[$which] = $url;
    set_transient('sjioc_member_page_urls', $cache, HOUR_IN_SECONDS);
    return $url;
}

add_action('save_post_page', fn() => delete_transient('sjioc_member_page_urls'));

function sjioc_member_redirect(string $url): void {
    wp_safe_redirect($url);
    exit;
}

/* ─────────────────────────────────────────────────────────────
   RATE LIMITING  (throwaway counters — transients, not tables)
───────────────────────────────────────────────────────────── */

function sjioc_member_rate_hit(string $email): bool {
    $keys = [
        'sjioc_ma_ip_' . md5(sjioc_member_ip()),
        'sjioc_ma_em_' . md5(strtolower($email)),
    ];
    $blocked = false;
    foreach ($keys as $k) {
        $n = (int) get_transient($k);
        if ($n >= 3) $blocked = true;
        set_transient($k, $n + 1, 15 * MINUTE_IN_SECONDS);
    }
    return $blocked;
}

/* ─────────────────────────────────────────────────────────────
   DEV DELIVERY  (local only — link/code to error_log + admin screen)
───────────────────────────────────────────────────────────── */

function sjioc_member_dev_stash(string $email, string $line): void {
    if (!sjioc_member_is_dev()) return;
    error_log('[sjioc-member-auth] ' . $email . ' — ' . $line);
    set_transient('sjioc_member_dev_last', ['email' => $email, 'line' => $line, 't' => time()], 20 * MINUTE_IN_SECONDS);
}

/* ─────────────────────────────────────────────────────────────
   SEND  (magic link or OTP)  —  POST admin-post.php?action=sjioc_member_send
───────────────────────────────────────────────────────────── */

add_action('admin_post_nopriv_sjioc_member_send', 'sjioc_member_send');
add_action('admin_post_sjioc_member_send',        'sjioc_member_send');

function sjioc_member_send(): void {
    $login = sjioc_member_page_url('login');

    if (!wp_verify_nonce($_POST['sjnonce'] ?? '', 'sjioc_member_send')) {
        sjioc_member_redirect(add_query_arg('err', 'expired', $login));
    }

    // Honeypot — pretend success, send nothing.
    if (!empty($_POST['sjioc_hp'])) {
        sjioc_member_redirect(add_query_arg('sent', 'link', $login));
    }

    $method = (($_POST['method'] ?? '') === 'otp') ? 'otp' : 'link';
    $email  = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $remember = !empty($_POST['remember']);

    // Remember-email convenience cookie (not authentication).
    if ($remember && is_email($email)) {
        sjioc_member_set_cookie('sjioc_member_email', $email, time() + 90 * DAY_IN_SECONDS);
    } else {
        sjioc_member_clear_cookie('sjioc_member_email');
    }

    if (!is_email($email)) {
        sjioc_member_redirect(add_query_arg('err', 'email', $login));
    }

    // reCAPTCHA (fails open if unconfigured / unreachable — existing helper behavior).
    if (!sjioc_recaptcha_verify(sanitize_text_field($_POST['recaptcha_token'] ?? ''), 'member_auth')) {
        sjioc_member_redirect(add_query_arg('err', 'captcha', $login));
    }

    $sent_arg  = $method === 'otp' ? 'otp' : 'link';
    $neutral   = add_query_arg('sent', $sent_arg, $login);
    $blocked   = sjioc_member_rate_hit($email);
    $member    = sjioc_member_by_email($email);

    // Uniform outcome: a non-member or a rate-limited request looks identical.
    if ($blocked || !$member) {
        sjioc_member_log($blocked ? 'send_blocked' : 'send_unknown', ['email' => $email, 'detail' => $method]);
        sjioc_member_redirect($neutral);
    }

    global $wpdb;
    $ct = sjioc_member_tbl('challenges');

    // Invalidate this member's prior unconsumed challenges of the same kind.
    $wpdb->query($wpdb->prepare(
        "UPDATE {$ct} SET consumed_at = %s WHERE member_id = %d AND kind = %s AND consumed_at IS NULL",
        gmdate('Y-m-d H:i:s'), $member->id, $method
    ));

    $selector = bin2hex(random_bytes(12));
    $now      = time();
    $expires  = gmdate('Y-m-d H:i:s', $now + 15 * MINUTE_IN_SECONDS);

    if ($method === 'otp') {
        $code   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $secret = $code;
    } else {
        $secret = bin2hex(random_bytes(32));
    }

    $wpdb->insert($ct, [
        'member_id'     => $member->id,
        'email'         => $member->email,
        'kind'          => $method,
        'selector'      => $selector,
        'verifier_hash' => hash_hmac('sha256', $secret, sjioc_member_auth_salt()),
        'expires_at'    => $expires,
        'ip'            => sjioc_member_ip(),
        'created_at'    => gmdate('Y-m-d H:i:s', $now),
    ]);

    if ($method === 'otp') {
        sjioc_member_set_cookie('sjioc_member_otp', $selector, $now + 20 * MINUTE_IN_SECONDS);
        $subject = sprintf('Your %s sign-in code: %s', sjioc_abbr(), $code);
        $body    = sjioc_member_email_body_otp($code);
        sjioc_member_dev_stash($member->email, 'OTP code = ' . $code);
    } else {
        $url     = add_query_arg('ml', $selector . '.' . $secret, $login);
        $subject = sprintf('Your %s sign-in link', sjioc_abbr());
        $body    = sjioc_member_email_body_link($url);
        sjioc_member_dev_stash($member->email, 'Magic link = ' . $url);
    }

    wp_mail($member->email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    sjioc_member_log($method === 'otp' ? 'send_otp' : 'send_link', ['member_id' => $member->id, 'email' => $member->email]);

    sjioc_member_redirect($neutral);
}

/* ─────────────────────────────────────────────────────────────
   VERIFY MAGIC LINK  —  GET  ?ml=selector.validator  on the login page
───────────────────────────────────────────────────────────── */

add_action('template_redirect', 'sjioc_member_gate', 1);

function sjioc_member_gate(): void {
    $is_login = is_page_template('page-member-login.php');
    $is_dash  = is_page_template('page-member-dashboard.php');
    if (!$is_login && !$is_dash) return;

    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow', true);

    // Magic-link verification.
    if ($is_login && !empty($_GET['ml'])) {
        sjioc_member_verify_link((string) wp_unslash($_GET['ml']));
    }

    $member = sjioc_current_member();

    if ($is_dash && !$member) {
        sjioc_member_redirect(add_query_arg('err', 'auth', sjioc_member_page_url('login')));
    }
    if ($is_login && $member && empty($_GET['bye'])) {
        sjioc_member_redirect(sjioc_member_page_url('dashboard'));
    }
}

function sjioc_member_verify_link(string $raw): void {
    $login = sjioc_member_page_url('login');
    [$selector, $validator] = array_pad(explode('.', $raw, 2), 2, '');
    if (strlen($selector) !== 24 || $validator === '') {
        sjioc_member_redirect(add_query_arg('err', 'link', $login));
    }

    global $wpdb;
    $ct  = sjioc_member_tbl('challenges');
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$ct} WHERE selector = %s AND kind = 'link' AND consumed_at IS NULL LIMIT 1",
        $selector
    ));

    $ok = $row
        && strtotime($row->expires_at . ' UTC') >= time()
        && hash_equals($row->verifier_hash, hash_hmac('sha256', $validator, sjioc_member_auth_salt()));

    if (!$ok) {
        sjioc_member_log('login_link_fail', ['detail' => $selector]);
        sjioc_member_redirect(add_query_arg('err', 'link', $login));
    }

    $wpdb->update($ct, ['consumed_at' => gmdate('Y-m-d H:i:s')], ['id' => $row->id]);
    sjioc_member_start_session((int) $row->member_id);
    sjioc_member_log('login_link', ['member_id' => (int) $row->member_id, 'email' => $row->email]);
    sjioc_member_redirect(sjioc_member_page_url('dashboard'));
}

/* ─────────────────────────────────────────────────────────────
   VERIFY OTP  —  POST admin-post.php?action=sjioc_member_otp
───────────────────────────────────────────────────────────── */

add_action('admin_post_nopriv_sjioc_member_otp', 'sjioc_member_verify_otp');
add_action('admin_post_sjioc_member_otp',        'sjioc_member_verify_otp');

function sjioc_member_verify_otp(): void {
    $login = sjioc_member_page_url('login');

    if (!wp_verify_nonce($_POST['sjnonce'] ?? '', 'sjioc_member_otp')) {
        sjioc_member_redirect(add_query_arg('err', 'expired', $login));
    }

    $selector = (string) ($_COOKIE['sjioc_member_otp'] ?? '');
    $code     = preg_replace('/\D/', '', (string) wp_unslash($_POST['code'] ?? ''));

    global $wpdb;
    $ct  = sjioc_member_tbl('challenges');
    $row = strlen($selector) === 24 ? $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$ct} WHERE selector = %s AND kind = 'otp' AND consumed_at IS NULL LIMIT 1",
        $selector
    )) : null;

    if (!$row || strtotime($row->expires_at . ' UTC') < time()) {
        sjioc_member_redirect(add_query_arg(['sent' => 'otp', 'err' => 'otp_expired'], $login));
    }

    if ((int) $row->attempts >= 5) {
        $wpdb->update($ct, ['consumed_at' => gmdate('Y-m-d H:i:s')], ['id' => $row->id]);
        sjioc_member_log('login_otp_locked', ['member_id' => (int) $row->member_id, 'email' => $row->email]);
        sjioc_member_redirect(add_query_arg('err', 'otp_locked', $login));
    }

    if (strlen($code) !== 6
        || !hash_equals($row->verifier_hash, hash_hmac('sha256', $code, sjioc_member_auth_salt()))) {
        $wpdb->update($ct, ['attempts' => (int) $row->attempts + 1], ['id' => $row->id]);
        sjioc_member_log('login_otp_fail', ['member_id' => (int) $row->member_id, 'email' => $row->email]);
        sjioc_member_redirect(add_query_arg(['sent' => 'otp', 'err' => 'otp_bad'], $login));
    }

    $wpdb->update($ct, ['consumed_at' => gmdate('Y-m-d H:i:s')], ['id' => $row->id]);
    sjioc_member_clear_cookie('sjioc_member_otp');
    sjioc_member_start_session((int) $row->member_id);
    sjioc_member_log('login_otp', ['member_id' => (int) $row->member_id, 'email' => $row->email]);
    sjioc_member_redirect(sjioc_member_page_url('dashboard'));
}

/* ─────────────────────────────────────────────────────────────
   LOGOUT
───────────────────────────────────────────────────────────── */

add_action('admin_post_nopriv_sjioc_member_logout', 'sjioc_member_logout');
add_action('admin_post_sjioc_member_logout',        'sjioc_member_logout');

function sjioc_member_logout(): void {
    if (wp_verify_nonce($_POST['sjnonce'] ?? '', 'sjioc_member_logout')) {
        $m = sjioc_current_member();
        sjioc_member_log('logout', $m ? ['member_id' => $m->id, 'email' => $m->email] : []);
        sjioc_member_end_session();
    }
    sjioc_member_redirect(add_query_arg('bye', '1', sjioc_member_page_url('login')));
}

/* ─────────────────────────────────────────────────────────────
   EMAIL BODIES
───────────────────────────────────────────────────────────── */

function sjioc_member_email_shell(string $inner): string {
    $name = esc_html(sjioc_name());
    return "<div style=\"font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#1A1208\">
        <p style=\"font-size:18px;color:#7B1818;margin:0 0 18px\"><strong>{$name}</strong></p>
        {$inner}
        <hr style=\"border:none;border-top:1px solid #eee;margin:26px 0\">
        <p style=\"font-size:12px;color:#888\">If you didn't request this, you can safely ignore this email — no one can sign in without it.</p>
    </div>";
}

function sjioc_member_email_body_link(string $url): string {
    $u = esc_url($url);
    return sjioc_member_email_shell(
        "<p style=\"font-size:15px;line-height:1.6\">Tap the button below to sign in to the member area. This link works once and expires in 15 minutes.</p>
         <p style=\"margin:24px 0\"><a href=\"{$u}\" style=\"background:#7B1818;color:#fff;text-decoration:none;padding:13px 28px;border-radius:4px;font-weight:bold;display:inline-block\">Sign in</a></p>
         <p style=\"font-size:13px;color:#666;word-break:break-all\">Or paste this address into your browser:<br>{$u}</p>"
    );
}

function sjioc_member_email_body_otp(string $code): string {
    $c   = esc_html($code);
    $abbr = esc_html(sjioc_abbr());
    // First line kept plain and code-first so iOS Mail / Android autofill detect it.
    return sjioc_member_email_shell(
        "<p style=\"font-size:15px;line-height:1.6\">{$c} is your {$abbr} sign-in code. It expires in 15 minutes.</p>
         <p style=\"font-size:34px;letter-spacing:8px;font-weight:bold;color:#7B1818;margin:20px 0\">{$c}</p>
         <p style=\"font-size:14px;color:#666\">Enter it on the sign-in page to continue to the member area.</p>"
    );
}

/* ─────────────────────────────────────────────────────────────
   ASSETS + robots
───────────────────────────────────────────────────────────── */

add_action('wp_enqueue_scripts', function () {
    if (!is_page_template(['page-member-login.php', 'page-member-dashboard.php'])) return;

    wp_enqueue_style('sjioc-member', SJIOC_URI . '/assets/css/member.css', ['sjioc-style'], SJIOC_MEMBER_ASSET_VER);

    if (is_page_template('page-member-login.php')) {
        wp_enqueue_script('sjioc-member', SJIOC_URI . '/assets/js/member.js', [], SJIOC_MEMBER_ASSET_VER, true);
        $rc = sjioc_recaptcha_site_key();
        if ($rc) {
            wp_enqueue_script('google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($rc), [], null, true);
        }
        wp_localize_script('sjioc-member', 'sjiocMember', ['recaptchaKey' => $rc]);
    }
}, 20);

add_filter('wp_robots', function ($robots) {
    if (is_page_template(['page-member-login.php', 'page-member-dashboard.php'])) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['max-image-preview']);
    }
    return $robots;
});
