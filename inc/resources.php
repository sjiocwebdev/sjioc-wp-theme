<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Resources — external links (Diocese sites, sister parishes,
   important organizations, etc.) shown on the Resources page. Each
   card's icon is auto-pulled from the linked site's own favicon, so
   the admin never has to upload an image — just title, a short
   description, the URL, and an optional label/tag.
───────────────────────────────────── */
function sjioc_register_resources() {
    register_post_type('sjioc_resource', [
        'labels'       => [
            'name'          => __('Resources',        'sjioc'),
            'singular_name' => __('Resource',          'sjioc'),
            'add_new_item'  => __('Add New Resource',  'sjioc'),
            'edit_item'     => __('Edit Resource',     'sjioc'),
            'menu_name'     => __('Resources',         'sjioc'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'sjioc',
        'menu_icon'    => 'dashicons-admin-links',
        'supports'     => ['title', 'editor'],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'sjioc_register_resources');

add_action('add_meta_boxes', function () {
    add_meta_box('sjioc_resource_details', 'Resource Link Details', 'sjioc_resource_meta_box_html', 'sjioc_resource', 'normal', 'high');
});

function sjioc_resource_meta_box_html($post) {
    wp_nonce_field('sjioc_resource_save', 'sjioc_resource_nonce');
    $url   = get_post_meta($post->ID, 'resource_url',   true);
    $tag   = get_post_meta($post->ID, 'resource_tag',   true);
    $order = get_post_meta($post->ID, 'resource_order', true) ?: 10;
    ?>
    <p class="description">Use the <strong>main content editor above</strong> for the short description shown on the card.</p>
    <table class="form-table">
        <tr>
            <th><label for="resource_url">Website URL</label></th>
            <td><input type="url" id="resource_url" name="resource_url" style="width:100%" value="<?php echo esc_attr($url); ?>" placeholder="https://example.org" required></td>
        </tr>
        <tr>
            <th><label for="resource_tag">Label (optional)</label></th>
            <td><input type="text" id="resource_tag" name="resource_tag" style="width:100%" value="<?php echo esc_attr($tag); ?>" placeholder="e.g. Diocese, Sister Parish, Organization"></td>
        </tr>
        <tr>
            <th><label for="resource_order">Display Order</label></th>
            <td><input type="number" id="resource_order" name="resource_order" value="<?php echo esc_attr($order); ?>" style="width:80px">
                <span style="color:#666;margin-left:6px;font-style:italic">Lower = appears first</span>
            </td>
        </tr>
    </table>
    <?php
}

add_action('save_post_sjioc_resource', function ($post_id) {
    if (!isset($_POST['sjioc_resource_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_resource_nonce'], 'sjioc_resource_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'resource_url',   esc_url_raw(wp_unslash($_POST['resource_url'] ?? '')));
    update_post_meta($post_id, 'resource_tag',   sanitize_text_field(wp_unslash($_POST['resource_tag'] ?? '')));
    update_post_meta($post_id, 'resource_order', absint($_POST['resource_order'] ?? 10));
});

/* ─────────────────────────────────────
   Frontend — all published resources, ordered by the admin's Order field
───────────────────────────────────── */
function sjioc_get_resources() {
    $posts = get_posts([
        'post_type'      => 'sjioc_resource',
        'posts_per_page' => -1,
        'meta_key'       => 'resource_order',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]);
    $rows = [];
    foreach ($posts as $p) {
        $url = get_post_meta($p->ID, 'resource_url', true);
        if (!$url) continue;
        $host = wp_parse_url($url, PHP_URL_HOST);
        $rows[] = [
            'title'   => $p->post_title,
            'desc'    => wp_trim_words(wp_strip_all_tags($p->post_content), 22, '…'),
            'url'     => $url,
            'tag'     => get_post_meta($p->ID, 'resource_tag', true),
            'favicon' => $host ? 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=64' : '',
        ];
    }
    return $rows;
}
