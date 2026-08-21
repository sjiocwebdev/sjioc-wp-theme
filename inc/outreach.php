<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Outreach Programs
   Same pattern as Ministries (title/editor/thumbnail + tag + activities +
   order), minus leadership roles and gallery link — kept simpler per request.
───────────────────────────────────── */
function sjioc_register_outreach() {
    register_post_type('sjioc_outreach', [
        'labels'       => [
            'name'          => __('Outreach Programs',       'sjioc'),
            'singular_name' => __('Outreach Program',         'sjioc'),
            'add_new_item'  => __('Add New Outreach Program', 'sjioc'),
            'edit_item'     => __('Edit Outreach Program',    'sjioc'),
            'menu_name'     => __('Outreach',                 'sjioc'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'sjioc',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'sjioc_register_outreach');

/* ─────────────────────────────────────
   META BOX: Outreach Program Details
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'sjioc_outreach_details',
        'Outreach Program Details',
        'sjioc_outreach_meta_box_html',
        'sjioc_outreach',
        'normal',
        'high'
    );
});

function sjioc_outreach_meta_box_html($post) {
    wp_nonce_field('sjioc_outreach_save', 'sjioc_outreach_nonce');
    $tag        = get_post_meta($post->ID, 'outreach_tag',        true);
    $activities = get_post_meta($post->ID, 'outreach_activities', true);
    $order      = get_post_meta($post->ID, 'outreach_order',      true);
    ?>
    <style>
        #sjioc-omb td { padding:6px 0; }
        #sjioc-omb input[type=text], #sjioc-omb textarea { width:100%; max-width:480px; }
        .sjioc-omb-sep { margin-top:6px; padding:8px 0 2px; border-top:1px solid #e0e0e0;
            font-weight:600; color:#555; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
    </style>
    <table class="form-table" id="sjioc-omb">
        <tr>
            <th><label for="outreach_tag">Category Tag</label></th>
            <td>
                <input type="text" id="outreach_tag" name="outreach_tag"
                    value="<?php echo esc_attr($tag); ?>"
                    placeholder="e.g. Food Security, Community Garden, Youth">
                <p class="description">Short label shown on the card.</p>
            </td>
        </tr>
        <tr>
            <th><label for="outreach_order">Display Order</label></th>
            <td>
                <input type="number" id="outreach_order" name="outreach_order"
                    value="<?php echo esc_attr($order ?: 10); ?>"
                    min="1" max="99" style="width:70px">
                <span style="color:#666;margin-left:6px;font-style:italic">Lower = appears first</span>
            </td>
        </tr>
        <tr><td colspan="2"><p class="sjioc-omb-sep">Introduction</p></td></tr>
        <tr><td colspan="2">
            <p class="description">Use the <strong>main content editor above</strong> for the introduction shown in the popup.</p>
        </td></tr>
        <tr><td colspan="2"><p class="sjioc-omb-sep">Activities</p></td></tr>
        <tr>
            <th><label for="outreach_activities">Activities / Programs</label></th>
            <td><textarea id="outreach_activities" name="outreach_activities" rows="4"
                placeholder="List the key activities or how this program runs…"><?php echo esc_textarea($activities); ?></textarea></td>
        </tr>
    </table>
    <p style="margin-top:12px;color:#555">
        <strong>Cover Photo:</strong> Use <em>Featured Image</em> (right sidebar) for the card image.
    </p>
    <?php
}

add_action('save_post_sjioc_outreach', function ($post_id) {
    if (!isset($_POST['sjioc_outreach_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_outreach_nonce'], 'sjioc_outreach_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'outreach_tag',        sanitize_text_field($_POST['outreach_tag']        ?? ''));
    update_post_meta($post_id, 'outreach_activities', sanitize_textarea_field($_POST['outreach_activities'] ?? ''));
    update_post_meta($post_id, 'outreach_order',      absint($_POST['outreach_order'] ?? 10));
});

/* ─────────────────────────────────────
   Seed the 3 programs mentioned when this feature was requested, so the
   page isn't empty on first load. Admins can add more or edit these freely.
───────────────────────────────────── */
function sjioc_seed_outreach_programs() {
    if (get_option('sjioc_outreach_seeded')) return;

    $programs = [
        [
            'title'      => 'Philabundance',
            'tag'        => 'Food Security',
            'content'    => '<p>Content coming soon — edit this in WP Admin → Outreach.</p>',
            'activities' => '',
            'order'      => 10,
        ],
        [
            'title'      => "Grow Murphy's Market",
            'tag'        => 'Community Garden',
            'content'    => '<p>Content coming soon — edit this in WP Admin → Outreach.</p>',
            'activities' => '',
            'order'      => 20,
        ],
        [
            'title'      => 'Sunday School Cards',
            'tag'        => 'Youth',
            'content'    => '<p>Content coming soon — edit this in WP Admin → Outreach.</p>',
            'activities' => '',
            'order'      => 30,
        ],
    ];

    foreach ($programs as $p) {
        $id = wp_insert_post([
            'post_type'    => 'sjioc_outreach',
            'post_status'  => 'publish',
            'post_title'   => $p['title'],
            'post_content' => $p['content'],
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'outreach_tag',        $p['tag']);
            update_post_meta($id, 'outreach_activities', $p['activities']);
            update_post_meta($id, 'outreach_order',      $p['order']);
        }
    }

    update_option('sjioc_outreach_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_outreach_programs');
