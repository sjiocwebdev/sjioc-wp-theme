<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: Office Bearers  (Leadership + Committees / Organizations)
   Drives the Leadership and "Committees & Organizations" sections
   of the About Us page. People are grouped via the sjioc_office_group
   taxonomy; each GROUP TERM carries its own section/tab/style/order as
   term meta (set on the group itself in WP Admin → Office Bearers →
   Groups) — adding a brand-new group needs no code change.
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
            'name'               => __('Groups',          'sjioc'),
            'singular_name'      => __('Group',           'sjioc'),
            'menu_name'          => __('Groups',          'sjioc'),
            'add_new_item'       => __('Add New Group',   'sjioc'),
            'parent_item'        => __('Parent Group',    'sjioc'),
            'parent_item_colon'  => __('Parent Group:',   'sjioc'),
            'all_items'          => __('All Groups',      'sjioc'),
            'edit_item'          => __('Edit Group',      'sjioc'),
            'update_item'        => __('Update Group',    'sjioc'),
            'search_items'       => __('Search Groups',   'sjioc'),
            'not_found'          => __('No groups found', 'sjioc'),
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
   Admin menu link — the Groups taxonomy has no menu item by default
   because sjioc_office lives under the shared "SJIOC" top-level menu
   rather than its own. Without this, the full Groups screen (where the
   Section/Tab/Style/Order fields below actually render) is unreachable
   except by typing the URL directly.
───────────────────────────────────── */
add_action('admin_menu', function () {
    add_submenu_page(
        'sjioc',
        __('Office Bearer Groups', 'sjioc'),
        __('Groups', 'sjioc'),
        'manage_categories',
        'edit-tags.php?taxonomy=sjioc_office_group&post_type=sjioc_office'
    );
});

/* ─────────────────────────────────────
   Fixed page structure — the two Committees tabs, and the option lists
   used by the group settings fields. This is layout, not content, so it
   stays in code; everything content-specific (which group goes where)
   is now term meta, editable in WP Admin.
───────────────────────────────────── */
function sjioc_office_tabs() {
    return [
        'admin'     => __('Parish Administration',   'sjioc'),
        'spiritual' => __('Spiritual Organizations',  'sjioc'),
    ];
}

function sjioc_office_sections() {
    return [
        'leadership' => __('Leadership (top of About page)', 'sjioc'),
        'committee'  => __('Committee / Organization (tabbed list)', 'sjioc'),
    ];
}

function sjioc_office_styles() {
    return [
        'grid'  => __('Grid — names only', 'sjioc'),
        'roles' => __('Roles — role → name pairs', 'sjioc'),
        'card'  => __('Card — photo + bio (Leadership only)', 'sjioc'),
        'text'  => __('Text Card — name + role + bio, no photo', 'sjioc'),
        'pill'  => __('Pill — compact role/name chip (Leadership only)', 'sjioc'),
    ];
}

/* ─────────────────────────────────────
   Default groups — used ONLY to seed the initial term list and to
   backfill their settings once, so existing content keeps rendering
   exactly as before this became admin-configurable. Not consulted at
   render time. New groups you add afterward are configured entirely
   via the Groups screen, not here.
───────────────────────────────────── */
function sjioc_office_seed_defaults() {
    return [
        'leadership'          => ['label' => 'Leadership',           'section' => 'leadership', 'style' => 'card',  'tab' => '',          'order' => 10],
        'joint-bearers'       => ['label' => 'Joint Office Bearers', 'section' => 'leadership', 'style' => 'pill',  'tab' => '',          'order' => 20],
        'managing-committee'  => ['label' => 'Managing Committee',   'section' => 'committee',  'style' => 'grid',  'tab' => 'admin',     'order' => 10],
        'auditors'            => ['label' => 'Auditors',             'section' => 'committee',  'style' => 'grid',  'tab' => 'admin',     'order' => 20],
        'association-members' => ['label' => 'Association Members', 'section' => 'committee',  'style' => 'roles', 'tab' => 'admin',     'order' => 30],
        'event-coordinators'  => ['label' => 'Event Coordinators',   'section' => 'committee',  'style' => 'roles', 'tab' => 'admin',     'order' => 40],
        'ecumenical-members'  => ['label' => 'Ecumenical Members',   'section' => 'committee',  'style' => 'grid',  'tab' => 'admin',     'order' => 50],
        'sunday-school'       => ['label' => 'Sunday School',        'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 10],
        'mmvs'                => ['label' => 'MMVS',                 'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 20],
        'mgocsm'              => ['label' => 'MGOCSM',                'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 30],
        'focus'               => ['label' => 'FOCUS',                 'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 40],
        'grow'                => ['label' => 'GROW',                  'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 50],
        'mens-forum'          => ['label' => "Men's Forum",          'section' => 'committee',  'style' => 'roles', 'tab' => 'spiritual', 'order' => 60],
    ];
}

/* ─────────────────────────────────────
   Seed the default group terms once (admin only) so they're ready to fill.
───────────────────────────────────── */
function sjioc_seed_office_terms() {
    if (get_option('sjioc_office_seeded')) return;
    foreach (sjioc_office_seed_defaults() as $slug => $g) {
        if (!term_exists($slug, 'sjioc_office_group')) {
            wp_insert_term($g['label'], 'sjioc_office_group', ['slug' => $slug]);
        }
    }
    update_option('sjioc_office_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_office_terms');

/* ─────────────────────────────────────
   One-time backfill: give the default groups their term meta (section/
   style/tab/order) so they keep rendering exactly as before. Only fills
   in terms that don't have this meta yet — never overwrites an admin's
   own edit, and never touches groups created after this shipped (those
   are configured through the form fields below when they're created).
───────────────────────────────────── */
function sjioc_seed_office_group_meta() {
    if (get_option('sjioc_office_group_meta_seeded')) return;
    foreach (sjioc_office_seed_defaults() as $slug => $g) {
        $term = get_term_by('slug', $slug, 'sjioc_office_group');
        if (!$term || is_wp_error($term)) continue;
        if (get_term_meta($term->term_id, 'office_group_section', true) !== '') continue;
        update_term_meta($term->term_id, 'office_group_section', $g['section']);
        update_term_meta($term->term_id, 'office_group_style',   $g['style']);
        update_term_meta($term->term_id, 'office_group_tab',     $g['tab']);
        update_term_meta($term->term_id, 'office_group_order',   $g['order']);
    }
    update_option('sjioc_office_group_meta_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_office_group_meta');

/* ─────────────────────────────────────
   GROUP SETTINGS FIELDS — Section / Style / Tab / Order, shown on the
   "Add New Group" and "Edit Group" screens (Office Bearers → Groups).
   This is what replaces the old hardcoded per-group config.
───────────────────────────────────── */
function sjioc_office_group_field_rows($term_id = 0) {
    $section = $term_id ? get_term_meta($term_id, 'office_group_section', true) : '';
    $style   = $term_id ? get_term_meta($term_id, 'office_group_style',   true) : '';
    $tab     = $term_id ? get_term_meta($term_id, 'office_group_tab',     true) : '';
    $order   = $term_id ? get_term_meta($term_id, 'office_group_order',  true) : '';
    if ($order === '') $order = 10;
    ?>
    <p><label for="office_group_section"><strong><?php esc_html_e('Where does this group appear?', 'sjioc'); ?></strong></label><br>
    <select name="office_group_section" id="office_group_section">
        <option value=""><?php esc_html_e('— choose —', 'sjioc'); ?></option>
        <?php foreach (sjioc_office_sections() as $val => $label): ?>
        <option value="<?php echo esc_attr($val); ?>" <?php selected($section, $val); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select></p>

    <p><label for="office_group_tab"><strong><?php esc_html_e('Committees tab (ignored for Leadership groups)', 'sjioc'); ?></strong></label><br>
    <select name="office_group_tab" id="office_group_tab">
        <option value=""><?php esc_html_e('— choose —', 'sjioc'); ?></option>
        <?php foreach (sjioc_office_tabs() as $val => $label): ?>
        <option value="<?php echo esc_attr($val); ?>" <?php selected($tab, $val); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select></p>

    <p><label for="office_group_style"><strong><?php esc_html_e('Display style', 'sjioc'); ?></strong></label><br>
    <select name="office_group_style" id="office_group_style">
        <option value=""><?php esc_html_e('— choose —', 'sjioc'); ?></option>
        <?php foreach (sjioc_office_styles() as $val => $label): ?>
        <option value="<?php echo esc_attr($val); ?>" <?php selected($style, $val); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select></p>

    <p><label for="office_group_order"><strong><?php esc_html_e('Display Order', 'sjioc'); ?></strong></label><br>
    <input type="number" name="office_group_order" id="office_group_order" value="<?php echo esc_attr($order); ?>" min="1" max="99" style="width:70px">
    <span style="color:#666;font-style:italic"><?php esc_html_e('Lower = appears first within its tab', 'sjioc'); ?></span></p>
    <?php
}

add_action('sjioc_office_group_add_form_fields', function ($taxonomy) {
    wp_nonce_field('sjioc_office_group_save', 'sjioc_office_group_nonce');
    echo '<div class="form-field">';
    sjioc_office_group_field_rows(0);
    echo '</div>';
});

add_action('sjioc_office_group_edit_form_fields', function ($term, $taxonomy) {
    wp_nonce_field('sjioc_office_group_save', 'sjioc_office_group_nonce');
    ?>
    <tr class="form-field">
        <th scope="row"><?php esc_html_e('Group Settings', 'sjioc'); ?></th>
        <td><?php sjioc_office_group_field_rows($term->term_id); ?></td>
    </tr>
    <?php
}, 10, 2);

function sjioc_save_office_group_fields($term_id) {
    if (!isset($_POST['sjioc_office_group_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_office_group_nonce'], 'sjioc_office_group_save')) return;
    if (!current_user_can('manage_categories')) return;

    $sections = array_keys(sjioc_office_sections());
    $tabs     = array_keys(sjioc_office_tabs());
    $styles   = array_keys(sjioc_office_styles());

    $section = in_array($_POST['office_group_section'] ?? '', $sections, true) ? $_POST['office_group_section'] : '';
    $tab     = in_array($_POST['office_group_tab']     ?? '', $tabs,     true) ? $_POST['office_group_tab']     : '';
    $style   = in_array($_POST['office_group_style']   ?? '', $styles,   true) ? $_POST['office_group_style']   : '';
    $order   = absint($_POST['office_group_order'] ?? 10) ?: 10;

    update_term_meta($term_id, 'office_group_section', $section);
    update_term_meta($term_id, 'office_group_tab',     $tab);
    update_term_meta($term_id, 'office_group_style',   $style);
    update_term_meta($term_id, 'office_group_order',   $order);
}
add_action('created_sjioc_office_group', 'sjioc_save_office_group_fields');
add_action('edited_sjioc_office_group',  'sjioc_save_office_group_fields');

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
        <strong>Group:</strong> assign this person to a group in the <em>Groups</em> box (right sidebar). New groups
        can be created there too — set that group's Section/Tab/Style under Office Bearers → Groups.<br>
        <strong>Photo:</strong> use <em>Featured Image</em> (right sidebar) — used by Leadership cards only.
    </p>
    <?php
}

add_action('save_post_sjioc_office', function ($post_id) {
    if (!isset($_POST['sjioc_office_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_office_nonce'], 'sjioc_office_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'office_role',  sanitize_text_field(wp_unslash($_POST['office_role'] ?? '')));
    update_post_meta($post_id, 'office_bio',   sanitize_textarea_field(wp_unslash($_POST['office_bio'] ?? '')));
    update_post_meta($post_id, 'office_order', absint($_POST['office_order'] ?? 10));
});

/* ─────────────────────────────────────
   Data access — one structured, transient-cached read for the whole page.
   Shape: [ group_slug => ['name','section','style','tab','order','people' => [...]] ]
   Group settings (section/style/tab/order) come from term meta, so a
   newly-created group needs no code change — just fill in its settings
   on the Groups screen and add Office Bearer entries to it.
───────────────────────────────────── */
function sjioc_get_office_data() {
    $cached = get_transient('sjioc_office_data_v2');
    if ($cached !== false) return $cached;

    $data = [];

    $terms = get_terms(['taxonomy' => 'sjioc_office_group', 'hide_empty' => false]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            $order = get_term_meta($t->term_id, 'office_group_order', true);
            $data[$t->slug] = [
                'name'    => $t->name,
                'section' => get_term_meta($t->term_id, 'office_group_section', true) ?: 'committee',
                'style'   => get_term_meta($t->term_id, 'office_group_style',   true) ?: 'grid',
                'tab'     => get_term_meta($t->term_id, 'office_group_tab',     true) ?: 'admin',
                'order'   => $order !== '' ? (int) $order : 10,
                'people'  => [],
            ];
        }
    }

    $posts = get_posts([
        'post_type'      => 'sjioc_office',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);
    if ($posts) {
        update_object_term_cache(wp_list_pluck($posts, 'ID'), 'sjioc_office');
        foreach ($posts as $p) {
            $p_terms = get_the_terms($p->ID, 'sjioc_office_group');
            if (!$p_terms || is_wp_error($p_terms)) continue;
            $slug = $p_terms[0]->slug;
            if (!isset($data[$slug])) continue; // orphaned/unrecognized term, skip
            $data[$slug]['people'][] = [
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

    set_transient('sjioc_office_data_v2', $data, DAY_IN_SECONDS);
    return $data;
}

function sjioc_flush_office_cache() {
    delete_transient('sjioc_office_data_v2');
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
    $data = sjioc_get_office_data();

    $groups = array_filter($data, fn($g) => $g['section'] === 'leadership' && $g['people']);
    uasort($groups, fn($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['name'], $b['name']));
    if (!$groups) return false;

    $leaders = [];
    $joint   = [];
    foreach ($groups as $g) {
        foreach ($g['people'] as $p) {
            $p['_style'] = $g['style'];
            if ($g['style'] === 'pill') $joint[]   = $p;
            else                        $leaders[] = $p;
        }
    }

    if ($leaders) {
        echo '<div class="leadership-grid">';
        foreach ($leaders as $p) {
            $no_photo = ($p['_style'] === 'text');
            echo '<div class="leader-card' . ($no_photo ? ' leader-card-nophoto' : '') . '">';
            if (!$no_photo) {
                $img = $p['photo'] ?: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=250&q=70';
                echo '<img class="leader-avatar" src="' . esc_url($img) . '" alt="' . esc_attr($p['name']) . '" loading="lazy">';
            }
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
    $tabs = sjioc_office_tabs();

    $groups = array_filter($data, fn($g) => $g['section'] === 'committee' && $g['people']);
    uasort($groups, fn($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['name'], $b['name']));
    if (!$groups) return false;

    // Bucket populated committee groups by tab, in group order.
    $by_tab = [];
    foreach ($groups as $g) {
        $tab = isset($tabs[$g['tab']]) ? $g['tab'] : 'admin';
        $by_tab[$tab][] = $g;
    }

    // Tab bar (only tabs that have content).
    echo '<div class="cmte-tabs" role="tablist">';
    $first = true;
    foreach ($tabs as $key => $label) {
        if (empty($by_tab[$key])) continue;
        echo '<button class="cmte-tab' . ($first ? ' is-active' : '') . '" role="tab" data-panel="cmte-' . esc_attr($key) . '">' . esc_html($label) . '</button>';
        $first = false;
    }
    echo '</div>';

    // Panels.
    $first_panel = true;
    foreach ($tabs as $key => $label) {
        if (empty($by_tab[$key])) continue;
        echo '<div class="cmte-panel" id="cmte-' . esc_attr($key) . '"' . ($first_panel ? '' : ' style="display:none"') . '>';
        $first_item = true;
        foreach ($by_tab[$key] as $grp) {
            $open = $first_item;
            echo '<div class="acc-item' . ($open ? ' is-open' : '') . '">';
            echo '<button class="acc-header" aria-expanded="' . ($open ? 'true' : 'false') . '"><span>' . esc_html($grp['name']) . '</span><span class="acc-chevron">&#8964;</span></button>';
            echo '<div class="acc-body"' . ($open ? '' : ' style="display:none"') . '>';
            if ($grp['style'] === 'grid') {
                echo '<ul class="acc-grid">';
                foreach ($grp['people'] as $p) echo '<li>' . esc_html($p['name']) . '</li>';
                echo '</ul>';
            } elseif ($grp['style'] === 'text') {
                echo '<ul class="acc-role-list">';
                foreach ($grp['people'] as $p) {
                    echo '<li>';
                    if ($p['role']) echo '<span class="acc-role">' . esc_html($p['role']) . '</span>';
                    echo '<span class="acc-name">' . esc_html($p['name']) . '</span>';
                    if ($p['bio']) echo '<div class="acc-bio">' . nl2br(esc_html($p['bio'])) . '</div>';
                    echo '</li>';
                }
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
