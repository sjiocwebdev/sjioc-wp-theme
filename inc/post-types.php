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
        'has_archive'   => false,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 5,
        'supports'      => ['title','editor','thumbnail','excerpt','custom-fields'],
        'rewrite'       => ['slug' => 'event', 'with_front' => false],
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
        'show_in_menu'  => 'sjioc',
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

/* ─────────────────────────────────────
   CPT: Flash News / Announcements
───────────────────────────────────── */
function sjioc_register_announcements() {
    register_post_type('sjioc_announcement', [
        'labels'       => [
            'name'          => __('Flash News',       'sjioc'),
            'singular_name' => __('Announcement',     'sjioc'),
            'add_new_item'  => __('Add Announcement', 'sjioc'),
            'edit_item'     => __('Edit Announcement','sjioc'),
            'menu_name'     => __('Flash News',       'sjioc'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'sjioc',
        'supports'     => ['title', 'thumbnail'],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'sjioc_register_announcements');

add_action('add_meta_boxes', function () {
    add_meta_box(
        'sjioc_announcement_details',
        'Announcement Details',
        'sjioc_announcement_meta_box_html',
        'sjioc_announcement',
        'normal',
        'high'
    );
});

function sjioc_announcement_meta_box_html($post) {
    wp_nonce_field('sjioc_announcement_save', 'sjioc_announcement_nonce');
    $type    = get_post_meta($post->ID, 'ann_type',    true) ?: 'info';
    $start   = get_post_meta($post->ID, 'ann_start',   true);
    $expiry  = get_post_meta($post->ID, 'ann_expiry',  true);
    $link    = get_post_meta($post->ID, 'ann_link',    true);
    $message = get_post_meta($post->ID, 'ann_message', true);
    $cards   = json_decode(get_post_meta($post->ID, 'ann_support_cards', true) ?: '[]', true) ?: [];
    $types   = [
        'info'   => '🔵 General Info (Sticky Ribbon)',
        'urgent' => '🔴 Urgent Notice (Card Grid)',
        'sad'    => '🕯️ Sad News / Condolences (Card Grid)',
        'rental' => '🏛️ Hall / Facility (Sticky Ribbon)',
        'event'  => '📅 Event (Sticky Ribbon)',
    ];
    ?>
    <style>
        #sjioc-anb td { padding:6px 0; }
        #sjioc-anb input[type=text],#sjioc-anb input[type=url],#sjioc-anb input[type=date],
        #sjioc-anb select,#sjioc-anb textarea { width:100%; max-width:480px; }
        .ann-sep { margin-top:6px; padding:8px 0 2px; border-top:1px solid #e0e0e0;
            font-weight:600; color:#555; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        .ann-card-row { display:flex; gap:8px; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap; }
        .ann-card-row input { flex:1; min-width:120px; }
        .ann-card-row textarea { flex:2; min-width:180px; height:54px; resize:vertical; }
        #ann-sad-urgent-section { display:none; margin-top:4px; }
    </style>
    <table class="form-table" id="sjioc-anb">
        <tr>
            <th><label for="ann_type">Type</label></th>
            <td>
                <select name="ann_type" id="ann_type" onchange="annToggle(this.value)">
                    <?php foreach ($types as $v => $l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($type, $v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><strong>Ribbon types</strong> show a sticky bar that opens a modal. <strong>Card Grid types</strong> show a full featured card + support grid on the page.</p>
            </td>
        </tr>
        <tr>
            <th><label>Body Text</label></th>
            <td>
                <?php wp_editor($message, 'ann_message', [
                    'textarea_name' => 'ann_message',
                    'textarea_rows' => 6,
                    'media_buttons' => false,
                    'teeny'         => false,
                    'quicktags'     => false,
                    'tinymce'       => [
                        'toolbar1' => 'bold,italic,underline,bullist,numlist,indent,outdent,link,removeformat',
                        'toolbar2' => '',
                    ],
                ]); ?>
                <p class="description">Shown in the modal (ribbon types) or on the featured card (card grid types). Leave blank if not needed.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ann_link">Button Link (optional)</label></th>
            <td>
                <input type="url" name="ann_link" id="ann_link"
                    value="<?php echo esc_attr($link); ?>" placeholder="https://…">
                <p class="description">Adds a "Learn More" button. Leave blank if not needed.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ann_start">Show From</label></th>
            <td><input type="date" name="ann_start" id="ann_start" value="<?php echo esc_attr($start); ?>">
                <p class="description">Leave blank to show immediately.</p></td>
        </tr>
        <tr>
            <th><label for="ann_expiry">Hide After</label></th>
            <td><input type="date" name="ann_expiry" id="ann_expiry" value="<?php echo esc_attr($expiry); ?>">
                <p class="description">Leave blank to show indefinitely.</p></td>
        </tr>
        <tr id="ann-sad-urgent-section">
            <td colspan="2">
                <p class="ann-sep">Support Cards (Card Grid only — all fields optional)</p>
                <p class="description" style="margin-bottom:10px">Add up to 4 supporting cards (e.g. Prayer Details, Funeral Info, Family Message). Leave blank to skip.</p>
                <div id="ann-cards-wrap">
                    <?php foreach ($cards as $c): ?>
                    <div class="ann-card-row">
                        <input type="text"     name="ann_card_title[]" value="<?php echo esc_attr($c['title'] ?? ''); ?>" placeholder="Card title (e.g. Prayer Details)">
                        <textarea             name="ann_card_desc[]"  placeholder="Short description…"><?php echo esc_textarea($c['desc'] ?? ''); ?></textarea>
                        <input type="url"      name="ann_card_link[]"  value="<?php echo esc_attr($c['link'] ?? ''); ?>" placeholder="Link (optional)">
                        <button type="button" class="button" onclick="this.closest('.ann-card-row').remove()">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="ann-add-card" style="margin-top:4px">+ Add Support Card</button>
            </td>
        </tr>
    </table>
    <p style="margin-top:12px;color:#555">
        <strong>Headline:</strong> use the <em>Title</em> field above — one concise sentence.<br>
        <strong>Photo (Card Grid only):</strong> set via <em>Featured Image</em> in the right sidebar. If none, a solid colour background is used.
    </p>
    <script>
    function annToggle(v) {
        var su = document.getElementById('ann-sad-urgent-section');
        su.style.display = (v === 'urgent' || v === 'sad') ? '' : 'none';
    }
    annToggle(document.getElementById('ann_type').value);
    document.getElementById('ann-add-card').addEventListener('click', function() {
        var wrap = document.getElementById('ann-cards-wrap');
        if (wrap.querySelectorAll('.ann-card-row').length >= 4) return;
        var row = document.createElement('div');
        row.className = 'ann-card-row';
        row.innerHTML = '<input type="text" name="ann_card_title[]" placeholder="Card title">'
            + '<textarea name="ann_card_desc[]" placeholder="Short description…"></textarea>'
            + '<input type="url" name="ann_card_link[]" placeholder="Link (optional)">'
            + '<button type="button" class="button" onclick="this.closest(\'.ann-card-row\').remove()">✕</button>';
        wrap.appendChild(row);
        row.querySelector('input').focus();
    });
    </script>
    <?php
}

add_action('save_post_sjioc_announcement', function ($post_id) {
    if (!isset($_POST['sjioc_announcement_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_announcement_nonce'], 'sjioc_announcement_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $allowed = ['info', 'urgent', 'sad', 'rental', 'event'];
    $type    = in_array($_POST['ann_type'] ?? '', $allowed, true) ? $_POST['ann_type'] : 'info';
    update_post_meta($post_id, 'ann_type',    $type);
    update_post_meta($post_id, 'ann_message', wp_kses_post(wp_unslash($_POST['ann_message'] ?? '')));
    update_post_meta($post_id, 'ann_start',   sanitize_text_field($_POST['ann_start']  ?? ''));
    update_post_meta($post_id, 'ann_expiry',  sanitize_text_field($_POST['ann_expiry'] ?? ''));
    update_post_meta($post_id, 'ann_link',    esc_url_raw($_POST['ann_link'] ?? ''));

    $titles = array_map('sanitize_text_field',    (array)($_POST['ann_card_title'] ?? []));
    $descs  = array_map('sanitize_textarea_field', (array)($_POST['ann_card_desc']  ?? []));
    $links  = array_map('esc_url_raw',             (array)($_POST['ann_card_link']  ?? []));
    $cards  = [];
    for ($i = 0; $i < min(4, count($titles)); $i++) {
        if ($titles[$i] !== '' || $descs[$i] !== '') {
            $cards[] = ['title' => $titles[$i], 'desc' => $descs[$i], 'link' => $links[$i] ?? ''];
        }
    }
    update_post_meta($post_id, 'ann_support_cards', wp_json_encode($cards));
});

/* ─────────────────────────────────────
   CPT: Ministries
───────────────────────────────────── */
function sjioc_register_ministries() {
    register_post_type('sjioc_ministry', [
        'labels'       => [
            'name'          => __('Ministries',       'sjioc'),
            'singular_name' => __('Ministry',         'sjioc'),
            'add_new_item'  => __('Add New Ministry', 'sjioc'),
            'edit_item'     => __('Edit Ministry',    'sjioc'),
            'menu_name'     => __('Ministries',       'sjioc'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'sjioc',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'sjioc_register_ministries');

/* ─────────────────────────────────────
   META BOX: Ministry Details
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'sjioc_ministry_details',
        'Ministry Details',
        'sjioc_ministry_meta_box_html',
        'sjioc_ministry',
        'normal',
        'high'
    );
});

function sjioc_ministry_meta_box_html($post) {
    wp_nonce_field('sjioc_ministry_save', 'sjioc_ministry_nonce');
    $tag        = get_post_meta($post->ID, 'ministry_tag',        true);
    $activities = get_post_meta($post->ID, 'ministry_activities', true);
    $album_cat  = get_post_meta($post->ID, 'ministry_album_cat',  true);
    $album_name = get_post_meta($post->ID, 'ministry_album_name', true);
    $order      = get_post_meta($post->ID, 'ministry_order',      true);
    $roles_raw  = get_post_meta($post->ID, 'ministry_roles',      true) ?: '[]';
    $roles      = json_decode($roles_raw, true) ?: [];
    ?>
    <style>
        #sjioc-mmb td { padding:6px 0; }
        #sjioc-mmb input[type=text], #sjioc-mmb textarea, #sjioc-mmb select { width:100%; max-width:480px; }
        .sjioc-mmb-sep { margin-top:6px; padding:8px 0 2px; border-top:1px solid #e0e0e0;
            font-weight:600; color:#555; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        .min-role-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
        .min-role-row .role-title { flex:0 0 180px; }
        .min-role-row .role-name  { flex:1; }
        .min-role-row .remove-role { flex:0 0 auto; }
    </style>
    <table class="form-table" id="sjioc-mmb">
        <tr>
            <th><label for="ministry_tag">Category Tag</label></th>
            <td>
                <input type="text" id="ministry_tag" name="ministry_tag"
                    value="<?php echo esc_attr($tag); ?>"
                    placeholder="e.g. Active Ministry, Education, Liturgical, Fellowship">
                <p class="description">Short label shown on the card.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ministry_order">Display Order</label></th>
            <td>
                <input type="number" id="ministry_order" name="ministry_order"
                    value="<?php echo esc_attr($order ?: 10); ?>"
                    min="1" max="99" style="width:70px">
                <span style="color:#666;margin-left:6px;font-style:italic">Lower = appears first</span>
            </td>
        </tr>
        <tr><td colspan="2"><p class="sjioc-mmb-sep">Introduction</p></td></tr>
        <tr><td colspan="2">
            <p class="description">Use the <strong>main content editor above</strong> for the ministry introduction shown in the popup.</p>
        </td></tr>
        <tr><td colspan="2"><p class="sjioc-mmb-sep">Activities</p></td></tr>
        <tr>
            <th><label for="ministry_activities">Activities / Programs</label></th>
            <td><textarea id="ministry_activities" name="ministry_activities" rows="4"
                placeholder="List the key activities, programs, and events this ministry runs…"><?php echo esc_textarea($activities); ?></textarea></td>
        </tr>
        <tr><td colspan="2"><p class="sjioc-mmb-sep">Leadership Roles</p></td></tr>
        <tr>
            <th>Roles</th>
            <td>
                <div id="ministry-roles-wrap">
                    <?php foreach ($roles as $r): ?>
                    <div class="min-role-row">
                        <input type="text" class="role-title" name="ministry_roles_title[]"
                            value="<?php echo esc_attr($r['role'] ?? ''); ?>"
                            placeholder="Role (e.g. Secretary)">
                        <input type="text" class="role-name" name="ministry_roles_name[]"
                            value="<?php echo esc_attr($r['name'] ?? ''); ?>"
                            placeholder="Person's full name">
                        <button type="button" class="button remove-role"
                            onclick="this.closest('.min-role-row').remove()">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="ministry-add-role" style="margin-top:4px">+ Add Role</button>
                <p class="description" style="margin-top:6px">Add any roles: Secretary, Jt. Secretary, Treasurer, Youth Leader, etc.</p>
            </td>
        </tr>
        <tr><td colspan="2"><p class="sjioc-mmb-sep">Parish Life Gallery Link</p></td></tr>
        <tr>
            <th><label for="ministry_album_cat">Album Category</label></th>
            <td>
                <select id="ministry_album_cat" name="ministry_album_cat" style="max-width:220px">
                    <option value="">— No gallery link —</option>
                    <?php foreach (['worship'=>'Worship','events'=>'Events','ministries'=>'Ministries','community'=>'Community'] as $val => $lbl): ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($album_cat, $val); ?>><?php echo esc_html($lbl); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="ministry_album_name">Album Name</label></th>
            <td>
                <input type="text" id="ministry_album_name" name="ministry_album_name"
                    value="<?php echo esc_attr($album_name); ?>"
                    placeholder="Exact album name as it appears in OneDrive (optional)">
                <p class="description">If set, a "View Photos" button appears in the popup.</p>
            </td>
        </tr>
    </table>
    <p style="margin-top:12px;color:#555">
        <strong>Cover Photo:</strong> Use <em>Featured Image</em> (right sidebar) for the ministry card image.
    </p>
    <script>
    document.getElementById('ministry-add-role').addEventListener('click', function () {
        var wrap = document.getElementById('ministry-roles-wrap');
        var row  = document.createElement('div');
        row.className = 'min-role-row';
        row.innerHTML =
            '<input type="text" class="role-title" name="ministry_roles_title[]" placeholder="Role (e.g. Secretary)" style="flex:0 0 180px">'
          + '<input type="text" class="role-name"  name="ministry_roles_name[]"  placeholder="Person\'s full name" style="flex:1;margin-left:8px">'
          + '<button type="button" class="button remove-role" style="margin-left:8px" onclick="this.closest(\'.min-role-row\').remove()">✕</button>';
        wrap.appendChild(row);
        row.querySelector('input').focus();
    });
    </script>
    <?php
}

add_action('save_post_sjioc_ministry', function ($post_id) {
    if (!isset($_POST['sjioc_ministry_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_ministry_nonce'], 'sjioc_ministry_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Build roles array from parallel name/title inputs
    $titles = array_map('sanitize_text_field', (array) ($_POST['ministry_roles_title'] ?? []));
    $names  = array_map('sanitize_text_field', (array) ($_POST['ministry_roles_name']  ?? []));
    $roles  = [];
    $max    = max(count($titles), count($names));
    for ($i = 0; $i < $max; $i++) {
        $t = $titles[$i] ?? '';
        $n = $names[$i]  ?? '';
        if ($t !== '' || $n !== '') {
            $roles[] = ['role' => $t, 'name' => $n];
        }
    }
    update_post_meta($post_id, 'ministry_roles', wp_json_encode($roles));

    update_post_meta($post_id, 'ministry_tag',        sanitize_text_field($_POST['ministry_tag']        ?? ''));
    update_post_meta($post_id, 'ministry_album_name', sanitize_text_field($_POST['ministry_album_name'] ?? ''));
    update_post_meta($post_id, 'ministry_activities', sanitize_textarea_field($_POST['ministry_activities'] ?? ''));
    update_post_meta($post_id, 'ministry_order',      absint($_POST['ministry_order'] ?? 10));
    $allowed_cats = ['', 'worship', 'events', 'ministries', 'community'];
    $cat = in_array($_POST['ministry_album_cat'] ?? '', $allowed_cats, true) ? $_POST['ministry_album_cat'] : '';
    update_post_meta($post_id, 'ministry_album_cat', $cat);
});
