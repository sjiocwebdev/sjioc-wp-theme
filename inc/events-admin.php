<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   META BOX — Event Details (sidebar)
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'sjioc-event-details', 'Event Details',
        'sjioc_event_meta_box_html', 'sjioc_event', 'side', 'high'
    );
});

function sjioc_event_meta_box_html(WP_Post $post): void {
    wp_nonce_field('sjioc_event_meta', 'sjioc_event_nonce');

    $date   = get_post_meta($post->ID, 'event_date',     true);
    $time   = get_post_meta($post->ID, 'event_time',     true);
    $end    = get_post_meta($post->ID, 'event_end_time', true);
    $allday = get_post_meta($post->ID, 'event_all_day',  true);
    $loc    = get_post_meta($post->ID, 'event_location', true);
    $cat    = get_post_meta($post->ID, 'event_category', true);

    // Pre-fill date when opened via calendar "+" link
    if (!$date && $post->post_status === 'auto-draft' && isset($_GET['event_date'])) {
        $raw = sanitize_text_field($_GET['event_date']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) $date = $raw;
    }

    $cats = [
        'worship'     => 'Worship',
        'fellowship'  => 'Fellowship',
        'education'   => 'Education',
        'outreach'    => 'Outreach',
        'special'     => 'Special',
    ];
    ?>
    <p style="margin-top:8px">
        <label style="font-weight:600;display:block;margin-bottom:3px">
            Date <span style="color:#b32d2e">*</span>
        </label>
        <input type="date" name="event_date" value="<?php echo esc_attr($date); ?>" style="width:100%">
    </p>

    <p>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:600">
            <input type="checkbox" name="event_all_day" id="ev-allday" value="1" <?php checked($allday, '1'); ?>>
            All Day
        </label>
    </p>

    <div id="ev-time-fields"<?php echo $allday === '1' ? ' style="display:none"' : ''; ?>>
        <p>
            <label style="font-weight:600;display:block;margin-bottom:3px">Start Time</label>
            <input type="text" name="event_time" value="<?php echo esc_attr($time); ?>"
                placeholder="10:00 AM" style="width:100%">
        </p>
        <p>
            <label style="font-weight:600;display:block;margin-bottom:3px">
                End Time <small style="font-weight:400">(optional)</small>
            </label>
            <input type="text" name="event_end_time" value="<?php echo esc_attr($end); ?>"
                placeholder="12:00 PM" style="width:100%">
        </p>
    </div>

    <p>
        <label style="font-weight:600;display:block;margin-bottom:3px">Location</label>
        <input type="text" name="event_location" value="<?php echo esc_attr($loc); ?>"
            placeholder="Parish Hall" style="width:100%">
    </p>

    <p>
        <label style="font-weight:600;display:block;margin-bottom:3px">Category</label>
        <select name="event_category" style="width:100%">
            <option value="">— Select —</option>
            <?php foreach ($cats as $val => $label): ?>
            <option value="<?php echo esc_attr($val); ?>" <?php selected($cat, $val); ?>>
                <?php echo esc_html($label); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </p>

    <script>
    document.getElementById('ev-allday').addEventListener('change', function () {
        document.getElementById('ev-time-fields').style.display = this.checked ? 'none' : '';
    });
    </script>
    <?php
}

add_action('save_post_sjioc_event', function (int $post_id): void {
    if (!isset($_POST['sjioc_event_nonce'])
        || !wp_verify_nonce($_POST['sjioc_event_nonce'], 'sjioc_event_meta')
    ) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    foreach (['event_date', 'event_time', 'event_end_time', 'event_location', 'event_category'] as $f) {
        update_post_meta($post_id, $f, sanitize_text_field($_POST[$f] ?? ''));
    }
    update_post_meta($post_id, 'event_all_day', isset($_POST['event_all_day']) ? '1' : '0');
});

/* ─────────────────────────────────────
   ADMIN CALENDAR PAGE
───────────────────────────────────── */
function sjioc_events_admin_page(): void {
    if (!current_user_can('manage_options')) return;

    $year  = (int) ($_GET['year']  ?? date('Y'));
    $month = (int) ($_GET['month'] ?? date('n'));
    if ($month < 1)  { $month = 12; $year--; }
    if ($month > 12) { $month = 1;  $year++; }

    $first_ts      = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = (int) date('t', $first_ts);
    $start_dow     = (int) date('w', $first_ts); // 0=Sun … 6=Sat

    $prev_m = $month === 1  ? 12 : $month - 1;  $prev_y = $month === 1  ? $year - 1 : $year;
    $next_m = $month === 12 ? 1  : $month + 1;  $next_y = $month === 12 ? $year + 1 : $year;

    $base_url = admin_url('admin.php?page=sjioc-events');
    $add_url  = admin_url('post-new.php?post_type=sjioc_event');
    $edit_url = admin_url('post.php?action=edit&post=');

    // Query all events for this month
    $posts = get_posts([
        'post_type'      => 'sjioc_event',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'event_date',
            'value'   => [date('Y-m-01', $first_ts), date('Y-m-t', $first_ts)],
            'compare' => 'BETWEEN',
            'type'    => 'DATE',
        ]],
    ]);

    $by_day = [];
    foreach ($posts as $p) {
        $d   = get_post_meta($p->ID, 'event_date', true);
        $day = (int) date('j', strtotime($d));
        $by_day[$day][] = [
            'id'    => $p->ID,
            'title' => $p->post_title,
            'cat'   => get_post_meta($p->ID, 'event_category', true),
            'time'  => get_post_meta($p->ID, 'event_time',     true),
            'allday'=> get_post_meta($p->ID, 'event_all_day',  true),
        ];
    }

    $cat_colors = [
        'worship'    => '#7c2d12',
        'fellowship' => '#14532d',
        'education'  => '#1e3a8a',
        'outreach'   => '#92400e',
        'special'    => '#4c1d95',
        ''           => '#555',
    ];

    $today           = (int) date('j');
    $is_current_month = ((int) date('n') === $month && (int) date('Y') === $year);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">📅 Events Calendar</h1>
        <a href="<?php echo esc_url($add_url); ?>" class="page-title-action">+ Add Event</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-import-events')); ?>" class="page-title-action">↑ Import</a>
        <hr class="wp-header-end">

        <!-- Month navigation -->
        <div style="display:flex;align-items:center;gap:12px;margin:16px 0 10px">
            <a href="<?php echo esc_url(add_query_arg(['month' => $prev_m, 'year' => $prev_y], $base_url)); ?>" class="button">◀ <?php echo date('M', mktime(0,0,0,$prev_m,1,$prev_y)); ?></a>
            <h2 style="margin:0;font-size:1.25rem;min-width:160px;text-align:center"><?php echo esc_html(date('F Y', $first_ts)); ?></h2>
            <a href="<?php echo esc_url(add_query_arg(['month' => $next_m, 'year' => $next_y], $base_url)); ?>" class="button"><?php echo date('M', mktime(0,0,0,$next_m,1,$next_y)); ?> ▶</a>
            <?php if (!$is_current_month): ?>
            <a href="<?php echo esc_url(add_query_arg(['month' => (int) date('n'), 'year' => (int) date('Y')], $base_url)); ?>" class="button button-secondary">Today</a>
            <?php endif; ?>
            <span style="margin-left:auto;color:#888;font-size:13px"><?php echo count($posts); ?> event<?php echo count($posts) !== 1 ? 's' : ''; ?> this month</span>
        </div>

        <!-- Category legend -->
        <div style="display:flex;gap:14px;margin-bottom:12px;flex-wrap:wrap">
            <?php foreach (['worship','fellowship','education','outreach','special'] as $c): ?>
            <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#555">
                <span style="width:11px;height:11px;border-radius:2px;background:<?php echo $cat_colors[$c]; ?>;display:inline-block"></span>
                <?php echo ucfirst($c); ?>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- Calendar grid -->
        <table style="width:100%;border-collapse:collapse;table-layout:fixed;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.1)">
            <thead>
                <tr>
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dname): ?>
                    <th style="padding:8px 4px;text-align:center;font-size:12px;font-weight:600;color:#666;background:#f6f7f7;border:1px solid #dcdcde">
                        <?php echo $dname; ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $cell = 0;
            echo '<tr>';
            for ($i = 0; $i < $start_dow; $i++) {
                echo '<td style="height:96px;border:1px solid #dcdcde;background:#f9f9f9;vertical-align:top;padding:4px"></td>';
                $cell++;
            }
            for ($day = 1; $day <= $days_in_month; $day++) {
                if ($cell > 0 && $cell % 7 === 0) echo '</tr><tr>';
                $is_today   = $is_current_month && $day === $today;
                $date_str   = date('Y-m-', $first_ts) . sprintf('%02d', $day);
                $add_day_url = esc_url(add_query_arg('event_date', $date_str, $add_url));
                $bg = $is_today ? '#fffbeb' : '#fff';
                echo '<td style="height:96px;border:1px solid #dcdcde;background:' . $bg . ';vertical-align:top;padding:4px">';
                echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">';
                $day_style = $is_today
                    ? 'font-size:13px;font-weight:700;color:#fff;background:#d97706;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center'
                    : 'font-size:13px;color:#333';
                echo '<span style="' . $day_style . '">' . $day . '</span>';
                echo '<a href="' . $add_day_url . '" style="text-decoration:none;color:#aaa;font-size:18px;line-height:1;font-weight:300" title="Add event on ' . esc_attr(date('M j Y', strtotime($date_str))) . '">+</a>';
                echo '</div>';
                if (!empty($by_day[$day])) {
                    foreach ($by_day[$day] as $ev) {
                        $color  = $cat_colors[$ev['cat']] ?? '#555';
                        $label  = esc_html(mb_strimwidth($ev['title'], 0, 20, '…'));
                        $tip    = esc_attr($ev['title'] . ($ev['allday'] === '1' ? ' · All Day' : ($ev['time'] ? ' · ' . $ev['time'] : '')));
                        echo '<a href="' . esc_url($edit_url . $ev['id']) . '" title="' . $tip . '" style="display:block;background:' . $color . ';color:#fff;font-size:11px;padding:2px 5px;border-radius:3px;margin-bottom:2px;text-decoration:none;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">' . $label . '</a>';
                    }
                }
                echo '</td>';
                $cell++;
            }
            $trailing = 7 - ($cell % 7);
            if ($trailing < 7) {
                for ($i = 0; $i < $trailing; $i++) {
                    echo '<td style="height:96px;border:1px solid #dcdcde;background:#f9f9f9;vertical-align:top;padding:4px"></td>';
                }
            }
            echo '</tr>';
            ?>
            </tbody>
        </table>

        <?php if (empty($posts)): ?>
        <p style="margin-top:16px;text-align:center;color:#888">
            No events in <?php echo esc_html(date('F Y', $first_ts)); ?>.
            <a href="<?php echo esc_url($add_url); ?>">Add one</a> or
            <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-import-events')); ?>">import from CSV/XLSX</a>.
        </p>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────
   IMPORT EVENTS PAGE
───────────────────────────────────── */
function sjioc_import_events_page(): void {
    if (!current_user_can('manage_options')) return;

    $result = null;

    if (isset($_POST['sjioc_import_events']) && check_admin_referer('sjioc_import_events_nonce')) {
        if (empty($_FILES['events_file']['tmp_name'])) {
            $result = ['error' => 'No file uploaded.'];
        } else {
            $file = $_FILES['events_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx'], true)) {
                $result = ['error' => 'Only .csv and .xlsx files are accepted.'];
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $result = ['error' => 'File exceeds 5 MB limit.'];
            } else {
                $on_dup = ($_POST['on_dup'] ?? 'update') === 'skip' ? 'skip' : 'update';
                $rows   = $ext === 'xlsx'
                    ? sjioc_parse_xlsx($file['tmp_name'])
                    : sjioc_parse_csv($file['tmp_name']);
                $result = sjioc_import_event_rows($rows, $on_dup);
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>📥 Import Events</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-events')); ?>" class="button" style="margin-bottom:16px">← Back to Calendar</a>

        <?php if ($result): ?>
            <?php if (isset($result['error'])): ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html($result['error']); ?></p></div>
            <?php else: ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Import complete.</strong>
                    <?php echo (int) $result['inserted']; ?> created &nbsp;·&nbsp;
                    <?php echo (int) $result['updated'];  ?> updated &nbsp;·&nbsp;
                    <?php echo (int) $result['skipped'];  ?> skipped.
                </p>
            </div>
            <?php if (!empty($result['row_errors'])): ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Rows with issues:</strong></p>
                <ul style="margin-left:16px;list-style:disc">
                    <?php foreach ($result['row_errors'] as $e): ?>
                    <li><?php echo esc_html($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width:600px">
            <?php wp_nonce_field('sjioc_import_events_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="events_file">File</label></th>
                    <td>
                        <input id="events_file" type="file" name="events_file" accept=".csv,.xlsx" required>
                        <p class="description">Excel (.xlsx) or CSV (.csv) — max 5 MB.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Duplicates</th>
                    <td>
                        <label style="display:block;margin-bottom:6px">
                            <input type="radio" name="on_dup" value="update" checked>
                            Update existing (matched by Title + Date)
                        </label>
                        <label>
                            <input type="radio" name="on_dup" value="skip">
                            Skip — only add new events
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Upload & Import', 'primary', 'sjioc_import_events'); ?>
        </form>

        <hr style="margin:28px 0">
        <h3>Expected Column Headers</h3>
        <p class="description" style="margin-bottom:10px">First row must be headers. Column order does not matter.</p>
        <table class="widefat striped" style="max-width:540px">
            <thead><tr><th>Column</th><th>Required</th><th>Notes</th></tr></thead>
            <tbody>
                <tr><td><strong>Title</strong></td><td>✔ Yes</td><td>Event name</td></tr>
                <tr><td><strong>Date</strong></td><td>✔ Yes</td><td>DD/MM/YYYY or YYYY-MM-DD</td></tr>
                <tr><td>Time</td><td>No</td><td>e.g. 10:00 AM</td></tr>
                <tr><td>End Time</td><td>No</td><td>e.g. 12:00 PM</td></tr>
                <tr><td>All Day</td><td>No</td><td>Yes / No</td></tr>
                <tr><td>Location</td><td>No</td><td>e.g. Parish Hall</td></tr>
                <tr><td>Category</td><td>No</td><td>worship · fellowship · education · outreach · special</td></tr>
                <tr><td>Description</td><td>No</td><td>Event body text / excerpt</td></tr>
            </tbody>
        </table>
    </div>
    <?php
}

/* ─────────────────────────────────────
   IMPORT EVENTS — ROW PROCESSOR
───────────────────────────────────── */
function sjioc_import_event_rows(array $rows, string $on_dup): array {
    global $wpdb;

    if (count($rows) < 2) {
        return ['error' => 'File has no data rows (only a header or is empty).'];
    }

    $headers = array_map('sjioc_normalize_header', $rows[0]);
    $aliases  = [
        'title'       => ['title', 'event_title', 'event_name', 'name'],
        'date'        => ['date', 'event_date', 'start_date'],
        'time'        => ['time', 'start_time', 'event_time'],
        'end_time'    => ['end_time', 'end'],
        'all_day'     => ['all_day', 'allday', 'full_day'],
        'location'    => ['location', 'venue', 'place'],
        'category'    => ['category', 'cat', 'type'],
        'description' => ['description', 'desc', 'details', 'notes'],
    ];

    $col_map = [];
    foreach ($aliases as $key => $names) {
        foreach ($names as $name) {
            $idx = array_search($name, $headers, true);
            if ($idx !== false) { $col_map[$key] = $idx; break; }
        }
    }

    if (!isset($col_map['title'], $col_map['date'])) {
        return ['error' => 'Required columns "Title" and "Date" not found. Check your header row.'];
    }

    $get        = fn($row, $col) => isset($col_map[$col]) ? trim((string) ($row[$col_map[$col]] ?? '')) : '';
    $valid_cats = ['worship', 'fellowship', 'education', 'outreach', 'special'];

    $inserted   = 0;
    $updated    = 0;
    $skipped    = 0;
    $row_errors = [];

    foreach (array_slice($rows, 1) as $line => $row) {
        $title = $get($row, 'title');
        $date  = sjioc_import_parse_date($get($row, 'date'));
        if (!$title || !$date) { $skipped++; continue; }

        $all_day_raw = strtolower($get($row, 'all_day'));
        $all_day     = in_array($all_day_raw, ['yes', '1', 'true', 'y'], true) ? '1' : '0';

        $cat = strtolower($get($row, 'category'));
        if (!in_array($cat, $valid_cats, true)) $cat = '';

        // Duplicate check: same title + same event_date meta
        $existing_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type   = 'sjioc_event'
               AND p.post_status != 'trash'
               AND p.post_title  = %s
               AND pm.meta_key   = 'event_date'
               AND pm.meta_value = %s
             LIMIT 1",
            $title, $date
        ));

        if ($existing_id) {
            if ($on_dup === 'skip') { $skipped++; continue; }
            $desc = sanitize_textarea_field($get($row, 'description'));
            wp_update_post(['ID' => $existing_id, 'post_content' => $desc]);
            $post_id = $existing_id;
            $updated++;
        } else {
            $post_id = wp_insert_post([
                'post_type'    => 'sjioc_event',
                'post_status'  => 'publish',
                'post_title'   => sanitize_text_field($title),
                'post_content' => sanitize_textarea_field($get($row, 'description')),
            ]);
            if (is_wp_error($post_id)) {
                $row_errors[] = 'Row ' . ($line + 2) . ': ' . $post_id->get_error_message();
                continue;
            }
            $inserted++;
        }

        update_post_meta($post_id, 'event_date',     $date);
        update_post_meta($post_id, 'event_time',     sanitize_text_field($get($row, 'time')));
        update_post_meta($post_id, 'event_end_time', sanitize_text_field($get($row, 'end_time')));
        update_post_meta($post_id, 'event_all_day',  $all_day);
        update_post_meta($post_id, 'event_location', sanitize_text_field($get($row, 'location')));
        update_post_meta($post_id, 'event_category', $cat);
    }

    return compact('inserted', 'updated', 'skipped', 'row_errors');
}
