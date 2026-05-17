<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   EVENTS — DB-backed, GCal sync + manual
───────────────────────────────────── */

// ── Constants (wp-config preferred; fallback to DB options) ────────────────
if (!defined('SJIOC_GCAL_KEY')) define('SJIOC_GCAL_KEY', get_option('sjioc_gcal_key', ''));
if (!defined('SJIOC_GCAL_ID'))  define('SJIOC_GCAL_ID',  get_option('sjioc_gcal_id',  ''));
if (!defined('SJIOC_GCAL_ICS')) define('SJIOC_GCAL_ICS', get_option('sjioc_gcal_ics', ''));

// ── DB table ───────────────────────────────────────────────────────────────
function sjioc_events_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'sjioc_events';
}

add_action('after_switch_theme', 'sjioc_create_events_table');
add_action('admin_init', function () {
    if (get_option('sjioc_events_db_ver') !== '1') {
        sjioc_create_events_table();
        update_option('sjioc_events_db_ver', '1');
    }
    // CSV template download — must run before any HTML output
    if (($_GET['page'] ?? '') === 'sjioc-events'
        && ($_GET['action'] ?? '') === 'csv_template'
        && current_user_can('manage_options')
        && isset($_GET['_wpnonce'])
        && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'sjioc_csv_template')) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="events-import-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Title', 'Start Date', 'Start Time', 'End Date', 'End Time', 'All Day', 'Location', 'Description', 'URL']);
        fputcsv($out, ['Parish Picnic',       '2026-06-07', '10:00', '2026-06-07', '14:00', 'No',  'Church Grounds',  'Annual outdoor gathering.', '']);
        fputcsv($out, ['Sunday School Opening','2026-09-07', '',      '',           '',      'Yes', 'Fellowship Hall', 'New academic year kickoff.', '']);
        fclose($out);
        exit;
    }
});

function sjioc_create_events_table(): void {
    global $wpdb;
    $t   = sjioc_events_table();
    $col = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$t} (
        id          int(11)      NOT NULL AUTO_INCREMENT,
        title       varchar(255) NOT NULL DEFAULT '',
        description longtext,
        location    varchar(255) DEFAULT '',
        start_date  date         NOT NULL,
        start_time  time         DEFAULT NULL,
        end_date    date         DEFAULT NULL,
        end_time    time         DEFAULT NULL,
        all_day     tinyint(1)   DEFAULT 1,
        url         varchar(500) DEFAULT '',
        source      varchar(20)  DEFAULT 'manual',
        gcal_id     varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY          idx_start (start_date),
        UNIQUE KEY   uq_gcal (gcal_id)
    ) {$col};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// ── Enqueue ────────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-events.php')) return;
    wp_enqueue_style('sjioc-events',  SJIOC_URI . '/assets/css/events.css', [], SJIOC_VER);
    wp_enqueue_script('sjioc-events', SJIOC_URI . '/assets/js/events.js',   [], SJIOC_VER, true);
    wp_localize_script('sjioc-events', 'SJIOC_EVENTS', [
        'restUrl' => rest_url('sjioc/v1/events'),

        'calId'   => SJIOC_GCAL_ID,
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);
});

// ── REST endpoints ─────────────────────────────────────────────────────────
add_action('rest_api_init', function () {
    register_rest_route('sjioc/v1', '/events', [
        'methods'             => 'GET',
        'callback'            => 'sjioc_events_rest',
        'permission_callback' => '__return_true',
        'args'                => [
            'months' => ['default' => 6, 'sanitize_callback' => fn($v) => max(1, min(12, (int)$v))],
        ],
    ]);
    register_rest_route('sjioc/v1', '/calendar\.ics', [
        'methods'             => 'GET',
        'callback'            => 'sjioc_calendar_ics_endpoint',
        'permission_callback' => '__return_true',
    ]);
});

function sjioc_events_rest(WP_REST_Request $req): WP_REST_Response {
    return rest_ensure_response(sjioc_get_db_events((int)$req->get_param('months')));
}

function sjioc_get_db_events(int $months = 6): array {
    global $wpdb;
    $t        = sjioc_events_table();
    $today    = current_time('Y-m-d');
    $max_date = date('Y-m-d', strtotime("+{$months} months", current_time('timestamp')));
    $mshort   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$t} WHERE start_date >= %s AND start_date <= %s ORDER BY start_date, start_time",
        $today, $max_date
    ));

    if (!$rows) return [];

    return array_map(function ($r) use ($mshort) {
        $ts    = strtotime($r->start_date);
        $all   = (bool)(int)$r->all_day;
        $start = $all ? $r->start_date : ($r->start_date . 'T' . ($r->start_time ?: '00:00:00'));
        $end   = '';
        if ($r->end_date) {
            $end = $all
                ? date('Y-m-d', strtotime($r->end_date . ' +1 day'))  // exclusive end, GCal convention
                : ($r->end_date . 'T' . ($r->end_time ?: '00:00:00'));
        }
        return [
            'id'          => (string) $r->id,
            'title'       => $r->title,
            'description' => $r->description ?: '',
            'location'    => $r->location    ?: '',
            'start'       => $start,
            'end'         => $end,
            'all_day'     => $all,
            'mon'         => $mshort[(int)date('n', $ts) - 1],
            'day'         => (int)date('j', $ts),
            'url'         => $r->url ?: '',
        ];
    }, $rows);
}

// ── Front-page teaser ──────────────────────────────────────────────────────
function sjioc_front_page_events(): array {
    $items = sjioc_get_db_events(1);
    if ($items) {
        return array_slice(array_map(fn($e) => [
            'mon'     => $e['mon'],
            'day'     => $e['day'],
            'title'   => $e['title'],
            'excerpt' => wp_trim_words($e['description'], 14, '…'),
        ], $items), 0, 3);
    }
    return [
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Holy Qurbana',     'excerpt' => 'Every Sunday. Feast day celebrations posted on our calendar.'],
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Sunday School',    'excerpt' => 'Classes for all ages following Holy Qurbana. New students welcome.'],
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Parish Fellowship', 'excerpt' => 'Monthly fellowship gathering after service. All welcome.'],
    ];
}

// ── AJAX: GCal sync ────────────────────────────────────────────────────────
add_action('wp_ajax_sjioc_gcal_sync', 'sjioc_gcal_sync_ajax');
function sjioc_gcal_sync_ajax(): void {
    check_ajax_referer('sjioc_events_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $events = sjioc_fetch_gcal_events(6);
    if (empty($events)) {
        wp_send_json_error(SJIOC_GCAL_KEY ? 'No events returned — check Calendar ID and that it is public.' : 'API key not configured.');
    }

    global $wpdb;
    $t      = sjioc_events_table();
    $synced = 0;

    foreach ($events as $e) {
        if (!$e['id'] || !$e['start']) continue;
        $ts      = strtotime($e['start']);
        $start_d = date('Y-m-d', $ts);
        $start_t = $e['all_day'] ? null : date('H:i:s', $ts);
        $end_d   = null;
        $end_t   = null;
        if ($e['end']) {
            $te    = strtotime($e['end']);
            $end_d = $e['all_day'] ? date('Y-m-d', $te - 86400) : date('Y-m-d', $te); // convert exclusive → inclusive
            $end_t = $e['all_day'] ? null : date('H:i:s', $te);
        }

        // INSERT … ON DUPLICATE KEY UPDATE — preserves existing DB id
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$t} (gcal_id, title, description, location, start_date, start_time, end_date, end_time, all_day, url, source)
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %d, %s, 'gcal')
             ON DUPLICATE KEY UPDATE
               title=%s, description=%s, location=%s, start_date=%s, start_time=%s, end_date=%s, end_time=%s, all_day=%d, url=%s",
            $e['id'], $e['title'], $e['description'], $e['location'], $start_d, $start_t, $end_d, $end_t, (int)$e['all_day'], $e['url'],
            $e['title'], $e['description'], $e['location'], $start_d, $start_t, $end_d, $end_t, (int)$e['all_day'], $e['url']
        ));
        $synced++;
    }

    update_option('sjioc_gcal_last_sync', current_time('mysql'));
    wp_send_json_success(['count' => $synced]);
}

// ── AJAX: ICS / Outlook sync ───────────────────────────────────────────────
add_action('wp_ajax_sjioc_ics_sync', 'sjioc_ics_sync_ajax');
function sjioc_ics_sync_ajax(): void {
    check_ajax_referer('sjioc_events_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $url = get_option('sjioc_gcal_ics', '');
    if (!$url) wp_send_json_error('No ICS URL configured. Paste an Outlook ICS URL and save settings first.');

    $events = sjioc_parse_ics_feed($url);
    if (empty($events)) {
        wp_send_json_error('No events returned — check the ICS URL is correct and the calendar is public.');
    }

    global $wpdb;
    $t      = sjioc_events_table();
    $synced = 0;

    foreach ($events as $e) {
        if (!$e['start_date']) continue;

        // For all-day events ICS DTEND is exclusive (day after last day) — convert to inclusive
        $end_date = $e['end_date'];
        if ($e['all_day'] && $end_date) {
            $end_date = date('Y-m-d', strtotime($end_date . ' -1 day'));
            if ($end_date === $e['start_date']) $end_date = null;
        }

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$t} (gcal_id, title, description, location, start_date, start_time, end_date, end_time, all_day, url, source)
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %d, %s, 'outlook')
             ON DUPLICATE KEY UPDATE
               title=%s, description=%s, location=%s, start_date=%s, start_time=%s, end_date=%s, end_time=%s, all_day=%d, url=%s",
            'ics:' . $e['uid'], $e['title'], $e['description'], $e['location'],
            $e['start_date'], $e['start_time'], $end_date, $e['end_time'], (int)$e['all_day'], $e['url'],
            $e['title'], $e['description'], $e['location'],
            $e['start_date'], $e['start_time'], $end_date, $e['end_time'], (int)$e['all_day'], $e['url']
        ));
        $synced++;
    }

    update_option('sjioc_ics_last_sync', current_time('mysql'));
    wp_send_json_success(['count' => $synced]);
}

// ── Admin page ─────────────────────────────────────────────────────────────
function sjioc_events_settings_page(): void {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $t = sjioc_events_table();
    sjioc_create_events_table(); // ensure table exists after updates

    $notice  = '';
    $editing = null;

    // ── Import CSV ───────────────────────────────────────────────────────────
    if (isset($_POST['sjioc_import_csv'])) {
        check_admin_referer('sjioc_events_admin');
        if (!empty($_FILES['ev_csv']['tmp_name']) && $_FILES['ev_csv']['error'] === UPLOAD_ERR_OK) {
            $result = sjioc_parse_import_csv($_FILES['ev_csv']['tmp_name']);
            $msg    = $result['imported'] . ' event(s) imported.';
            if ($result['errors']) $msg .= ' Skipped: ' . implode(' | ', array_slice($result['errors'], 0, 5));
            $notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        } else {
            $notice = '<div class="notice notice-error"><p>No file selected or upload failed.</p></div>';
        }
    }

    // ── Save GCal credentials ────────────────────────────────────────────
    if (isset($_POST['sjioc_save_gcal'])) {
        check_admin_referer('sjioc_events_admin');
        update_option('sjioc_gcal_key', sanitize_text_field($_POST['sjioc_gcal_key'] ?? ''));
        update_option('sjioc_gcal_id',  sanitize_text_field($_POST['sjioc_gcal_id']  ?? ''));
        update_option('sjioc_gcal_ics', esc_url_raw($_POST['sjioc_gcal_ics'] ?? ''));
        $notice = '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    // ── Add / update manual event ────────────────────────────────────────
    if (isset($_POST['sjioc_save_event'])) {
        check_admin_referer('sjioc_events_admin');
        $ev_id   = (int)($_POST['ev_id'] ?? 0);
        $all_day = !empty($_POST['ev_all_day']) ? 1 : 0;
        $start_d = sanitize_text_field($_POST['ev_start_d'] ?? '');
        $start_t = $all_day ? null : (sanitize_text_field($_POST['ev_start_t'] ?? '') ?: null);
        $end_d   = sanitize_text_field($_POST['ev_end_d'] ?? '') ?: null;
        $end_t   = $all_day ? null : (sanitize_text_field($_POST['ev_end_t'] ?? '') ?: null);
        // End time without end date → same day as start
        if (!$all_day && $end_t && !$end_d) $end_d = $start_d ?: null;
        $data    = [
            'title'       => sanitize_text_field($_POST['ev_title']   ?? ''),
            'description' => sanitize_textarea_field($_POST['ev_desc'] ?? ''),
            'location'    => sanitize_text_field($_POST['ev_location'] ?? ''),
            'start_date'  => $start_d,
            'start_time'  => $start_t,
            'end_date'    => $end_d,
            'end_time'    => $end_t,
            'all_day'     => $all_day,
            'url'         => esc_url_raw($_POST['ev_url'] ?? ''),
            'source'      => 'manual',
        ];
        $fmt = ['%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'];

        if (!$data['title'] || !$data['start_date'] || (!$all_day && !$data['start_time'])) {
            $notice = '<div class="notice notice-error"><p>Title, start date, and start time are required.</p></div>';
        } elseif ($ev_id) {
            $wpdb->update($t, $data, ['id' => $ev_id, 'source' => 'manual'], $fmt, ['%d','%s']);
            $notice = '<div class="notice notice-success is-dismissible"><p>Event updated.</p></div>';
        } else {
            $wpdb->insert($t, $data, $fmt);
            $notice = '<div class="notice notice-success is-dismissible"><p>Event added.</p></div>';
        }
    }

    // ── Delete manual event ──────────────────────────────────────────────
    if (isset($_GET['del_ev'], $_GET['_wpnonce'])) {
        $del_id = (int)$_GET['del_ev'];
        if (wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'sjioc_del_ev_' . $del_id)) {
            $wpdb->delete($t, ['id' => $del_id, 'source' => 'manual'], ['%d','%s']);
            $notice = '<div class="notice notice-success is-dismissible"><p>Event deleted.</p></div>';
        }
    }

    // ── Load event for editing ───────────────────────────────────────────
    if (isset($_GET['edit_ev'])) {
        $editing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$t} WHERE id=%d AND source='manual'", (int)$_GET['edit_ev']
        ));
    }

    // ── Data for display ─────────────────────────────────────────────────
    $gcal_key  = esc_attr(get_option('sjioc_gcal_key', ''));
    $gcal_id   = esc_attr(get_option('sjioc_gcal_id',  ''));
    $gcal_ics  = esc_attr(get_option('sjioc_gcal_ics', ''));
    $last_sync     = get_option('sjioc_gcal_last_sync', '');
    $ics_last_sync = get_option('sjioc_ics_last_sync',  '');
    $base_url      = admin_url('admin.php?page=sjioc-events');
    $nonce_val = wp_create_nonce('sjioc_events_admin');

    $today      = current_time('Y-m-d');
    $all_events = $wpdb->get_results(
        "SELECT * FROM {$t} WHERE start_date >= '{$today}' ORDER BY start_date, start_time"
    );
    ?>
    <div class="wrap">
    <h1>Events</h1>
    <?php echo $notice; ?>

    <!-- ── Calendar Sync ── -->
    <h2 class="title">Calendar Sync</h2>
    <form method="post">
    <?php wp_nonce_field('sjioc_events_admin'); ?>

    <h3 style="margin:16px 0 6px">Outlook / ICS Calendar</h3>
    <p style="color:#555;margin:0 0 12px;font-size:13px">Paste your Outlook published ICS URL. Used to sync events to the website and as the subscribe link on the Events page.</p>
    <table class="form-table" style="max-width:640px"><tbody>
      <tr>
        <th><label for="gcal_ics">ICS Feed URL</label></th>
        <td><input type="url" id="gcal_ics" name="sjioc_gcal_ics" value="<?php echo $gcal_ics; ?>" class="regular-text" placeholder="https://outlook.live.com/owa/calendar/..."></td>
      </tr>
    </tbody></table>
    <p style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:0 0 0 200px">
      <button type="button" id="ics-sync-btn" class="button button-primary"
              <?php echo $gcal_ics ? '' : 'disabled title="Save an ICS URL first"'; ?>>
        &#8635; Sync from Outlook
      </button>
      <span id="ics-sync-status" style="color:#666;font-size:13px">
        <?php if ($ics_last_sync) echo 'Last synced: ' . esc_html(date('M j, Y g:i a', strtotime($ics_last_sync))); ?>
      </span>
    </p>

    <hr style="margin:24px 0">

    <h3 style="margin:0 0 6px">Google Calendar <span style="font-weight:400;color:#888;font-size:13px">(optional)</span></h3>
    <p style="color:#555;margin:0 0 12px;font-size:13px">Only needed if syncing from a public Google Calendar via API key.</p>
    <table class="form-table" style="max-width:640px"><tbody>
      <tr>
        <th><label for="gcal_key">API Key</label></th>
        <td><input type="password" id="gcal_key" name="sjioc_gcal_key" value="<?php echo $gcal_key; ?>" class="regular-text" autocomplete="off"></td>
      </tr>
      <tr>
        <th><label for="gcal_id">Calendar ID</label></th>
        <td><input type="text" id="gcal_id" name="sjioc_gcal_id" value="<?php echo $gcal_id; ?>" class="regular-text" placeholder="abc123@group.calendar.google.com"></td>
      </tr>
    </tbody></table>
    <p style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:0 0 0 200px">
      <button type="button" id="gcal-sync-btn" class="button button-primary"
              <?php echo SJIOC_GCAL_KEY ? '' : 'disabled title="Save an API key first"'; ?>>
        &#8635; Sync from Google Calendar
      </button>
      <span id="gcal-sync-status" style="color:#666;font-size:13px">
        <?php if ($last_sync) echo 'Last synced: ' . esc_html(date('M j, Y g:i a', strtotime($last_sync))); ?>
      </span>
    </p>

    <p class="submit">
      <?php submit_button('Save Settings', 'secondary', 'sjioc_save_gcal', false); ?>
    </p>
    </form>

    <hr style="margin:28px 0">

    <!-- ── Import from Spreadsheet ── -->
    <h2 class="title">Import from Spreadsheet</h2>
    <p style="color:#555;margin-bottom:12px">
      Upload a CSV file. Each row is one event.
      <a href="<?php echo esc_url(wp_nonce_url($base_url . '&action=csv_template', 'sjioc_csv_template')); ?>">
        Download template
      </a> to see the required column format.
    </p>
    <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('sjioc_events_admin'); ?>
    <p>
      <input type="file" name="ev_csv" accept=".csv,text/csv">
      &nbsp;
      <?php submit_button('Import Events', 'secondary', 'sjioc_import_csv', false); ?>
    </p>
    <p class="description">Dates must be in <strong>YYYY-MM-DD</strong> format. Times in <strong>HH:MM</strong> (24-hour). Leave Start Time blank for all-day events.</p>
    </form>

    <hr style="margin:28px 0">

    <!-- ── Add / Edit Event ── -->
    <h2 class="title" id="ev-form-heading"><?php echo $editing ? 'Edit Event' : 'Add Event'; ?></h2>
    <form method="post" id="ev-form">
    <?php wp_nonce_field('sjioc_events_admin'); ?>
    <input type="hidden" name="ev_id" value="<?php echo $editing ? (int)$editing->id : 0; ?>">
    <table class="form-table" style="max-width:700px"><tbody>
      <tr>
        <th><label for="ev_title">Title <span style="color:red">*</span></label></th>
        <td><input type="text" id="ev_title" name="ev_title" value="<?php echo esc_attr($editing->title ?? ''); ?>" class="regular-text" required></td>
      </tr>
      <tr>
        <th>All Day</th>
        <td><label><input type="checkbox" id="ev_all_day" name="ev_all_day" value="1"
              <?php checked(!$editing || $editing->all_day); ?>> All-day event</label></td>
      </tr>
      <tr>
        <th><label for="ev_start_d">Start Date <span style="color:red">*</span></label></th>
        <td>
          <input type="date" id="ev_start_d" name="ev_start_d" value="<?php echo esc_attr($editing->start_date ?? ''); ?>" required>
          <input type="time" id="ev_start_t" name="ev_start_t" value="<?php echo esc_attr($editing->start_time ?? ''); ?>"
                 style="<?php echo (!$editing || $editing->all_day) ? 'display:none' : ''; ?>"
                 <?php echo (!$editing || !$editing->all_day) ? 'required' : ''; ?>>
        </td>
      </tr>
      <tr>
        <th><label for="ev_end_d">End Date</label></th>
        <td>
          <input type="date" id="ev_end_d" name="ev_end_d" value="<?php echo esc_attr($editing->end_date ?? ''); ?>">
          <input type="time" id="ev_end_t" name="ev_end_t" value="<?php echo esc_attr($editing->end_time ?? ''); ?>"
                 style="<?php echo (!$editing || $editing->all_day) ? 'display:none' : ''; ?>">
        </td>
      </tr>
      <tr>
        <th><label for="ev_location">Location</label></th>
        <td><input type="text" id="ev_location" name="ev_location" value="<?php echo esc_attr($editing->location ?? ''); ?>" class="regular-text" placeholder="e.g. Church Hall"></td>
      </tr>
      <tr>
        <th><label for="ev_desc">Description</label></th>
        <td><textarea id="ev_desc" name="ev_desc" class="large-text" rows="4"><?php echo esc_textarea($editing->description ?? ''); ?></textarea></td>
      </tr>
      <tr>
        <th><label for="ev_url">Link / URL</label></th>
        <td><input type="url" id="ev_url" name="ev_url" value="<?php echo esc_attr($editing->url ?? ''); ?>" class="regular-text" placeholder="https://"></td>
      </tr>
    </tbody></table>
    <p class="submit">
      <?php submit_button($editing ? 'Update Event' : 'Add Event', 'primary', 'sjioc_save_event', false); ?>
      <?php if ($editing) : ?>
      &nbsp;<a href="<?php echo esc_url($base_url); ?>" class="button">Cancel</a>
      <?php endif; ?>
    </p>
    </form>

    <hr style="margin:28px 0">

    <!-- ── Events List ── -->
    <h2 class="title">
      Upcoming Events
      <span style="font-size:13px;font-weight:400;color:#666;margin-left:8px">(<?php echo count($all_events); ?>)</span>
    </h2>
    <?php if ($all_events) : ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:900px">
    <thead><tr>
      <th style="width:110px">Date</th>
      <th>Title</th>
      <th style="width:170px">Location</th>
      <th style="width:64px">Source</th>
      <th style="width:120px">Actions</th>
    </tr></thead><tbody>
    <?php foreach ($all_events as $ev) :
        $del_url     = wp_nonce_url($base_url . '&del_ev=' . $ev->id, 'sjioc_del_ev_' . $ev->id);
        $edit_url    = $base_url . '&edit_ev=' . $ev->id . '#ev-form-heading';
        $is_external = $ev->source !== 'manual';
        $src_label   = match($ev->source) { 'gcal' => 'GCal', 'outlook' => 'Outlook', default => 'Manual' };
        $src_color   = match($ev->source) { 'gcal' => '#2271b1', 'outlook' => '#0078d4', default => '#888' };
        $mgd_label   = match($ev->source) { 'outlook' => 'Managed in Outlook', default => 'Managed in GCal' };
    ?>
    <tr>
      <td><?php echo esc_html(date('M j, Y', strtotime($ev->start_date))); ?></td>
      <td><?php echo esc_html($ev->title); ?></td>
      <td><?php echo esc_html($ev->location ?: '—'); ?></td>
      <td><span style="font-size:11px;color:<?php echo $src_color; ?>">
        <?php echo $src_label; ?>
      </span></td>
      <td>
        <?php if (!$is_external) : ?>
          <a href="<?php echo esc_url($edit_url); ?>">Edit</a>
          &nbsp;|&nbsp;
          <a href="<?php echo esc_url($del_url); ?>"
             onclick="return confirm('Delete \'<?php echo esc_js($ev->title); ?>\'?')"
             style="color:#b32d2e">Delete</a>
        <?php else : ?>
          <span style="color:#aaa;font-size:11px"><?php echo $mgd_label; ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php else : ?>
    <p style="color:#666">No upcoming events. Add one above or sync from Outlook / Google Calendar.</p>
    <?php endif; ?>
    </div>

    <script>
    (function () {
      // All-day checkbox toggles time fields
      var allDay  = document.getElementById('ev_all_day');
      var startT  = document.getElementById('ev_start_t');
      var endT    = document.getElementById('ev_end_t');
      function toggleTime() {
        var hide = allDay.checked;
        startT.style.display = hide ? 'none' : '';
        endT.style.display   = hide ? 'none' : '';
        startT.required      = !hide;
      }
      allDay.addEventListener('change', toggleTime);

      // Outlook / ICS sync button
      var icsSyncBtn = document.getElementById('ics-sync-btn');
      var icsSyncSt  = document.getElementById('ics-sync-status');
      if (icsSyncBtn && !icsSyncBtn.disabled) {
        icsSyncBtn.addEventListener('click', function () {
          icsSyncBtn.disabled   = true;
          icsSyncSt.style.color = '#666';
          icsSyncSt.textContent = 'Syncing…';
          fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=sjioc_ics_sync&nonce=<?php echo esc_js($nonce_val); ?>'
          })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d.success) {
              icsSyncSt.textContent = 'Synced ' + d.data.count + ' event(s). Reloading…';
              setTimeout(function () { location.reload(); }, 900);
            } else {
              icsSyncSt.style.color = 'red';
              icsSyncSt.textContent = d.data || 'Sync failed.';
              icsSyncBtn.disabled   = false;
            }
          })
          .catch(function () {
            icsSyncSt.style.color = 'red';
            icsSyncSt.textContent = 'Request failed — check your connection.';
            icsSyncBtn.disabled   = false;
          });
        });
      }

      // GCal sync button
      var syncBtn = document.getElementById('gcal-sync-btn');
      var syncSt  = document.getElementById('gcal-sync-status');
      if (syncBtn && !syncBtn.disabled) {
        syncBtn.addEventListener('click', function () {
          syncBtn.disabled    = true;
          syncSt.style.color  = '#666';
          syncSt.textContent  = 'Syncing…';
          fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=sjioc_gcal_sync&nonce=<?php echo esc_js($nonce_val); ?>'
          })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d.success) {
              syncSt.textContent = 'Synced ' + d.data.count + ' event(s). Reloading…';
              setTimeout(function () { location.reload(); }, 900);
            } else {
              syncSt.style.color = 'red';
              syncSt.textContent = d.data || 'Sync failed.';
              syncBtn.disabled   = false;
            }
          })
          .catch(function () {
            syncSt.style.color = 'red';
            syncSt.textContent = 'Request failed — check your connection.';
            syncBtn.disabled   = false;
          });
        });
      }
    })();
    </script>
    <?php
}

// ── ICS calendar download ──────────────────────────────────────────────────
function sjioc_calendar_ics_endpoint(): void {
    $ics = sjioc_generate_ics();
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="sjioc-events.ics"');
    header('Cache-Control: public, max-age=1800');
    header('X-Robots-Tag: noindex');
    echo $ics;
    exit;
}

function sjioc_generate_ics(): string {
    $events = sjioc_get_db_events(12);
    $host   = parse_url(home_url(), PHP_URL_HOST) ?: 'sjioc';
    $now    = gmdate('Ymd\THis\Z');
    $lines  = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//SJIOC//Parish Events//EN',
        sjioc_ics_fold('X-WR-CALNAME:' . sjioc_ics_escape(sjioc_name() . ' — Events')),
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
    ];
    foreach ($events as $e) {
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:sjioc-ev-' . $e['id'] . '@' . $host;
        $lines[] = 'DTSTAMP:' . $now;
        if ($e['all_day']) {
            $start   = str_replace('-', '', substr($e['start'], 0, 10));
            $end     = $e['end'] ? str_replace('-', '', substr($e['end'], 0, 10)) : $start;
            $lines[] = 'DTSTART;VALUE=DATE:' . $start;
            $lines[] = 'DTEND;VALUE=DATE:'   . $end;
        } else {
            $ts_s    = strtotime($e['start']);
            $ts_e    = $e['end'] ? strtotime($e['end']) : $ts_s + 3600;
            $lines[] = 'DTSTART:' . date('Ymd\THis', $ts_s);  // floating local time
            $lines[] = 'DTEND:'   . date('Ymd\THis', $ts_e);
        }
        $lines[] = sjioc_ics_fold('SUMMARY:'     . sjioc_ics_escape($e['title']));
        if ($e['description']) $lines[] = sjioc_ics_fold('DESCRIPTION:' . sjioc_ics_escape($e['description']));
        if ($e['location'])    $lines[] = sjioc_ics_fold('LOCATION:'    . sjioc_ics_escape($e['location']));
        if ($e['url'])         $lines[] = 'URL:' . $e['url'];
        $lines[] = 'END:VEVENT';
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines) . "\r\n";
}

function sjioc_ics_escape(string $s): string {
    $s = strip_tags($s);
    return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $s);
}

function sjioc_ics_fold(string $line): string {
    if (strlen($line) <= 75) return $line;
    $out = '';
    $len = 0;
    foreach (str_split($line) as $ch) {
        if ($len >= 74) { $out .= "\r\n "; $len = 1; }
        $out .= $ch;
        $len++;
    }
    return $out;
}

// ── CSV import ─────────────────────────────────────────────────────────────
function sjioc_parse_import_csv(string $file): array {
    $handle = fopen($file, 'r');
    if (!$handle) return ['imported' => 0, 'errors' => ['Could not open file.']];

    fgetcsv($handle); // skip header row

    global $wpdb;
    $t        = sjioc_events_table();
    $imported = 0;
    $errors   = [];
    $row_num  = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        if (count($row) < 2 || trim($row[0]) === '') continue;

        [$title, $start_d, $start_t, $end_d, $end_t, $all_day_str, $location, $desc, $url]
            = array_pad($row, 9, '');

        $title   = sanitize_text_field(trim($title));
        $start_d = trim($start_d);

        if (!$title || !$start_d) {
            $errors[] = "Row {$row_num}: Title and Start Date required.";
            continue;
        }

        $ts = strtotime($start_d);
        if (!$ts) {
            $errors[] = "Row {$row_num}: Invalid date '{$start_d}' — use YYYY-MM-DD.";
            continue;
        }
        $start_d = date('Y-m-d', $ts);

        $all_day    = in_array(strtolower(trim($all_day_str)), ['yes','y','1','true'], true)
                   || trim($start_t) === '';
        $start_time = null;
        $end_date   = null;
        $end_time   = null;

        if (!$all_day && trim($start_t)) {
            $ts_t = strtotime('2000-01-01 ' . trim($start_t));
            if ($ts_t) $start_time = date('H:i:s', $ts_t);
        }
        if (trim($end_d)) {
            $ts_e = strtotime(trim($end_d));
            if ($ts_e) $end_date = date('Y-m-d', $ts_e);
        }
        if (!$all_day && trim($end_t) && $end_date) {
            $ts_et = strtotime('2000-01-01 ' . trim($end_t));
            if ($ts_et) $end_time = date('H:i:s', $ts_et);
        }

        $wpdb->insert($t, [
            'title'       => $title,
            'description' => sanitize_textarea_field(trim($desc)),
            'location'    => sanitize_text_field(trim($location)),
            'start_date'  => $start_d,
            'start_time'  => $start_time,
            'end_date'    => $end_date,
            'end_time'    => $end_time,
            'all_day'     => $all_day ? 1 : 0,
            'url'         => esc_url_raw(trim($url)),
            'source'      => 'manual',
        ], ['%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        $imported++;
    }

    fclose($handle);
    return ['imported' => $imported, 'errors' => $errors];
}

// ── ICS feed parser (Outlook / any iCal) ──────────────────────────────────
function sjioc_ics_unescape(string $s): string {
    return str_replace(['\\n', '\\N', '\\,', '\\;', '\\\\'], ["\n", "\n", ',', ';', '\\'], $s);
}

function sjioc_parse_ics_dt(string $value, string $params): array {
    if (str_contains($params, 'VALUE=DATE')) {
        return [
            'all_day' => true,
            'date'    => substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2),
            'time'    => null,
        ];
    }
    $is_utc = str_ends_with($value, 'Z');
    $tz     = new DateTimeZone($is_utc ? 'UTC' : wp_timezone_string());
    $dt     = DateTimeImmutable::createFromFormat('Ymd\THis', rtrim($value, 'Zz'), $tz);
    if (!$dt) return ['all_day' => false, 'date' => null, 'time' => null];
    if ($is_utc) $dt = $dt->setTimezone(wp_timezone());
    return ['all_day' => false, 'date' => $dt->format('Y-m-d'), 'time' => $dt->format('H:i:s')];
}

function sjioc_parse_ics_feed(string $url): array {
    $res = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($res)) return [];
    $body = wp_remote_retrieve_body($res);
    if (!$body) return [];

    // Unfold continuation lines per RFC 5545 §3.1
    $body  = preg_replace('/\r?\n[ \t]/', '', $body);
    $lines = preg_split('/\r?\n/', $body);

    $events  = [];
    $current = null;

    foreach ($lines as $raw) {
        $line = rtrim($raw);
        if ($line === 'BEGIN:VEVENT') { $current = []; continue; }
        if ($line === 'END:VEVENT')   { if ($current !== null) $events[] = $current; $current = null; continue; }
        if ($current === null) continue;

        $colon     = strpos($line, ':');
        if ($colon === false) continue;
        $prop_full = substr($line, 0, $colon);
        $value     = substr($line, $colon + 1);
        $semi      = strpos($prop_full, ';');
        $prop_name = $semi !== false ? substr($prop_full, 0, $semi) : $prop_full;
        $params    = $semi !== false ? substr($prop_full, $semi + 1) : '';

        $current[$prop_name] = ['value' => $value, 'params' => $params];
    }

    $result = [];
    foreach ($events as $ev) {
        $uid = $ev['UID']['value'] ?? '';
        $sum = sjioc_ics_unescape($ev['SUMMARY']['value'] ?? '');
        if (!$uid || !$sum) continue;
        if (!isset($ev['DTSTART'])) continue;

        $dtstart = sjioc_parse_ics_dt($ev['DTSTART']['value'], $ev['DTSTART']['params'] ?? '');
        if (!$dtstart['date']) continue;

        $dtend = isset($ev['DTEND'])
            ? sjioc_parse_ics_dt($ev['DTEND']['value'], $ev['DTEND']['params'] ?? '')
            : null;

        $result[] = [
            'uid'         => $uid,
            'title'       => $sum,
            'description' => sjioc_ics_unescape($ev['DESCRIPTION']['value'] ?? ''),
            'location'    => sjioc_ics_unescape($ev['LOCATION']['value']    ?? ''),
            'url'         => $ev['URL']['value'] ?? '',
            'all_day'     => $dtstart['all_day'],
            'start_date'  => $dtstart['date'],
            'start_time'  => $dtstart['time'],
            'end_date'    => $dtend ? $dtend['date'] : null,
            'end_time'    => ($dtend && !$dtend['all_day']) ? $dtend['time'] : null,
        ];
    }
    return $result;
}

// ── Google Calendar API fetch ──────────────────────────────────────────────
function sjioc_fetch_gcal_events(int $months = 6): array {
    $key = SJIOC_GCAL_KEY;
    $id  = SJIOC_GCAL_ID;
    if (!$key || !$id) return [];

    $time_min = gmdate('Y-m-d\TH:i:s\Z');
    $time_max = gmdate('Y-m-d\TH:i:s\Z', strtotime("+{$months} months"));
    $url      = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($id)
              . '/events?key=' . rawurlencode($key)
              . '&timeMin=' . rawurlencode($time_min)
              . '&timeMax=' . rawurlencode($time_max)
              . '&singleEvents=true&orderBy=startTime&maxResults=100';

    $res = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($res)) return [];

    $data  = json_decode(wp_remote_retrieve_body($res), true);
    $items = $data['items'] ?? [];
    $ms    = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    return array_values(array_map(function ($item) use ($ms) {
        $start   = $item['start']['dateTime'] ?? $item['start']['date'] ?? '';
        $end     = $item['end']['dateTime']   ?? $item['end']['date']   ?? '';
        $all_day = !isset($item['start']['dateTime']);
        $ts      = $start ? strtotime($start) : 0;
        return [
            'id'          => $item['id']          ?? '',
            'title'       => $item['summary']     ?? '',
            'description' => $item['description'] ?? '',
            'location'    => $item['location']    ?? '',
            'start'       => $start,
            'end'         => $end,
            'all_day'     => $all_day,
            'mon'         => $ts ? $ms[(int)date('n', $ts) - 1] : '',
            'day'         => $ts ? (int)date('j', $ts) : '',
            'url'         => $item['htmlLink']    ?? '',
        ];
    }, $items));
}
