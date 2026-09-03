<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Vicar History — powers the "Vicar Leadership Timeline" shown on
   Our History + About Us. Each entry = one Vicar: name (title), photo
   (featured image, shown circular), and a start/end date. Leave End
   Date blank for the current Vicar — displays as "Present". Ordered
   chronologically by Start Date.
───────────────────────────────────── */
function sjioc_register_vicar_history() {
    register_post_type('sjioc_vicar_history', [
        'labels'        => [
            'name'          => __('Vicar History',      'sjioc'),
            'singular_name' => __('Vicar',               'sjioc'),
            'add_new_item'  => __('Add Vicar',            'sjioc'),
            'edit_item'     => __('Edit Vicar',           'sjioc'),
            'menu_name'     => __('Vicar History',        'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sjioc',
        'menu_icon'     => 'dashicons-businessman',
        'supports'      => ['title', 'thumbnail'],
        'show_in_rest'  => false,
    ]);
}
add_action('init', 'sjioc_register_vicar_history');

add_action('add_meta_boxes', function () {
    add_meta_box('sjioc_vicar_history_dates', 'Term of Service', 'sjioc_vicar_history_meta_box_html', 'sjioc_vicar_history', 'normal', 'high');
});

function sjioc_vicar_history_meta_box_html($post) {
    wp_nonce_field('sjioc_vicar_history_save', 'sjioc_vicar_history_nonce');
    $start = get_post_meta($post->ID, 'vicar_period_start', true);
    $end   = get_post_meta($post->ID, 'vicar_period_end',   true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="vicar_period_start">Start Date</label></th>
            <td><input type="date" id="vicar_period_start" name="vicar_period_start" value="<?php echo esc_attr($start); ?>" required></td>
        </tr>
        <tr>
            <th><label for="vicar_period_end">End Date</label></th>
            <td>
                <input type="date" id="vicar_period_end" name="vicar_period_end" value="<?php echo esc_attr($end); ?>">
                <p class="description">Leave blank if this Vicar is currently serving — will be shown as "Present".</p>
            </td>
        </tr>
    </table>
    <p class="description">Set the <strong>Featured Image</strong> (right sidebar) to the Vicar's photo. A close, front-facing headshot works best — it's shown in a circular frame.</p>
    <?php
}

add_action('save_post_sjioc_vicar_history', function ($post_id) {
    if (!isset($_POST['sjioc_vicar_history_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_vicar_history_nonce'], 'sjioc_vicar_history_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'vicar_period_start', sanitize_text_field(wp_unslash($_POST['vicar_period_start'] ?? '')));
    update_post_meta($post_id, 'vicar_period_end',   sanitize_text_field(wp_unslash($_POST['vicar_period_end']   ?? '')));
});

/* ─────────────────────────────────────
   One-time seed — carries over the 4 Vicars that previously lived in
   the plain-text Vicar Leadership Timeline field, so the admin only
   has to attach photos rather than re-type names/dates. Runs once,
   admin-side only (zero cost to public page loads).
───────────────────────────────────── */
add_action('admin_init', function () {
    if (get_option('sjioc_vicar_history_seeded')) return;

    $counts = wp_count_posts('sjioc_vicar_history');
    if ($counts && array_sum((array) $counts) > 0) {
        update_option('sjioc_vicar_history_seeded', 1);
        return;
    }

    $seed = [
        ['name' => 'Rev. Fr. Geevarghese Errakkath', 'start' => '2006-11-15', 'end' => '2008-04-19'],
        ['name' => 'Rev. Fr. Roy P. George',          'start' => '2008-04-19', 'end' => '2009-03-27'],
        ['name' => 'Rev. Fr. Siby Varghese',          'start' => '2009-06-01', 'end' => '2024-05-30'],
        ['name' => 'Rev. Fr. Tojo Baby',              'start' => '2024-07-01', 'end' => ''],
    ];
    foreach ($seed as $s) {
        $id = wp_insert_post([
            'post_type'   => 'sjioc_vicar_history',
            'post_title'  => $s['name'],
            'post_status' => 'publish',
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'vicar_period_start', $s['start']);
            update_post_meta($id, 'vicar_period_end',   $s['end']);
        }
    }
    update_option('sjioc_vicar_history_seeded', 1);
});

/* ─────────────────────────────────────
   Frontend — fetch all Vicars ordered chronologically (oldest first)
───────────────────────────────────── */
function sjioc_get_vicar_history() {
    $posts = get_posts([
        'post_type'      => 'sjioc_vicar_history',
        'posts_per_page' => -1,
        'meta_key'       => 'vicar_period_start',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]);
    $rows = [];
    foreach ($posts as $p) {
        $start = get_post_meta($p->ID, 'vicar_period_start', true);
        $end   = get_post_meta($p->ID, 'vicar_period_end',   true);
        $rows[] = [
            'name'    => $p->post_title,
            'photo'   => get_the_post_thumbnail_url($p->ID, 'medium') ?: '',
            'start'   => $start ? date_i18n('M. j, Y', strtotime($start)) : '',
            'end'     => $end   ? date_i18n('M. j, Y', strtotime($end))   : '',
            'current' => !$end,
        ];
    }
    return $rows;
}

/* ─────────────────────────────────────
   Shared render — used by both Our History and About Us so the markup
   only ever lives in one place.
───────────────────────────────────── */
function sjioc_render_vicar_history_timeline($rows) {
    if (!$rows) return;
    ?>
    <div class="vt-timeline">
        <?php foreach ($rows as $v): ?>
        <div class="vt-row<?php echo $v['current'] ? ' is-current' : ''; ?>">
            <div class="vt-photo">
                <?php if ($v['photo']): ?>
                <img src="<?php echo esc_url($v['photo']); ?>" alt="<?php echo esc_attr($v['name']); ?>" loading="lazy">
                <?php else: ?>
                <span class="vt-photo-fallback" aria-hidden="true">✟</span>
                <?php endif; ?>
            </div>
            <div class="vt-info">
                <span class="vt-period"><?php echo esc_html($v['start']); ?> &ndash; <?php echo $v['current'] ? 'Present' : esc_html($v['end']); ?></span>
                <span class="vt-name"><?php echo esc_html($v['name']); ?></span>
                <?php if ($v['current']): ?><span class="vt-current-badge">Current Vicar</span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}
