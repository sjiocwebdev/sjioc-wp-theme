<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   EVENTS — DB-backed; monthly calendar upload + manual
───────────────────────────────────── */

// ── DB table ───────────────────────────────────────────────────────────────
function sjioc_events_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'sjioc_events';
}

add_action('after_switch_theme', 'sjioc_create_events_table');
add_action('admin_init', function () {
    if (get_option('sjioc_events_db_ver') !== '2') {
        sjioc_create_events_table();
        // Outlook/Google Calendar sync has been removed — drop any events it
        // previously synced in; spreadsheet upload + manual entry are now
        // the only sources, and both use source='manual'.
        global $wpdb;
        $wpdb->query("DELETE FROM " . sjioc_events_table() . " WHERE source IN ('gcal','outlook')");
        update_option('sjioc_events_db_ver', '2');
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
        is_highlight tinyint(1)  DEFAULT 0,
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
            'months'      => ['default' => 6, 'sanitize_callback' => fn($v) => max(1, min(12, (int)$v))],
            'months_back' => ['default' => 0, 'sanitize_callback' => fn($v) => max(0, min(24, (int)$v))],
        ],
    ]);
    register_rest_route('sjioc/v1', '/calendar\.ics', [
        'methods'             => 'GET',
        'callback'            => 'sjioc_calendar_ics_endpoint',
        'permission_callback' => '__return_true',
    ]);
});

// Simple per-IP request cap for public REST routes — same transient pattern
// used everywhere else in this theme (chat, contact form, photo proxy).
function sjioc_rest_rate_limited(string $key_prefix, int $max, int $window_seconds): bool {
    $key  = $key_prefix . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits = (int) get_transient($key);
    if ($hits >= $max) return true;
    set_transient($key, $hits + 1, $window_seconds);
    return false;
}

function sjioc_events_rest(WP_REST_Request $req): WP_REST_Response {
    if (sjioc_rest_rate_limited('sjioc_rl_events_', 30, 5 * MINUTE_IN_SECONDS)) {
        return new WP_REST_Response(['message' => 'Too many requests.'], 429);
    }
    return rest_ensure_response(sjioc_get_db_events((int)$req->get_param('months'), (int)$req->get_param('months_back')));
}

// $months_back > 0 widens the lower bound into the past — used by the public
// Events page so visitors can browse earlier months; every other caller
// (home page teaser, widget-bar panel, the outbound .ics feed) leaves it at
// 0 and stays future-only, which is the right default for those.
function sjioc_get_db_events(int $months = 6, int $months_back = 0): array {
    global $wpdb;
    $t        = sjioc_events_table();
    $today    = current_time('Y-m-d');
    $min_date = $months_back > 0
        ? date('Y-m-d', strtotime("-{$months_back} months", current_time('timestamp')))
        : $today;
    $max_date = date('Y-m-d', strtotime("+{$months} months", current_time('timestamp')));
    $mshort   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$t} WHERE start_date >= %s AND start_date <= %s ORDER BY start_date, start_time",
        $min_date, $max_date
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
            'id'           => (string) $r->id,
            'title'        => $r->title,
            'description'  => $r->description ?: '',
            'location'     => $r->location    ?: '',
            'start'        => $start,
            'end'          => $end,
            'all_day'      => $all,
            'mon'          => $mshort[(int)date('n', $ts) - 1],
            'day'          => (int)date('j', $ts),
            'url'          => $r->url ?: '',
            'is_highlight' => (bool)(int)$r->is_highlight,
        ];
    }, $rows);
}

// ── Front-page teaser ──────────────────────────────────────────────────────
function sjioc_front_page_events(): array {
    $items = sjioc_get_db_events(1);
    if ($items) {
        return array_slice(array_map(fn($e) => [
            'mon'          => $e['mon'],
            'day'          => $e['day'],
            'title'        => $e['title'],
            'excerpt'      => wp_trim_words($e['description'], 14, '…'),
            'is_highlight' => $e['is_highlight'],
        ], $items), 0, 3);
    }
    return [
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Holy Qurbana',     'excerpt' => 'Every Sunday. Feast day celebrations posted on our calendar.', 'is_highlight' => false],
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Sunday School',    'excerpt' => 'Classes for all ages following Holy Qurbana. New students welcome.', 'is_highlight' => false],
        ['mon' => 'Upcoming', 'day' => '', 'title' => 'Parish Fellowship', 'excerpt' => 'Monthly fellowship gathering after service. All welcome.', 'is_highlight' => false],
    ];
}

// ── Widget-bar Calendar panel — cached wrapper, since footer.php (and thus
//    this call) loads on every page site-wide, unlike the home-page-only
//    teaser above. 30-min transient keeps it within the +1-query budget.
function sjioc_get_widget_calendar_events(): array {
    $cached = get_transient('sjioc_widget_calendar_events');
    if ($cached !== false) return $cached;
    $items = sjioc_front_page_events();
    set_transient('sjioc_widget_calendar_events', $items, 30 * MINUTE_IN_SECONDS);
    return $items;
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

    // ── Import monthly calendar (.xlsx) ───────────────────────────────────
    if (isset($_POST['sjioc_import_xlsx'])) {
        check_admin_referer('sjioc_events_admin');
        if (!empty($_FILES['ev_xlsx']['tmp_name']) && $_FILES['ev_xlsx']['error'] === UPLOAD_ERR_OK) {
            $result = sjioc_parse_import_xlsx($_FILES['ev_xlsx']['tmp_name']);
            if ($result['errors']) {
                $notice = '<div class="notice notice-error"><p>' . esc_html(implode(' | ', $result['errors'])) . '</p></div>';
            } else {
                $msg = $result['imported'] . ' event(s) imported.';
                if ($result['skipped']) $msg .= ' ' . $result['skipped'] . ' already on the calendar, skipped.';
                $notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            }
        } else {
            $notice = '<div class="notice notice-error"><p>No file selected or upload failed.</p></div>';
        }
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
            'title'       => sanitize_text_field(wp_unslash($_POST['ev_title']   ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['ev_desc'] ?? '')),
            'location'    => sanitize_text_field(wp_unslash($_POST['ev_location'] ?? '')),
            'start_date'  => $start_d,
            'start_time'  => $start_t,
            'end_date'    => $end_d,
            'end_time'    => $end_t,
            'all_day'     => $all_day,
            'url'         => esc_url_raw(wp_unslash($_POST['ev_url'] ?? '')),
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
    $base_url = admin_url('admin.php?page=sjioc-events');

    $today      = current_time('Y-m-d');
    $all_events = $wpdb->get_results(
        "SELECT * FROM {$t} WHERE start_date >= '{$today}' ORDER BY start_date, start_time"
    );
    ?>
    <div class="wrap">
    <h1>Events</h1>
    <?php echo $notice; ?>

    <!-- ── Upload Monthly Calendar ── -->
    <h2 class="title">Upload Monthly Calendar</h2>
    <p style="color:#555;margin-bottom:12px">
      Upload the secretary's monthly calendar workbook (.xlsx) exactly as-is — no reformatting needed.
      Each day's text becomes one or more events automatically; entries already on the calendar are skipped.
    </p>
    <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('sjioc_events_admin'); ?>
    <p>
      <input type="file" name="ev_xlsx" accept=".xlsx">
      &nbsp;
      <?php submit_button('Upload Calendar', 'primary', 'sjioc_import_xlsx', false); ?>
    </p>
    </form>

    <hr style="margin:28px 0">

    <!-- ── Import from Spreadsheet ── -->
    <h2 class="title">Import from Spreadsheet (CSV)</h2>
    <p style="color:#555;margin-bottom:12px">
      For a simple one-row-per-event list instead of the monthly calendar above.
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
      <th style="width:120px">Actions</th>
    </tr></thead><tbody>
    <?php foreach ($all_events as $ev) :
        $del_url  = wp_nonce_url($base_url . '&del_ev=' . $ev->id, 'sjioc_del_ev_' . $ev->id);
        $edit_url = $base_url . '&edit_ev=' . $ev->id . '#ev-form-heading';
    ?>
    <tr>
      <td><?php echo esc_html(date('M j, Y', strtotime($ev->start_date))); ?></td>
      <td><?php echo esc_html($ev->title); ?>
        <?php if ($ev->is_highlight) : ?><span style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9a6b00;background:#fdf0d0;border-radius:3px;padding:2px 6px;margin-left:6px">Special</span><?php endif; ?>
      </td>
      <td><?php echo esc_html($ev->location ?: '—'); ?></td>
      <td>
        <a href="<?php echo esc_url($edit_url); ?>">Edit</a>
        &nbsp;|&nbsp;
        <a href="<?php echo esc_url($del_url); ?>"
           onclick="return confirm('Delete \'<?php echo esc_js($ev->title); ?>\'?')"
           style="color:#b32d2e">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php else : ?>
    <p style="color:#666">No upcoming events. Add one above, or upload the monthly calendar.</p>
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
    })();
    </script>
    <?php
}

// ── ICS calendar download ──────────────────────────────────────────────────
function sjioc_calendar_ics_endpoint(): void {
    if (sjioc_rest_rate_limited('sjioc_rl_ics_', 10, 30 * MINUTE_IN_SECONDS)) {
        status_header(429);
        header('Retry-After: 1800');
        exit('Too many requests. Please try again later.');
    }
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
            // Interpret the stored wall-clock time as the site's own timezone,
            // then convert to true UTC — so subscribers outside the church's
            // timezone still see the correct local time on their own calendar.
            $site_tz = wp_timezone();
            $utc_tz  = new DateTimeZone('UTC');
            $dt_s    = new DateTimeImmutable($e['start'], $site_tz);
            $dt_e    = $e['end'] ? new DateTimeImmutable($e['end'], $site_tz) : $dt_s->modify('+1 hour');
            $lines[] = 'DTSTART:' . $dt_s->setTimezone($utc_tz)->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'   . $dt_e->setTimezone($utc_tz)->format('Ymd\THis\Z');
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
    // Fold by UTF-8 character, not raw byte, so a multi-byte character
    // (e.g. Malayalam text) never gets split across a fold boundary.
    foreach (mb_str_split($line) as $ch) {
        $ch_len = strlen($ch);
        if ($len > 0 && $len + $ch_len > 74) { $out .= "\r\n "; $len = 0; }
        $out .= $ch;
        $len += $ch_len;
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

// ── Monthly calendar (.xlsx) import ─────────────────────────────────────────
// Reads the uploaded workbook's first sheet directly (xlsx is just a zip of
// XML — no external library). The secretary's sheet is a visual month grid:
// a row of real date cells, immediately followed by a row holding that
// week's text, one cell per weekday. Each day's cell can hold more than one
// item, separated by the long runs of spaces she already uses as a line
// break. A cell's red font marks a feast day; anything on a day other than
// Sunday is inherently non-routine too — both get flagged as "Special".

function sjioc_xlsx_shared_strings(ZipArchive $zip): array {
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) return [];
    $sx = @simplexml_load_string($xml);
    if (!$sx) return [];
    $out = [];
    foreach ($sx->si as $si) {
        if (isset($si->t)) {
            $out[] = (string) $si->t;
        } else {
            $text = '';
            foreach ($si->r as $r) $text .= (string) $r->t;
            $out[] = $text;
        }
    }
    return $out;
}

// Maps each cellXfs style index to that cell's font color (RRGGBB), if any.
function sjioc_xlsx_style_font_colors(ZipArchive $zip): array {
    $xml = $zip->getFromName('xl/styles.xml');
    if ($xml === false) return [];
    $sx = @simplexml_load_string($xml);
    if (!$sx) return [];

    $font_colors = [];
    if (isset($sx->fonts)) {
        $i = 0;
        foreach ($sx->fonts->font as $font) {
            $font_colors[$i] = (isset($font->color) && isset($font->color['rgb']))
                ? strtoupper(substr((string) $font->color['rgb'], -6))
                : null;
            $i++;
        }
    }

    $style_map = [];
    if (isset($sx->cellXfs)) {
        $i = 0;
        foreach ($sx->cellXfs->xf as $xf) {
            $font_id = isset($xf['fontId']) ? (int) $xf['fontId'] : 0;
            $style_map[$i] = $font_colors[$font_id] ?? null;
            $i++;
        }
    }
    return $style_map;
}

function sjioc_xlsx_serial_to_ymd(string $raw): ?string {
    if (!is_numeric($raw)) return null;
    $serial = (float) $raw;
    if ($serial < 2 || $serial > 60000) return null; // plausible 1900–2064 range
    $unix = ($serial - 25569) * 86400; // Excel epoch 1899-12-30 → Unix epoch
    return gmdate('Y-m-d', (int) round($unix));
}

function sjioc_parse_import_xlsx(string $file): array {
    $imported = 0;
    $skipped  = 0;

    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not open the file — is it a valid .xlsx?']];
    }

    // Resolve the first sheet listed in the workbook, whatever it's named.
    $sheet_path = 'xl/worksheets/sheet1.xml';
    $wb_xml   = $zip->getFromName('xl/workbook.xml');
    $rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb_xml && $rels_xml) {
        $wb   = @simplexml_load_string($wb_xml);
        $rels = @simplexml_load_string($rels_xml);
        $first_sheet = $wb->sheets->sheet[0] ?? null;
        if ($first_sheet && $rels) {
            $rid = (string) $first_sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($rels->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $sheet_path = 'xl/' . ltrim((string) $rel['Target'], '/');
                    break;
                }
            }
        }
    }

    $sheet_xml = $zip->getFromName($sheet_path);
    if ($sheet_xml === false) {
        $zip->close();
        return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read the first sheet in that file.']];
    }
    $shared = sjioc_xlsx_shared_strings($zip);
    $styles = sjioc_xlsx_style_font_colors($zip);
    $zip->close();

    $sx = @simplexml_load_string($sheet_xml);
    if (!$sx || !isset($sx->sheetData)) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read that sheet — is it a valid calendar export?']];
    }

    // Build [row_num][col_letter] => ['text'=>?, 'date'=>?, 'red'=>bool]
    $grid = [];
    foreach ($sx->sheetData->row as $row) {
        $r = (int) $row['r'];
        foreach ($row->c as $c) {
            if (!preg_match('/^([A-Z]+)\d+$/', (string) $c['r'], $m)) continue;
            $col    = $m[1];
            $type   = (string) ($c['t'] ?? '');
            $s_idx  = isset($c['s']) ? (int) $c['s'] : 0;
            $raw    = isset($c->v) ? (string) $c->v : null;
            if ($raw === null) continue;

            $text = null;
            $date = null;
            if ($type === 's') {
                $text = $shared[(int) $raw] ?? '';
            } elseif ($type === 'str') {
                $text = $raw;
            } elseif ($type === '' || $type === 'n') {
                $date = sjioc_xlsx_serial_to_ymd($raw);
                if ($date === null) $text = $raw;
            }
            if ($text === null && $date === null) continue;

            $grid[$r][$col] = ['text' => $text, 'date' => $date, 'red' => (($styles[$s_idx] ?? null) === 'FF0000')];
        }
    }

    if (!$grid) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => ['No data found on that sheet.']];
    }
    ksort($grid);
    $rows = array_keys($grid);

    global $wpdb;
    $t = sjioc_events_table();
    $is_date_row = fn($cells) => count(array_filter($cells, fn($c) => $c['date'] !== null)) >= 3;

    $i = 0;
    $n = count($rows);
    while ($i < $n) {
        $r = $rows[$i];
        if (!$is_date_row($grid[$r])) { $i++; continue; }

        // A real per-week date row is always followed by a content row, never
        // another date row. A row that IS followed by another date row is a
        // one-off header strip (e.g. weekday names built from formatted date
        // serials) — skip just that row and re-check the next one.
        $peek = $rows[$i + 1] ?? null;
        if ($peek !== null && $is_date_row($grid[$peek])) { $i++; continue; }

        $date_cells = array_filter($grid[$r], fn($c) => $c['date'] !== null);
        $i++;
        $content_row = (isset($rows[$i]) && $rows[$i] === $r + 1) ? $grid[$rows[$i]] : [];
        if ($content_row) $i++;

        foreach ($date_cells as $col => $date_cell) {
            $day_cell = $content_row[$col] ?? null;
            if (!$day_cell || $day_cell['text'] === null || trim($day_cell['text']) === '') continue;

            $date    = $date_cell['date'];
            $weekday = (int) gmdate('w', strtotime($date)); // 0 = Sunday
            $special = $day_cell['red'] || $weekday !== 0;

            foreach (preg_split('/\s{3,}/', trim($day_cell['text'])) as $seg) {
                $seg = trim(preg_replace('/\s+/', ' ', $seg), " \t\n\r\0\x0B,-–—");
                if ($seg === '') continue;

                $start_time = null;
                if (preg_match('/\b(1[0-2]|0?[1-9])(:[0-5]\d)?\s*([AaPp]\.?[Mm]\.?)\b/', $seg, $tm)) {
                    $ts = strtotime($tm[1] . ($tm[2] ?: ':00') . ' ' . strtoupper(str_replace('.', '', $tm[3])));
                    if ($ts) $start_time = date('H:i:s', $ts);
                }

                $title = sanitize_text_field(mb_substr($seg, 0, 255));

                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$t} WHERE title=%s AND start_date=%s LIMIT 1", $title, $date
                ));
                if ($exists) { $skipped++; continue; }

                $wpdb->insert($t, [
                    'title'        => $title,
                    'description'  => '',
                    'location'     => '',
                    'start_date'   => $date,
                    'start_time'   => $start_time,
                    'end_date'     => null,
                    'end_time'     => null,
                    'all_day'      => $start_time ? 0 : 1,
                    'url'          => '',
                    'source'       => 'manual',
                    'is_highlight' => $special ? 1 : 0,
                ], ['%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%d']);
                $imported++;
            }
        }
    }

    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => []];
}
