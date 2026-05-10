<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Events
───────────────────────────────────── */
function sjioc_register_events() {
    register_post_type('sjioc_event', [
        'labels'        => [
            'name'          => __('Events',        'sjioc'),
            'singular_name' => __('Event',         'sjioc'),
            'add_new_item'  => __('Add New Event', 'sjioc'),
            'menu_name'     => __('Events',        'sjioc'),
        ],
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 5,
        'supports'      => ['title','editor','thumbnail','excerpt','custom-fields'],
        'rewrite'       => ['slug' => 'events'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'sjioc_register_events');

/* ─────────────────────────────────────
   CPT: Gallery
───────────────────────────────────── */
function sjioc_register_gallery() {
    register_post_type('sjioc_gallery', [
        'labels'        => [
            'name'          => __('Gallery',          'sjioc'),
            'singular_name' => __('Photo',            'sjioc'),
            'add_new_item'  => __('Add New Photo',    'sjioc'),
            'menu_name'     => __('Gallery',          'sjioc'),
        ],
        'public'        => true,
        'menu_icon'     => 'dashicons-format-gallery',
        'menu_position' => 6,
        'supports'      => ['title','thumbnail','excerpt','custom-fields'],
        'rewrite'       => ['slug' => 'gallery'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'sjioc_register_gallery');

/* ─────────────────────────────────────
   CPT: Contacts (Parish Directory)
───────────────────────────────────── */
function sjioc_register_contacts() {
    register_post_type('sjioc_contact', [
        'labels'        => [
            'name'          => __('Parish Directory', 'sjioc'),
            'singular_name' => __('Contact',          'sjioc'),
            'add_new_item'  => __('Add Contact',      'sjioc'),
            'edit_item'     => __('Edit Contact',     'sjioc'),
            'menu_name'     => __('Directory',        'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 7,
        'supports'      => ['title', 'thumbnail'],
        'show_in_rest'  => false,
    ]);
}
add_action('init', 'sjioc_register_contacts');

/* ─────────────────────────────────────
   META BOX: Contact Details
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'sjioc_contact_details',
        'Contact Details',
        'sjioc_contact_meta_box_html',
        'sjioc_contact',
        'normal',
        'high'
    );
});

function sjioc_contact_meta_box_html($post) {
    wp_nonce_field('sjioc_contact_save', 'sjioc_contact_nonce');
    $role   = get_post_meta($post->ID, 'contact_role',   true);
    $order  = get_post_meta($post->ID, 'contact_order',  true);
    $pinned = get_post_meta($post->ID, 'contact_pinned', true);
    $type   = get_post_meta($post->ID, 'contact_type',   true);
    $types  = ['' => 'None (no contact button)', 'vicar' => 'Vicar / Father', 'trustee' => 'Trustee', 'secretary' => 'Secretary'];
    ?>
    <style>
        #sjioc-cmb td { padding:6px 0; }
        #sjioc-cmb input[type=text] { width:100%;max-width:400px; }
        .sjioc-note { color:#666;font-style:italic;margin-left:6px; }
    </style>
    <table class="form-table" id="sjioc-cmb">
        <tr>
            <th><label for="contact_role">Role / Position</label></th>
            <td><input type="text" id="contact_role" name="contact_role"
                value="<?php echo esc_attr($role); ?>"
                placeholder="e.g. Vicar, Secretary, Trustee, Youth Leader"></td>
        </tr>
        <tr>
            <th><label for="contact_type">Contact Button</label></th>
            <td>
                <select id="contact_type" name="contact_type">
                    <?php foreach ($types as $val => $label): ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($type, $val); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <span class="sjioc-note">Shows a contact button linking to the Contact Us page</span>
            </td>
        </tr>
        <tr>
            <th><label for="contact_order">Display Order</label></th>
            <td>
                <input type="number" id="contact_order" name="contact_order"
                    value="<?php echo esc_attr($order ?: 10); ?>"
                    min="1" max="99" style="width:70px">
                <span class="sjioc-note">Lower = appears first &nbsp;(1 = Vicar at top)</span>
            </td>
        </tr>
        <tr>
            <th><label for="contact_pinned">Pin to Top</label></th>
            <td>
                <input type="checkbox" id="contact_pinned" name="contact_pinned"
                    value="1" <?php checked($pinned, '1'); ?>>
                <label for="contact_pinned">Always show first with gold highlight — use for Parish Vicar</label>
            </td>
        </tr>
    </table>
    <p style="margin-top:12px;color:#555">
        <strong>Photo:</strong> Use <em>Featured Image</em> (right sidebar) to set the person's thumbnail.
    </p>
    <?php
}

add_action('save_post_sjioc_contact', function ($post_id) {
    if (!isset($_POST['sjioc_contact_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_contact_nonce'], 'sjioc_contact_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'contact_role',   sanitize_text_field($_POST['contact_role']  ?? ''));
    update_post_meta($post_id, 'contact_order',  absint($_POST['contact_order']  ?? 10));
    update_post_meta($post_id, 'contact_pinned', isset($_POST['contact_pinned']) ? '1' : '0');
    $allowed_types = ['', 'vicar', 'trustee', 'secretary'];
    $type = in_array($_POST['contact_type'] ?? '', $allowed_types, true) ? $_POST['contact_type'] : '';
    update_post_meta($post_id, 'contact_type', $type);
});

/* ─────────────────────────────────────
   CPT: Celebrations
───────────────────────────────────── */
function sjioc_register_celebrations() {
    register_post_type('sjioc_celeb', [
        'labels'        => [
            'name'          => __('Birthdays & Anniversaries', 'sjioc'),
            'singular_name' => __('Celebration',               'sjioc'),
            'add_new_item'  => __('Add Celebration',           'sjioc'),
            'menu_name'     => __('Celebrations',              'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-heart',
        'menu_position' => 8,
        'supports'      => ['title','custom-fields'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'sjioc_register_celebrations');
