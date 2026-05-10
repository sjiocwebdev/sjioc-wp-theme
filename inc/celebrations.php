<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CUSTOM CRON INTERVAL
───────────────────────────────────── */
add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['sjioc_weekly'])) {
        $schedules['sjioc_weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display'  => __('Once Weekly (SJIOC)', 'sjioc'),
        ];
    }
    return $schedules;
});

/* ─────────────────────────────────────
   SCHEDULE ON ACTIVATION / CLEAR ON DEACTIVATION
───────────────────────────────────── */
add_action('after_switch_theme', function () {
    if (!wp_next_scheduled('sjioc_celebrations_cron')) {
        // Fire at 12:01 AM every Monday
        wp_schedule_event(strtotime('next Monday midnight') + 60, 'sjioc_weekly', 'sjioc_celebrations_cron');
    }
    // Seed cache immediately on activation so the panel isn't blank
    sjioc_run_celebrations_cron();
});

add_action('switch_theme', function () {
    wp_clear_scheduled_hook('sjioc_celebrations_cron');
});

/* ─────────────────────────────────────
   CRON CALLBACK — build cache
───────────────────────────────────── */
add_action('sjioc_celebrations_cron', 'sjioc_run_celebrations_cron');

function sjioc_run_celebrations_cron() {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';

    // 7-day window: always Mon–Sun of the current week
    $now        = current_time('timestamp');
    $dow        = (int) date('N', $now);                                          // 1=Mon … 7=Sun
    $monday_ts  = strtotime(date('Y-m-d 00:00:00', $now - ($dow - 1) * DAY_IN_SECONDS));
    $md_slots   = [];
    for ($i = 0; $i < 7; $i++) {
        $md_slots[] = date('m-d', $monday_ts + $i * DAY_IN_SECONDS);
    }
    $ph = implode(',', array_fill(0, 7, '%s'));

    // ── Birthdays ─────────────────────────────────────────
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT first_name, last_name, date_of_birth
             FROM {$table}
             WHERE is_active = 1
               AND date_of_birth IS NOT NULL
               AND DATE_FORMAT(date_of_birth, '%%m-%%d') IN ({$ph})
             ORDER BY DATE_FORMAT(date_of_birth, '%%m-%%d')",
            ...$md_slots
        )
    );

    $birthdays = [];
    foreach ($rows as $r) {
        $ts          = strtotime($r->date_of_birth);
        $birthdays[] = [
            'name'       => trim($r->first_name) . ' ' . trim($r->last_name),
            'month_name' => strtoupper(date('M', $ts)),
            'day'        => (int) date('j', $ts),
        ];
    }

    // ── Anniversaries ─────────────────────────────────────
    // Match husband (gender M) + wife (gender F) by cardex_no + wedding_date
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT h.first_name AS hf, h.last_name AS hl,
                    w.first_name AS wf,
                    h.wedding_date
             FROM {$table} h
             JOIN {$table} w
               ON h.cardex_no    = w.cardex_no
              AND h.wedding_date = w.wedding_date
              AND h.gender       = 'M'
              AND w.gender       = 'F'
             WHERE h.marital_status = 'M'
               AND h.is_active  = 1
               AND w.is_active  = 1
               AND h.wedding_date IS NOT NULL
               AND DATE_FORMAT(h.wedding_date, '%%m-%%d') IN ({$ph})
             ORDER BY DATE_FORMAT(h.wedding_date, '%%m-%%d')",
            ...$md_slots
        )
    );

    $anniversaries = [];
    foreach ($rows as $r) {
        $ts              = strtotime($r->wedding_date);
        $anniversaries[] = [
            'names'      => trim($r->hf) . ' & ' . trim($r->wf) . ' ' . trim($r->hl),
            'month_name' => strtoupper(date('M', $ts)),
            'day'        => (int) date('j', $ts),
        ];
    }

    // ── Store cache ───────────────────────────────────────
    $week_start = date('M j', $monday_ts);
    $week_end   = date('D, M j, Y', $monday_ts + 6 * DAY_IN_SECONDS);

    update_option('sjioc_celebrations_cache', [
        'generated_at'  => current_time('mysql'),
        'week_label'    => $week_start . ' – ' . $week_end,
        'birthdays'     => $birthdays,
        'anniversaries' => $anniversaries,
    ], false);  // false = do not autoload this big option
}
