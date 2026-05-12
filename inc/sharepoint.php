<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CREDENTIAL HELPERS
   wp-config.php constants always take precedence (preferred for production/Azure).
   Falls back to wp_options values set via the admin settings form.
───────────────────────────────────── */
function sjioc_od_tenant_id(): string {
    return defined('SJIOC_AZURE_TENANT_ID')   ? SJIOC_AZURE_TENANT_ID   : (string) get_option('sjioc_od_tenant_id',        '');
}
function sjioc_od_client_id(): string {
    return defined('SJIOC_AZURE_CLIENT_ID')   ? SJIOC_AZURE_CLIENT_ID   : (string) get_option('sjioc_od_client_id',        '');
}
function sjioc_od_client_secret(): string {
    return defined('SJIOC_AZURE_CLIENT_SECRET') ? SJIOC_AZURE_CLIENT_SECRET : (string) get_option('sjioc_od_client_secret', '');
}
function sjioc_od_drive_id(): string {
    return defined('SJIOC_ONEDRIVE_DRIVE_ID') ? SJIOC_ONEDRIVE_DRIVE_ID : (string) get_option('sjioc_od_drive_id',         '');
}
function sjioc_od_photos_folder_id(): string {
    return defined('SJIOC_ONEDRIVE_FOLDER_ID') ? SJIOC_ONEDRIVE_FOLDER_ID : (string) get_option('sjioc_od_photos_folder_id', '');
}
function sjioc_od_is_configured(): bool {
    return (bool) (sjioc_od_tenant_id() && sjioc_od_client_id() && sjioc_od_client_secret()
                && sjioc_od_drive_id()  && sjioc_od_photos_folder_id());
}

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
    // Weekly full delta sync (new photos, removals)
    if (!wp_next_scheduled('sjioc_od_sync_cron')) {
        $tz   = new DateTimeZone(wp_timezone_string() ?: 'UTC');
        $next = new DateTime('next sunday 00:01', $tz);
        $next->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($next->getTimestamp(), 'sjioc_weekly', 'sjioc_od_sync_cron');
    }
    // Hourly URL refresh — keeps download_urls alive (they expire ~1 h from Graph API)
    if (!wp_next_scheduled('sjioc_od_refresh_cron')) {
        wp_schedule_event(time() + 300, 'hourly', 'sjioc_od_refresh_cron');
    }
});

add_action('switch_theme', function () {
    foreach (['sjioc_od_sync_cron', 'sjioc_od_refresh_cron'] as $hook) {
        $ts = wp_next_scheduled($hook);
        if ($ts) wp_unschedule_event($ts, $hook);
    }
});

add_action('sjioc_od_sync_cron', 'sjioc_od_sync');

add_action('sjioc_od_refresh_cron', function () {
    $token = sjioc_od_get_token();
    if ($token) sjioc_od_refresh_urls($token);
});

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
        'https://login.microsoftonline.com/' . sjioc_od_tenant_id() . '/oauth2/v2.0/token',
        [
            'timeout' => 20,
            'body'    => [
                'grant_type'    => 'client_credentials',
                'client_id'     => sjioc_od_client_id(),
                'client_secret' => sjioc_od_client_secret(),
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

    // Refresh URLs that are missing or expiring within 20 minutes
    $rows = $wpdb->get_results(
        "SELECT id, od_drive_id, od_item_id FROM {$table}
         WHERE download_url IS NULL OR download_url = ''
            OR url_expires IS NULL OR url_expires <= DATE_ADD(NOW(), INTERVAL 20 MINUTE)"
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

        // Parse the actual expiry from the URL's se= (Signed Expiry) parameter.
        // Microsoft controls the real lifetime — we just track it accurately.
        // Fall back to 23 h if se= is absent (conservative but won't over-refresh).
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $qs);
        $se      = $qs['se'] ?? '';
        $expires = $se
            ? gmdate('Y-m-d H:i:s', strtotime($se) - 30)   // 30-sec safety margin only
            : gmdate('Y-m-d H:i:s', time() + 23 * HOUR_IN_SECONDS);

        $wpdb->update(
            $table,
            ['download_url' => $url, 'url_expires' => $expires],
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
    $url   = $saved ?: 'https://graph.microsoft.com/v1.0/drives/' . sjioc_od_drive_id()
           . '/items/' . sjioc_od_photos_folder_id() . '/delta';

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

        $photos_root = sjioc_od_photos_folder_id();
        if ($parent['parent_id'] === $photos_root) {
            $category = strtolower($parent['name']);
            $album    = '';
        } else {
            $grandparent = $folders[$parent['parent_id']] ?? null;
            if (!$grandparent || $grandparent['parent_id'] !== $photos_root) continue;
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
                'od_drive_id'  => sjioc_od_drive_id(),
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
    if (!sjioc_od_is_configured()) {
        return ['error' => 'SharePoint credentials not configured. Add them via SJIOC → Photos → Settings, or in wp-config.php.'];
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

    /* ── Handle settings save ── */
    if (isset($_POST['sjioc_od_save_settings'])) {
        check_admin_referer('sjioc_od_save_settings');

        $db_fields = [
            'sjioc_od_tenant_id'        => 'SJIOC_AZURE_TENANT_ID',
            'sjioc_od_client_id'        => 'SJIOC_AZURE_CLIENT_ID',
            'sjioc_od_client_secret'    => 'SJIOC_AZURE_CLIENT_SECRET',
            'sjioc_od_drive_id'         => 'SJIOC_ONEDRIVE_DRIVE_ID',
            'sjioc_od_photos_folder_id' => 'SJIOC_ONEDRIVE_FOLDER_ID',
        ];
        $saved = 0;
        foreach ($db_fields as $option => $constant) {
            if (defined($constant)) continue; // wp-config.php wins — don't overwrite
            $val = sanitize_text_field($_POST[$option] ?? '');
            if ($val !== '') {
                update_option($option, $val, false);
                $saved++;
            }
        }
        delete_transient('sjioc_od_token');
        echo '<div class="notice notice-success is-dismissible"><p>&#10003; Settings saved ('
           . $saved . ' field' . ($saved !== 1 ? 's' : '') . ' updated). Token cache cleared.</p></div>';
    }

    $configured = sjioc_od_is_configured();

    global $wpdb;
    $table       = $wpdb->prefix . 'sjioc_photos';
    $photo_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $last_sync   = get_option('sjioc_od_last_sync', null);
    $last_result = get_option('sjioc_od_sync_result', []);
    $next_cron   = wp_next_scheduled('sjioc_od_sync_cron');

    /* ── Helper: render one settings row ── */
    $row = function (string $label, string $option, string $constant, bool $is_secret = false) {
        $from_const = defined($constant);
        $db_val     = (string) get_option($option, '');
        $display    = $from_const
            ? ($is_secret ? str_repeat('•', 12) . ' (wp-config.php)' : constant($constant) . ' (wp-config.php)')
            : ($is_secret && $db_val ? str_repeat('•', 12) . ' (Database)' : ($db_val ?: ''));
        $locked     = $from_const;
        $source_tag = $from_const
            ? '<span style="background:#d1fae5;color:#065f46;font-size:10px;padding:2px 7px;border-radius:10px;font-weight:700;margin-left:6px">wp-config.php</span>'
            : '<span style="background:#fef3c7;color:#92400e;font-size:10px;padding:2px 7px;border-radius:10px;font-weight:700;margin-left:6px">Database</span>';
        ?>
        <tr>
            <th style="width:200px;vertical-align:middle">
                <?php echo esc_html($label); ?>
                <?php echo ($db_val || $from_const) ? $source_tag : '<span style="background:#fee2e2;color:#991b1b;font-size:10px;padding:2px 7px;border-radius:10px;font-weight:700;margin-left:6px">Not set</span>'; ?>
            </th>
            <td>
                <?php if ($locked): ?>
                    <input type="text" class="regular-text" value="<?php echo esc_attr($display); ?>" disabled style="background:#f0f0f0;color:#666;font-family:monospace;font-size:12px">
                    <p class="description">Set in <code>wp-config.php</code> — edit that file to change it.</p>
                <?php else: ?>
                    <input type="<?php echo $is_secret ? 'password' : 'text'; ?>"
                           name="<?php echo esc_attr($option); ?>"
                           class="regular-text"
                           value="<?php echo esc_attr($db_val); ?>"
                           placeholder="<?php echo $db_val ? '' : 'Enter value…'; ?>"
                           style="font-family:monospace;font-size:12px"
                           autocomplete="<?php echo $is_secret ? 'new-password' : 'off'; ?>">
                    <?php if ($is_secret && $db_val): ?>
                    <p class="description">Secret is saved. Enter a new value only if you want to replace it.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    };
    ?>
    <div class="wrap">
        <h1>&#128248; SharePoint / OneDrive Photos</h1>

        <?php if (!$configured): ?>
        <div class="notice notice-warning">
            <p><strong>Not fully configured.</strong> Fill in the SharePoint credentials below to enable photo sync.</p>
        </div>
        <?php else: ?>
        <div class="notice notice-success is-dismissible"><p>&#10003; All credentials configured — photo sync is active.</p></div>
        <?php endif; ?>

        <!-- ── Sync Status ── -->
        <div style="max-width:680px;margin-top:20px">
            <h2 style="font-size:1rem;border-bottom:1px solid #ddd;padding-bottom:8px">Sync Status</h2>
            <table class="widefat striped" style="margin-bottom:20px">
                <tr><th>Photos in library</th><td><?php echo esc_html($photo_count); ?></td></tr>
                <tr><th>Last sync</th><td><?php echo $last_sync ? esc_html($last_sync) : '<em>Never</em>'; ?></td></tr>
                <?php if ($last_result): ?>
                <tr><th>Last result</th><td>
                    <?php echo (int)($last_result['inserted'] ?? 0); ?> new &nbsp;&middot;&nbsp;
                    <?php echo (int)($last_result['updated']  ?? 0); ?> updated &nbsp;&middot;&nbsp;
                    <?php echo (int)($last_result['deleted']  ?? 0); ?> removed &nbsp;&middot;&nbsp;
                    <?php echo (int)($last_result['url_refreshed'] ?? 0); ?> URLs refreshed
                </td></tr>
                <?php endif; ?>
                <tr><th>Next auto-sync</th><td>
                    <?php echo $next_cron
                        ? esc_html(date_i18n('D, M j Y g:i A', $next_cron))
                        : '<em>Not scheduled — re-activate theme</em>';
                    ?>
                </td></tr>
            </table>

            <div id="od-result" style="display:none;margin-bottom:16px"></div>

            <?php if ($configured): ?>
            <button class="button button-primary button-large" id="btn-od-sync" onclick="odSync(false)">
                &#128260; Sync Now from SharePoint
            </button>
            &nbsp;
            <button class="button button-large" id="btn-od-reset" onclick="odSync(true)">
                &#8634; Reset &amp; Full Sync
            </button>
            <p class="description" style="margin-top:8px">
                <strong>Sync Now</strong> — fetches only changes since last run (fast).<br>
                <strong>Reset &amp; Full Sync</strong> — clears delta link and rescans everything from scratch.
            </p>
            <?php endif; ?>
        </div>

        <hr style="margin:32px 0">

        <!-- ── Credentials Settings ── -->
        <div style="max-width:780px">
            <h2 style="font-size:1rem;border-bottom:1px solid #ddd;padding-bottom:8px">SharePoint / Azure Credentials</h2>
            <p>Values set in <code>wp-config.php</code> are shown read-only and always take precedence. Use the fields below to configure any values not already in <code>wp-config.php</code>.</p>

            <form method="post" style="margin-top:16px">
                <?php wp_nonce_field('sjioc_od_save_settings'); ?>
                <table class="widefat" style="margin-bottom:16px">
                    <thead><tr>
                        <th style="width:200px">Setting</th>
                        <th>Value</th>
                    </tr></thead>
                    <tbody>
                        <?php
                        $row('Azure Tenant ID',        'sjioc_od_tenant_id',        'SJIOC_AZURE_TENANT_ID');
                        $row('Azure Client ID',        'sjioc_od_client_id',        'SJIOC_AZURE_CLIENT_ID');
                        $row('Azure Client Secret',    'sjioc_od_client_secret',    'SJIOC_AZURE_CLIENT_SECRET', true);
                        $row('SharePoint Drive ID',    'sjioc_od_drive_id',         'SJIOC_ONEDRIVE_DRIVE_ID');
                        $row('Photos Folder Item ID',  'sjioc_od_photos_folder_id', 'SJIOC_ONEDRIVE_FOLDER_ID');
                        ?>
                    </tbody>
                </table>
                <input type="submit" name="sjioc_od_save_settings" class="button button-primary" value="Save Settings">
                <p class="description" style="margin-top:8px">
                    Saving clears the cached OAuth token so the new credentials take effect immediately.<br>
                    For production on Azure, prefer setting these as App Service environment variables and defining them as constants in <code>wp-config.php</code>.
                </p>
            </form>
        </div>

        <hr style="margin:32px 0">
        <h2 style="font-size:1rem;border-bottom:1px solid #ddd;padding-bottom:8px">Required SharePoint Folder Structure</h2>
        <pre style="background:#f6f6f6;padding:18px;max-width:460px;line-height:1.9;font-size:.82rem;border-left:3px solid #C9A84C">SJIOC Photos/              ← Photos Folder Item ID points here
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
        <h2 style="font-size:1rem;border-bottom:1px solid #ddd;padding-bottom:8px">Azure AD App Permissions Required</h2>
        <table class="widefat" style="max-width:560px">
            <thead><tr><th>Permission</th><th>Type</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td><code>Files.Read.All</code></td><td>Application</td><td>Read photos for gallery sync</td></tr>
                <tr><td><code>Files.ReadWrite.All</code></td><td>Application</td><td>Upload hall rental summaries</td></tr>
            </tbody>
        </table>
        <p class="description" style="margin-top:8px">Grant admin consent after adding permissions in Azure AD → App registrations → API permissions.</p>
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
            btn.textContent = reset ? '↺ Reset & Full Sync' : '🔄 Sync Now from SharePoint';
            res.style.display = '';
            if (d.error) {
                res.innerHTML = '<div class="notice notice-error inline"><p><strong>Error:</strong> ' + d.error + '</p></div>';
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
            btn.textContent = reset ? '↺ Reset & Full Sync' : '🔄 Sync Now from SharePoint';
            res.style.display = '';
            res.innerHTML = '<div class="notice notice-error inline"><p>Network error — try again.</p></div>';
        });
    }
    </script>
    <?php
}