<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Office Bearers  (Leadership + Committees / Organizations)
   Drives the Leadership and "Committees & Organizations" sections
   of the About Us page. People are grouped via the sjioc_office_group
   taxonomy; each group renders into a fixed spot per sjioc_office_config().
───────────────────────────────────── */
function sjioc_register_office() {
    register_post_type('sjioc_office', [
        'labels'        => [
            'name'          => __('Office Bearers',      'sjioc'),
            'singular_name' => __('Office Bearer',       'sjioc'),
            'add_new_item'  => __('Add Office Bearer',   'sjioc'),
            'edit_item'     => __('Edit Office Bearer',  'sjioc'),
            'menu_name'     => __('Office Bearers',      'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sjioc',
        'menu_icon'     => 'dashicons-groups',
        'supports'      => ['title', 'thumbnail'],
        'show_in_rest'  => false,
    ]);

    register_taxonomy('sjioc_office_group', 'sjioc_office', [
        'labels'            => [
            'name'          => __('Groups',        'sjioc'),
            'singular_name' => __('Group',         'sjioc'),
            'menu_name'     => __('Groups',        'sjioc'),
            'add_new_item'  => __('Add New Group', 'sjioc'),
        ],
        'public'            => false,
        'show_ui'           => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => false,
    ]);
}
add_action('init', 'sjioc_register_office');

/* ─────────────────────────────────────
   Group configuration — order here = render order on the page.
   section: leadership | committee   tab: admin | spiritual (committee only)
   style:   card (photo+bio) | pill | grid (names only) | roles (role → name)
───────────────────────────────────── */
function sjioc_office_config() {
    return [
        'tabs'   => [
            'admin'     => __('Parish Administration',  'sjioc'),
            'spiritual' => __('Spiritual Organizations','sjioc'),
        ],
        'groups' => [
            'leadership'          => ['label' => 'Leadership',           'section' => 'leadership', 'style' => 'card'],
            'joint-bearers'       => ['label' => 'Joint Office Bearers', 'section' => 'leadership', 'style' => 'pill'],
            'managing-committee'  => ['label' => 'Managing Committee',   'section' => 'committee', 'tab' => 'admin',     'style' => 'grid'],
            'auditors'            => ['label' => 'Auditors',             'section' => 'committee', 'tab' => 'admin',     'style' => 'grid'],
            'association-members' => ['label' => 'Association Members',   'section' => 'committee', 'tab' => 'admin',     'style' => 'roles'],
            'event-coordinators'  => ['label' => 'Event Coordinators',   'section' => 'committee', 'tab' => 'admin',     'style' => 'roles'],
            'ecumenical-members'  => ['label' => 'Ecumenical Members',   'section' => 'committee', 'tab' => 'admin',     'style' => 'grid'],
            'sunday-school'       => ['label' => 'Sunday School',        'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
            'mmvs'                => ['label' => 'MMVS',                  'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
            'mgocsm'              => ['label' => 'MGOCSM',                'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
            'focus'               => ['label' => 'FOCUS',                'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
            'grow'                => ['label' => 'GROW',                 'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
            'mens-forum'          => ['label' => "Men's Forum",          'section' => 'committee', 'tab' => 'spiritual', 'style' => 'roles'],
        ],
    ];
}

/* ─────────────────────────────────────
   Seed the default group terms once (admin only) so they're ready to fill.
───────────────────────────────────── */
function sjioc_seed_office_terms() {
    if (get_option('sjioc_office_seeded')) return;
    foreach (sjioc_office_config()['groups'] as $slug => $g) {
        if (!term_exists($slug, 'sjioc_office_group')) {
            wp_insert_term($g['label'], 'sjioc_office_group', ['slug' => $slug]);
        }
    }
    update_option('sjioc_office_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_office_terms');

/* ─────────────────────────────────────
   META BOX: Office Bearer details (role + bio + order)
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box('sjioc_office_details', 'Office Bearer Details', 'sjioc_office_meta_box_html', 'sjioc_office', 'normal', 'high');
});

function sjioc_office_meta_box_html($post) {
    wp_nonce_field('sjioc_office_save', 'sjioc_office_nonce');
    $role  = get_post_meta($post->ID, 'office_role',  true);
    $bio   = get_post_meta($post->ID, 'office_bio',   true);
    $order = get_post_meta($post->ID, 'office_order', true);
    ?>
    <style>
        #sjioc-omb td { padding:6px 0; }
        #sjioc-omb input[type=text], #sjioc-omb textarea { width:100%; max-width:480px; }
        .sjioc-note { color:#666; font-style:italic; margin-left:6px; }
    </style>
    <table class="form-table" id="sjioc-omb">
        <tr>
            <th><label for="office_role">Role / Position</label></th>
            <td><input type="text" id="office_role" name="office_role"
                value="<?php echo esc_attr($role); ?>"
                placeholder="e.g. Vicar, Trustee, Secretary, Treasurer">
                <span class="sjioc-note">Leave blank for name-only lists (e.g. Managing Committee)</span></td>
        </tr>
        <tr>
            <th><label for="office_bio">Short Bio</label></th>
            <td><textarea id="office_bio" name="office_bio" rows="3"
                placeholder="Shown only for Leadership cards"><?php echo esc_textarea($bio); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="office_order">Display Order</label></th>
            <td><input type="number" id="office_order" name="office_order"
                value="<?php echo esc_attr($order !== '' ? $order : 10); ?>" min="1" max="99" style="width:70px">
                <span class="sjioc-note">Lower = appears first within its group</span></td>
        </tr>
    </table>
    <p style="margin-top:12px;color:#555">
        <strong>Group:</strong> assign this person to a group in the <em>Groups</em> box (right sidebar).<br>
        <strong>Photo:</strong> use <em>Featured Image</em> (right sidebar) — used by Leadership cards only.
    </p>
    <?php
}

add_action('save_post_sjioc_office', function ($post_id) {
    if (!isset($_POST['sjioc_office_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_office_nonce'], 'sjioc_office_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'office_role',  sanitize_text_field($_POST['office_role'] ?? ''));
    update_post_meta($post_id, 'office_bio',   sanitize_textarea_field($_POST['office_bio'] ?? ''));
    update_post_meta($post_id, 'office_order', absint($_POST['office_order'] ?? 10));
});

/* ─────────────────────────────────────
   Data access — one structured, transient-cached read for the whole page.
   Shape: [ group_slug => ['name' => term label, 'people' => [ [name,role,bio,photo,order], ... ] ] ]
───────────────────────────────────── */
function sjioc_get_office_data() {
    $cached = get_transient('sjioc_office_data');
    if ($cached !== false) return $cached;

    $posts = get_posts([
        'post_type'      => 'sjioc_office',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    $data = [];
    if ($posts) {
        update_object_term_cache(wp_list_pluck($posts, 'ID'), 'sjioc_office');
        foreach ($posts as $p) {
            $terms = get_the_terms($p->ID, 'sjioc_office_group');
            if (!$terms || is_wp_error($terms)) continue;
            $term = $terms[0];
            if (!isset($data[$term->slug])) {
                $data[$term->slug] = ['name' => $term->name, 'people' => []];
            }
            $data[$term->slug]['people'][] = [
                'name'  => $p->post_title,
                'role'  => (string) get_post_meta($p->ID, 'office_role', true),
                'bio'   => (string) get_post_meta($p->ID, 'office_bio',  true),
                'photo' => get_the_post_thumbnail_url($p->ID, 'sjioc-square') ?: '',
                'order' => (int) (get_post_meta($p->ID, 'office_order', true) ?: 10),
            ];
        }
        foreach ($data as &$g) {
            usort($g['people'], function ($a, $b) {
                return ($a['order'] <=> $b['order']) ?: strcmp($a['name'], $b['name']);
            });
        }
        unset($g);
    }

    set_transient('sjioc_office_data', $data, DAY_IN_SECONDS);
    return $data;
}

function sjioc_flush_office_cache() {
    delete_transient('sjioc_office_data');
}
add_action('save_post_sjioc_office',      'sjioc_flush_office_cache');
add_action('deleted_post',                'sjioc_flush_office_cache');
add_action('created_sjioc_office_group',  'sjioc_flush_office_cache');
add_action('edited_sjioc_office_group',   'sjioc_flush_office_cache');
add_action('delete_sjioc_office_group',   'sjioc_flush_office_cache');

/* ─────────────────────────────────────
   Renderers — echo the dynamic markup for the About page.
   Return false (and echo nothing) when a section has no data, so the
   template can fall back to its built-in static content.
───────────────────────────────────── */
function sjioc_render_office_leadership() {
    $data    = sjioc_get_office_data();
    $leaders = $data['leadership']['people']    ?? [];
    $joint   = $data['joint-bearers']['people'] ?? [];
    if (!$leaders && !$joint) return false;

    if ($leaders) {
        echo '<div class="leadership-grid">';
        foreach ($leaders as $p) {
            $img = $p['photo'] ?: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=250&q=70';
            echo '<div class="leader-card">';
            echo '<img class="leader-avatar" src="' . esc_url($img) . '" alt="' . esc_attr($p['name']) . '" loading="lazy">';
            echo '<h3>' . esc_html($p['name']) . '</h3>';
            if ($p['role']) echo '<span class="leader-role">' . esc_html($p['role']) . '</span>';
            if ($p['bio'])  echo '<p>' . nl2br(esc_html($p['bio'])) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }

    if ($joint) {
        echo '<div class="jt-bearers">';
        foreach ($joint as $p) {
            echo '<div class="jt-pill">';
            if ($p['role']) echo '<span class="jt-role">' . esc_html($p['role']) . '</span>';
            echo '<span class="jt-name">' . esc_html($p['name']) . '</span></div>';
        }
        echo '</div>';
    }
    return true;
}

function sjioc_render_office_committees() {
    $data = sjioc_get_office_data();
    $cfg  = sjioc_office_config();

    // Bucket populated committee groups by tab, preserving config order.
    $by_tab = [];
    foreach ($cfg['groups'] as $slug => $g) {
        if (($g['section'] ?? '') !== 'committee') continue;
        $people = $data[$slug]['people'] ?? [];
        if (!$people) continue;
        $by_tab[$g['tab']][] = [
            'label'  => $data[$slug]['name'] ?? $g['label'],
            'style'  => $g['style'],
            'people' => $people,
        ];
    }
    if (!$by_tab) return false;

    // Tab bar (only tabs that have content).
    echo '<div class="cmte-tabs" role="tablist">';
    $first = true;
    foreach ($cfg['tabs'] as $key => $label) {
        if (empty($by_tab[$key])) continue;
        echo '<button class="cmte-tab' . ($first ? ' is-active' : '') . '" role="tab" data-panel="cmte-' . esc_attr($key) . '">' . esc_html($label) . '</button>';
        $first = false;
    }
    echo '</div>';

    // Panels.
    $first_panel = true;
    foreach ($cfg['tabs'] as $key => $label) {
        if (empty($by_tab[$key])) continue;
        echo '<div class="cmte-panel" id="cmte-' . esc_attr($key) . '"' . ($first_panel ? '' : ' style="display:none"') . '>';
        $first_item = true;
        foreach ($by_tab[$key] as $grp) {
            $open = $first_item;
            echo '<div class="acc-item' . ($open ? ' is-open' : '') . '">';
            echo '<button class="acc-header" aria-expanded="' . ($open ? 'true' : 'false') . '"><span>' . esc_html($grp['label']) . '</span><span class="acc-chevron">&#8964;</span></button>';
            echo '<div class="acc-body"' . ($open ? '' : ' style="display:none"') . '>';
            if ($grp['style'] === 'grid') {
                echo '<ul class="acc-grid">';
                foreach ($grp['people'] as $p) echo '<li>' . esc_html($p['name']) . '</li>';
                echo '</ul>';
            } else {
                echo '<ul class="acc-role-list">';
                foreach ($grp['people'] as $p) {
                    echo '<li>';
                    if ($p['role']) echo '<span class="acc-role">' . esc_html($p['role']) . '</span>';
                    echo '<span class="acc-name">' . esc_html($p['name']) . '</span></li>';
                }
                echo '</ul>';
            }
            echo '</div></div>';
            $first_item = false;
        }
        echo '</div>';
        $first_panel = false;
    }
    return true;
}