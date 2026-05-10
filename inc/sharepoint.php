<?php
defined('ABSPATH') || exit;

/*
 * OneDrive Photo Sync — Microsoft Graph API (app-only, no SDK needed)
 *
 * Images never leave OneDrive. WordPress stores only metadata + temporary
 * download URLs. No media_sideload_image(). No file copying. Ever.
 *
 * Add to wp-config.php:
 *   define('SJIOC_AZURE_TENANT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
 *   define('SJIOC_AZURE_CLIENT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
 *   define('SJIOC_AZURE_CLIENT_SECRET', 'your-client-secret');
 *   define('SJIOC_ONEDRIVE_DRIVE_ID',      'b!AbCdEf123...');   // drive ID from Graph Explorer
 *   define('SJIOC_ONEDRIVE_FOLDER_ID',     '01ABCDEF...');       // item ID of root photos folder
 *
 * In Azure AD → App registrations → API permissions:
 *   Microsoft Graph → Files.Read.All (application) → Grant admin consent
 *
 * Expected OneDrive folder structure (SJIOC_ONEDRIVE_FOLDER_ID is the root):
 *   SJIOC Photos/
 *   ├── Worship/
 *   │   └── Holy Qurbana 2025/
 *   ├── Events/
 *   ├── Ministries/
 *   └── Community/
 */

/* ── Table ───────────────────────────────────────────── */
function sjioc_od_create_table(): void {
    global $wpdb;
    $table   = $wpdb->prefix . 'sjioc_photos';
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$table} (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        od_item_id   VARCHAR(200) NOT NULL,
        od_drive_id  VARCHAR(200) NOT NULL,
        file_name    VARCHAR(255) NOT NULL,
        category     VARCHAR(60)  NOT NULL DEFAULT '',
        album        VARCHAR(120) NOT NULL DEFAULT '',
        title        VARCHAR(255) NOT NULL DEFAULT '',
        download_url TEXT,
        url_expires  DATETIME,
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY   uq_od_item (od_item_id),
        KEY          idx_cat    (category),
        KEY          idx_exp    (url_expires)
    ) {$charset};");
}
add_action('after_switch_theme', 'sjioc_od_create_table');

/* ── Cron ────────────────────────────────────────────── */
add_action('after_switch_theme', function () {
    if (!wp_next_scheduled('sjioc_od_sync_cron')) {
        $tz   = new DateTimeZone(wp_timezone_string() ?: 'UTC');
        $next = new DateTime('next sunday 00:01', $tz);
        $next->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($next->getTimestamp(), 'sjioc_weekly', 'sjioc_od_sync_cron');
    }
});

add_action('switch_theme', function () {
    $ts = wp_next_scheduled('sjioc_od_sync_cron');
    if ($ts) wp_unschedule_event($ts, 'sjioc_od_sync_cron');
});

add_action('sjioc_od_sync_cron', 'sjioc_od_sync');

/* ── Admin menu (self-registered) ────────────────────── */
add_action('admin_menu', function () {
    add_submenu_page(
        'sjioc',
        'OneDrive Photos',
        'Photos',
        'manage_options',
        'sjioc-photos',
        'sjioc_od_photos_page'
    );
});

/* ── AJAX: manual sync ───────────────────────────────── */
add_action('wp_ajax_sjioc_od_sync', function () {
    check_ajax_referer('sjioc_od_sync_nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    wp_send_json(sjioc_od_sync());
});

/* ── AJAX: reset delta link + full sync ──────────────── */
add_action('wp_ajax_sjioc_od_reset', function () {
    check_ajax_referer('sjioc_od_sync_nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    delete_option('sjioc_od_delta_link');
    wp_send_json(sjioc_od_sync());
});

/* ─────────────────────────────────────
   GRAPH API HELPERS
───────────────────────────────────── */

function sjioc_od_get_token(): string|false {
    $cached = get_transient('sjioc_od_token');
    if ($cached) return $cached;

    $resp = wp_remote_post(
        'https://login.microsoftonline.com/' . SJIOC_AZURE_TENANT_ID . '/oauth2/v2.0/token',
        [
            'timeout' => 20,
            'body'    => [
                'grant_type'    => 'client_credentials',
                'client_id'     => SJIOC_AZURE_CLIENT_ID,
                'client_secret' => SJIOC_AZURE_CLIENT_SECRET,
                'scope'         => 'https://graph.microsoft.com/.default',
            ],
        ]
    );

    if (is_wp_error($resp)) return false;
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['access_token'])) return false;

    $ttl = max(60, (int)($data['expires_in'] ?? 3600) - 60);
    set_transient('sjioc_od_token', $data['access_token'], $ttl);
    return $data['access_token'];
}

/* ─────────────────────────────────────
   URL REFRESH
   The delta endpoint does not return @microsoft.graph.downloadUrl
   with app-only (client_credentials) auth — only direct item GETs do.
   Runs after every delta sync to populate URLs for newly inserted rows
   and refresh any existing rows expiring within 2 hours.
───────────────────────────────────── */

function sjioc_od_refresh_urls(string $token): int {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_photos';

    $rows = $wpdb->get_results(
        "SELECT id, od_drive_id, od_item_id FROM {$table}
         WHERE download_url IS NULL OR download_url = ''
            OR url_expires IS NULL OR url_expires <= DATE_ADD(NOW(), INTERVAL 2 HOUR)"
    );

    $refreshed = 0;
    foreach ($rows as $row) {
        $resp = wp_remote_get(
            'https://graph.microsoft.com/v1.0/drives/' . $row->od_drive_id
            . '/items/' . $row->od_item_id,
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'timeout' => 15,
            ]
        );
        if (is_wp_error($resp)) continue;
        if (wp_remote_retrieve_response_code($resp) !== 200) continue;

        $data = json_decode(wp_remote_retrieve_body($resp), true);
        $url  = $data['@microsoft.graph.downloadUrl'] ?? '';
        if (!$url) continue;

        $wpdb->update(
            $table,
            [
                'download_url' => $url,
                'url_expires'  => gmdate('Y-m-d H:i:s', time() + 55 * MINUTE_IN_SECONDS),
            ],
            ['id' => (int) $row->id]
        );
        $refreshed++;
    }

    return $refreshed;
}

/* ─────────────────────────────────────
   DELTA SYNC
   First run: full enumeration of SJIOC_ONEDRIVE_FOLDER_ID tree.
   Subsequent runs: only items changed since the last saved deltaLink.
   410 (expired delta link) → restarts fresh automatically.
───────────────────────────────────── */

function sjioc_od_delta_sync(string $token, bool $fresh = false): array {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_photos';

    $saved = $fresh ? '' : (string) get_option('sjioc_od_delta_link', '');
    $url   = $saved ?: 'https://graph.microsoft.com/v1.0/drives/' . SJIOC_ONEDRIVE_DRIVE_ID
           . '/items/' . SJIOC_ONEDRIVE_FOLDER_ID . '/delta';

    // Collect all pages before processing — folder items from page 1 may be
    // needed to resolve parent paths for file items on later pages.
    $all_items = [];
    while ($url) {
        $resp = wp_remote_get($url, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 45,
        ]);

        if (is_wp_error($resp)) {
            return ['error' => 'Graph API request failed: ' . $resp->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($resp);

        if ($code === 410 && !$fresh) {
            delete_option('sjioc_od_delta_link');
            return sjioc_od_delta_sync($token, true);
        }
        if ($code < 200 || $code >= 300) {
            return ['error' => "Graph API returned HTTP {$code}."];
        }

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($body)) {
            return ['error' => 'Invalid response from Graph API.'];
        }

        $all_items = array_merge($all_items, $body['value'] ?? []);

        if (!empty($body['@odata.deltaLink'])) {
            update_option('sjioc_od_delta_link', $body['@odata.deltaLink'], false);
            break;
        }
        $url = $body['@odata.nextLink'] ?? null;
    }

    // Pass 1: build folder map  id → ['name', 'parent_id']
    $folders = [];
    foreach ($all_items as $item) {
        if (!empty($item['folder'])) {
            $folders[$item['id']] = [
                'name'      => $item['name'],
                'parent_id' => $item['parentReference']['id'] ?? '',
            ];
        }
    }

    $inserted = $updated = $deleted = 0;

    // Pass 2: process file items
    foreach ($all_items as $item) {
        if (!empty($item['deleted'])) {
            if ($wpdb->delete($table, ['od_item_id' => $item['id']])) $deleted++;
            continue;
        }
        if (empty($item['file'])) continue;

        $mime = $item['file']['mimeType'] ?? '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) continue;

        // Resolve category + album via two-level parent chain from SJIOC_ONEDRIVE_FOLDER_ID
        $parent_id = $item['parentReference']['id'] ?? '';
        $parent    = $folders[$parent_id] ?? null;
        if (!$parent) continue;

        if ($parent['parent_id'] === SJIOC_ONEDRIVE_FOLDER_ID) {
            $category = strtolower($parent['name']);
            $album    = '';
        } else {
            $grandparent = $folders[$parent['parent_id']] ?? null;
            if (!$grandparent || $grandparent['parent_id'] !== SJIOC_ONEDRIVE_FOLDER_ID) continue;
            $category = strtolower($grandparent['name']);
            $album    = $parent['name'];
        }

        $dl_url  = $item['@microsoft.graph.downloadUrl'] ?? '';
        $title   = pathinfo($item['name'], PATHINFO_FILENAME);
        $expires = gmdate('Y-m-d H:i:s', time() + 55 * MINUTE_IN_SECONDS);

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE od_item_id = %s LIMIT 1",
            $item['id']
        ));

        if ($exists) {
            $wpdb->update(
                $table,
                [
                    'file_name'    => $item['name'],
                    'category'     => $category,
                    'album'        => $album,
                    'title'        => $title,
                    'download_url' => $dl_url,
                    'url_expires'  => $expires,
                ],
                ['od_item_id' => $item['id']]
            );
            $updated++;
        } else {
            $wpdb->insert($table, [
                'od_item_id'   => $item['id'],
                'od_drive_id'  => SJIOC_ONEDRIVE_DRIVE_ID,
                'file_name'    => $item['name'],
                'category'     => $category,
                'album'        => $album,
                'title'        => $title,
                'download_url' => $dl_url,
                'url_expires'  => $expires,
            ]);
            $inserted++;
        }
    }

    return compact('inserted', 'updated', 'deleted');
}

/* ─────────────────────────────────────
   ORCHESTRATOR
───────────────────────────────────── */

function sjioc_od_sync(): array {
    if (!defined('SJIOC_AZURE_TENANT_ID')     || !defined('SJIOC_AZURE_CLIENT_ID') ||
        !defined('SJIOC_AZURE_CLIENT_SECRET') || !defined('SJIOC_ONEDRIVE_DRIVE_ID')  ||
        !defined('SJIOC_ONEDRIVE_FOLDER_ID')) {
        return ['error' => 'OneDrive credentials not configured in wp-config.php.'];
    }

    $token = sjioc_od_get_token();
    if (!$token) {
        return ['error' => 'Authentication failed — check Tenant ID, Client ID, and Client Secret.'];
    }

    sjioc_od_create_table();

    $delta         = sjioc_od_delta_sync($token);

    if (isset($delta['error'])) return $delta;

    $url_refreshed = sjioc_od_refresh_urls($token);

    $result = array_merge($delta, ['url_refreshed' => $url_refreshed]);
    update_option('sjioc_od_last_sync',   current_time('mysql'), false);
    update_option('sjioc_od_sync_result', $result,               false);
    return $result;
}

/* ─────────────────────────────────────
   ADMIN PAGE
───────────────────────────────────── */

function sjioc_od_photos_page(): void {
    if (!current_user_can('manage_options')) return;

    $configured = defined('SJIOC_AZURE_TENANT_ID')     && defined('SJIOC_AZURE_CLIENT_ID') &&
                  defined('SJIOC_AZURE_CLIENT_SECRET')  && defined('SJIOC_ONEDRIVE_DRIVE_ID') &&
                  defined('SJIOC_ONEDRIVE_FOLDER_ID');

    global $wpdb;
    $table       = $wpdb->prefix . 'sjioc_photos';
    $photo_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $last_sync   = get_option('sjioc_od_last_sync', null);
    $last_result = get_option('sjioc_od_sync_result', []);
    $next_cron   = wp_next_scheduled('sjioc_od_sync_cron');
    ?>
    <div class="wrap">
        <h1>📸 OneDrive Photos</h1>

        <?php if (!$configured): ?>
        <div class="notice notice-error">
            <p><strong>OneDrive not configured.</strong>
               Add the five constants to <code>wp-config.php</code> — see the setup section below.</p>
        </div>
        <?php else: ?>
        <div class="notice notice-success is-dismissible"><p>✅ OneDrive credentials found.</p></div>
        <?php endif; ?>

        <div style="max-width:640px;margin-top:20px">
            <table class="widefat striped" style="margin-bottom:20px">
                <tr><th>Photos in library</th>
                    <td><?php echo $photo_count; ?></td></tr>
                <tr><th>Last sync</th>
                    <td><?php echo $last_sync ? esc_html($last_sync) : '<em>Never</em>'; ?></td></tr>
                <?php if ($last_result): ?>
                <tr><th>Last sync result</th><td>
                    <?php echo (int)($last_result['inserted']      ?? 0); ?> new &nbsp;·&nbsp;
                    <?php echo (int)($last_result['updated']       ?? 0); ?> updated &nbsp;·&nbsp;
                    <?php echo (int)($last_result['deleted']       ?? 0); ?> removed &nbsp;·&nbsp;
                    <?php echo (int)($last_result['url_refreshed'] ?? 0); ?> URLs refreshed
                </td></tr>
                <?php endif; ?>
                <tr><th>Next auto-sync</th><td>
                    <?php echo $next_cron
                        ? esc_html(date_i18n('D, M j Y g:i A', $next_cron))
                        : '<em>Not scheduled — re-activate theme to reschedule</em>';
                    ?>
                </td></tr>
            </table>

            <div id="od-result" style="display:none;margin-bottom:16px"></div>

            <?php if ($configured): ?>
            <button class="button button-primary button-large" id="btn-od-sync" onclick="odSync(false)">
                🔄 Sync Now from OneDrive
            </button>
            &nbsp;
            <button class="button button-large" id="btn-od-reset" onclick="odSync(true)">
                ↺ Reset &amp; Full Sync
            </button>
            <p class="description" style="margin-top:8px">
                <strong>Sync Now</strong> — refreshes URLs and fetches changes since last run via delta query.<br>
                <strong>Reset &amp; Full Sync</strong> — clears the saved delta link and re-scans all files from scratch. Use this if photos are missing or URLs are broken.
            </p>
            <?php endif; ?>
        </div>

        <hr style="margin:32px 0">
        <h3>Required OneDrive Folder Structure</h3>
        <pre style="background:#f6f6f6;padding:18px;max-width:460px;line-height:1.9;font-size:.82rem;border-left:3px solid #C9A84C">SJIOC Photos/              ← SJIOC_ONEDRIVE_FOLDER_ID points here
├── Worship/
│   ├── Holy Qurbana 2025/
│   └── Christmas Service 2025/
├── Events/
│   ├── Perunnal 2025/
│   └── Parish Picnic 2025/
├── Ministries/
│   ├── Sunday School/
│   └── MGOCSM/
└── Community/
    └── Fellowship 2025/</pre>
        <p class="description">
            <strong>Top-level folders</strong> → category (worship · events · ministries · community)<br>
            <strong>Sub-folders</strong> → album name shown in the gallery<br>
            Supported: <code>.jpg</code> <code>.jpeg</code> <code>.png</code> <code>.webp</code>
        </p>

        <hr style="margin:32px 0">
        <h3>wp-config.php Setup</h3>
        <p>Add these five constants above <code>/* That's all, stop editing! */</code>:</p>
        <pre style="background:#f6f6f6;padding:18px;font-size:.82rem;border-left:3px solid #C9A84C">define('SJIOC_AZURE_TENANT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('SJIOC_AZURE_CLIENT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('SJIOC_AZURE_CLIENT_SECRET', 'your-client-secret');
define('SJIOC_ONEDRIVE_DRIVE_ID',      'b!AbCdEf123...');   // drive ID from Graph Explorer
define('SJIOC_ONEDRIVE_FOLDER_ID',     '01ABCDEF...');       // item ID of "SJIOC Photos" folder</pre>
        <p class="description">
            In Azure AD → App registrations → your app → API permissions, add:<br>
            <strong>Microsoft Graph → Files.Read.All</strong> (application permission) → Grant admin consent
        </p>
    </div>

    <script>
    function odSync(reset) {
        var btn = document.getElementById(reset ? 'btn-od-reset' : 'btn-od-sync');
        var res = document.getElementById('od-result');
        document.getElementById('btn-od-sync').disabled = true;
        document.getElementById('btn-od-reset').disabled = true;
        btn.textContent = '⏳ Syncing — please wait…';
        res.style.display = 'none';

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action:      reset ? 'sjioc_od_reset' : 'sjioc_od_sync',
                _ajax_nonce: '<?php echo wp_create_nonce('sjioc_od_sync_nonce'); ?>'
            })
        })
        .then(r => r.json())
        .then(d => {
            document.getElementById('btn-od-sync').disabled = false;
            document.getElementById('btn-od-reset').disabled = false;
            btn.textContent = reset ? '↺ Reset & Full Sync' : '🔄 Sync Now from OneDrive';
            res.style.display = '';
            if (d.error) {
                res.innerHTML = '<div class="notice notice-error inline"><p><strong>Error:</strong> '
                              + d.error + '</p></div>';
            } else {
                res.innerHTML = '<div class="notice notice-success inline"><p>'
                              + '<strong>Sync complete.</strong> '
                              + d.inserted + ' new · ' + d.updated + ' updated · '
                              + d.deleted  + ' removed · ' + d.url_refreshed + ' URLs refreshed.'
                              + '</p></div>';
            }
        })
        .catch(() => {
            document.getElementById('btn-od-sync').disabled = false;
            document.getElementById('btn-od-reset').disabled = false;
            btn.textContent = reset ? '↺ Reset & Full Sync' : '🔄 Sync Now from OneDrive';
            res.style.display = '';
            res.innerHTML = '<div class="notice notice-error inline"><p>Network error — try again.</p></div>';
        });
    }
    </script>
    <?php
}