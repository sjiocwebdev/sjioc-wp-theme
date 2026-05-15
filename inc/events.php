<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   EVENTS — Google Calendar integration
───────────────────────────────────── */

// ── Constants (wp-config.php preferred; fallback to DB options) ────────────
if (!defined('SJIOC_GCAL_KEY'))      define('SJIOC_GCAL_KEY',      get_option('sjioc_gcal_key',      ''));
if (!defined('SJIOC_GCAL_ID'))       define('SJIOC_GCAL_ID',       get_option('sjioc_gcal_id',       ''));
if (!defined('SJIOC_GCAL_ICS'))      define('SJIOC_GCAL_ICS',      get_option('sjioc_gcal_ics',      ''));

// ── Enqueue events assets ──────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-events.php')) return;
    wp_enqueue_style('sjioc-events', SJIOC_URI . '/assets/css/events.css', [], SJIOC_VER);
    wp_enqueue_script('sjioc-events', SJIOC_URI . '/assets/js/events.js', [], SJIOC_VER, true);
    wp_localize_script('sjioc-events', 'SJIOC_EVENTS', [
        'restUrl' => rest_url('sjioc/v1/events'),
        'icsUrl'  => SJIOC_GCAL_ICS,
        'calId'   => SJIOC_GCAL_ID,
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);
});

// ── REST endpoint — GET /wp-json/sjioc/v1/events ──────────────────────────
add_action('rest_api_init', function () {
    register_rest_route('sjioc/v1', '/events', [
        'methods'             => 'GET',
        'callback'            => 'sjioc_events_rest',
        'permission_callback' => '__return_true',
        'args'                => [
            'months' => ['default' => 3, 'sanitize_callback' => fn($v) => max(1, min(12, (int) $v))],
        ],
    ]);
});

function sjioc_events_rest(WP_REST_Request $req) {
    $months  = (int) $req->get_param('months');
    $cache   = get_transient('sjioc_gcal_events_' . $months);
    if ($cache !== false) return rest_ensure_response($cache);

    $events = sjioc_fetch_gcal_events($months);
    set_transient('sjioc_gcal_events_' . $months, $events, HOUR_IN_SECONDS);
    return rest_ensure_response($events);
}

function sjioc_fetch_gcal_events(int $months = 3): array {
    $key = SJIOC_GCAL_KEY;
    $id  = SJIOC_GCAL_ID;

    if (!$key || !$id) return [];

    $time_min = gmdate('Y-m-d\TH:i:s\Z');
    $time_max = gmdate('Y-m-d\TH:i:s\Z', strtotime("+{$months} months"));
    $url      = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($id)
              . '/events?key=' . rawurlencode($key)
              . '&timeMin=' . rawurlencode($time_min)
              . '&timeMax=' . rawurlencode($time_max)
              . '&singleEvents=true&orderBy=startTime&maxResults=50';

    $res = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($res)) return [];

    $data  = json_decode(wp_remote_retrieve_body($res), true);
    $items = $data['items'] ?? [];

    return array_values(array_map(function ($item) {
        $start     = $item['start']['dateTime'] ?? $item['start']['date'] ?? '';
        $end       = $item['end']['dateTime']   ?? $item['end']['date']   ?? '';
        $all_day   = !isset($item['start']['dateTime']);
        $ts        = $start ? strtotime($start) : 0;
        return [
            'id'          => $item['id']              ?? '',
            'title'       => $item['summary']         ?? '',
            'description' => $item['description']     ?? '',
            'location'    => $item['location']        ?? '',
            'start'       => $start,
            'end'         => $end,
            'all_day'     => $all_day,
            'mon'         => $ts ? gmdate('M', $ts) : '',
            'day'         => $ts ? (int) gmdate('j', $ts) : '',
            'url'         => $item['htmlLink']        ?? '',
        ];
    }, $items));
}

// ── Front-page teaser: 3 upcoming events ──────────────────────────────────
function sjioc_front_page_events(): array {
    $cached = get_transient('sjioc_gcal_events_1');
    $items  = ($cached !== false) ? $cached : sjioc_fetch_gcal_events(1);
    if ($cached === false && $items) {
        set_transient('sjioc_gcal_events_1', $items, HOUR_IN_SECONDS);
    }

    if ($items) {
        return array_slice(array_map(fn($e) => [
            'mon'     => $e['mon'],
            'day'     => $e['day'],
            'title'   => $e['title'],
            'excerpt' => wp_trim_words($e['description'], 14, '…'),
        ], $items), 0, 3);
    }

    // Static fallback when calendar not configured
    return [
        ['mon' => 'Upcoming', 'day' => '',  'title' => 'Holy Qurbana',    'excerpt' => 'Every Sunday. Feast day celebrations and special services posted on our calendar.'],
        ['mon' => 'Upcoming', 'day' => '',  'title' => 'Sunday School',   'excerpt' => 'Classes for all ages following Holy Qurbana. New students always welcome.'],
        ['mon' => 'Upcoming', 'day' => '',  'title' => 'Parish Fellowship','excerpt' => 'Monthly fellowship gathering after service. Food, community, and good company.'],
    ];
}

// ── Admin settings page ────────────────────────────────────────────────────
function sjioc_events_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['sjioc_events_save'])) {
        check_admin_referer('sjioc_events_settings');
        update_option('sjioc_gcal_key', sanitize_text_field($_POST['sjioc_gcal_key'] ?? ''));
        update_option('sjioc_gcal_id',  sanitize_text_field($_POST['sjioc_gcal_id']  ?? ''));
        update_option('sjioc_gcal_ics', esc_url_raw($_POST['sjioc_gcal_ics'] ?? ''));
        // Bust cached events
        for ($m = 1; $m <= 12; $m++) delete_transient('sjioc_gcal_events_' . $m);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

    $key = defined('SJIOC_GCAL_KEY') && constant('SJIOC_GCAL_KEY') !== get_option('sjioc_gcal_key', '')
         ? '(set in wp-config.php)' : esc_attr(get_option('sjioc_gcal_key', ''));
    $id  = esc_attr(get_option('sjioc_gcal_id',  ''));
    $ics = esc_attr(get_option('sjioc_gcal_ics', ''));
    ?>
    <div class="wrap">
    <h1>Events — Google Calendar</h1>
    <p>Enter your Google Calendar credentials below. Alternatively, define constants in <code>wp-config.php</code> — constants take precedence over saved options.</p>
    <table class="widefat" style="max-width:620px;margin-bottom:18px"><thead><tr><th>wp-config.php constant</th><th>Purpose</th></tr></thead><tbody>
    <tr><td><code>SJIOC_GCAL_KEY</code></td><td>Google Calendar API key (server-side, never exposed to browser)</td></tr>
    <tr><td><code>SJIOC_GCAL_ID</code></td><td>Calendar ID (e.g. <code>abc123@group.calendar.google.com</code>)</td></tr>
    <tr><td><code>SJIOC_GCAL_ICS</code></td><td>Public ICS feed URL (for subscribe links)</td></tr>
    </tbody></table>
    <form method="post">
    <?php wp_nonce_field('sjioc_events_settings'); ?>
    <table class="form-table"><tbody>
    <tr>
        <th><label for="sjioc_gcal_key">API Key</label></th>
        <td><input type="password" id="sjioc_gcal_key" name="sjioc_gcal_key" value="<?php echo $key; ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="sjioc_gcal_id">Calendar ID</label></th>
        <td><input type="text" id="sjioc_gcal_id" name="sjioc_gcal_id" value="<?php echo $id; ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="sjioc_gcal_ics">ICS Feed URL</label></th>
        <td><input type="url" id="sjioc_gcal_ics" name="sjioc_gcal_ics" value="<?php echo $ics; ?>" class="regular-text"></td>
    </tr>
    </tbody></table>
    <?php submit_button('Save Settings', 'primary', 'sjioc_events_save'); ?>
    </form>
    <p class="description">Saving settings automatically clears the events cache.</p>
    </div>
    <?php
}
