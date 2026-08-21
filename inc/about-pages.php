<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: About Sections  (Our Parish / Our Diocese / Our Church / Our Vicar / About Us)
   Each fixed "key" has at most one published post; admins edit the seeded
   post instead of creating new ones. Lets these narrative pages be updated
   from WP Admin (e.g. a new Vicar) without a code change.
───────────────────────────────────── */
function sjioc_register_about_section() {
    register_post_type('sjioc_about_section', [
        'labels'        => [
            'name'          => __('About Sections',     'sjioc'),
            'singular_name' => __('About Section',       'sjioc'),
            'edit_item'     => __('Edit About Section',  'sjioc'),
            'menu_name'     => __('About Sections',      'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sjioc',
        'menu_icon'     => 'dashicons-book-alt',
        'supports'      => ['title', 'editor', 'thumbnail'],
        'show_in_rest'  => false,
    ]);
}
add_action('init', 'sjioc_register_about_section');

/* ─────────────────────────────────────
   CPT: Parish Milestones  (Our History timeline)
───────────────────────────────────── */
function sjioc_register_milestone() {
    register_post_type('sjioc_milestone', [
        'labels'        => [
            'name'          => __('Parish Milestones',    'sjioc'),
            'singular_name' => __('Milestone',             'sjioc'),
            'add_new_item'  => __('Add Milestone',         'sjioc'),
            'edit_item'     => __('Edit Milestone',        'sjioc'),
            'menu_name'     => __('Milestones',            'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sjioc',
        'menu_icon'     => 'dashicons-clock',
        'supports'      => ['title', 'editor'],
        'show_in_rest'  => false,
    ]);
}
add_action('init', 'sjioc_register_milestone');

/* ─────────────────────────────────────
   Fixed section keys — one published sjioc_about_section post per key.
───────────────────────────────────── */
function sjioc_about_section_config() {
    return [
        'about-us'    => 'About Us (hub page intro)',
        'our-parish'  => 'Our Parish',
        'our-diocese' => 'Our Diocese',
        'our-church'  => 'Our Church',
        'our-vicar'   => 'Our Vicar',
    ];
}

/* ─────────────────────────────────────
   Seed placeholder posts once so admins have something to edit immediately.
───────────────────────────────────── */
function sjioc_seed_about_sections() {
    if (get_option('sjioc_about_sections_seeded')) return;

    $placeholders = [
        'about-us'    => [
            'title'   => 'About Us',
            'content' => "<p>Welcome to St. John's Indian Orthodox Church of Delaware Valley. Explore our parish, diocese, church, and leadership below.</p><p><em>Edit this text in WP Admin → About Sections.</em></p>",
        ],
        'our-parish'  => [
            'title'   => 'Our Parish',
            'content' => "<p>Content coming soon — edit this in WP Admin → About Sections → Our Parish.</p>",
        ],
        'our-diocese' => [
            'title'   => 'Our Diocese',
            'content' => "<p>Content coming soon — edit this in WP Admin → About Sections → Our Diocese.</p>",
        ],
        'our-church'  => [
            'title'   => 'Our Church',
            'content' => "<p>Content coming soon — edit this in WP Admin → About Sections → Our Church.</p>",
        ],
        'our-vicar'   => [
            'title'   => 'Our Vicar',
            'content' => "<p>Content coming soon — edit this in WP Admin → About Sections → Our Vicar.</p>",
        ],
    ];

    foreach ($placeholders as $key => $p) {
        $id = wp_insert_post([
            'post_type'    => 'sjioc_about_section',
            'post_status'  => 'publish',
            'post_title'   => $p['title'],
            'post_content' => $p['content'],
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'about_section_key', $key);
        }
    }

    update_option('sjioc_about_sections_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_about_sections');

function sjioc_seed_milestones() {
    if (get_option('sjioc_milestones_seeded')) return;

    $milestones = [
        ['year' => 2006, 'title' => 'Parish Founded',       'content' => "By Kalpana No. K81/2006, His Grace Mathews Mar Barnabas declared the formation of St. John's congregation. First Holy Qurbana November 25, 2006. Fr. Geevarghese Erakkath appointed first Vicar."],
        ['year' => 2008, 'title' => 'Growing Congregation', 'content' => 'The parish grew significantly, welcoming families from across Delaware Valley into our Orthodox Christian community.'],
        ['year' => 2012, 'title' => 'MGOCSM Chapter',       'content' => 'The MGOCSM chapter was formally established, energizing youth and young adult participation in parish life.'],
        ['year' => 2019, 'title' => 'Home at Drexel Hill',  'content' => 'The parish settled at 4400 State Road, Drexel Hill, PA 19026 — our permanent home in the heart of Delaware Valley.'],
        ['year' => 2026, 'title' => 'Serving Today',        'content' => 'Under Rev. Fr. Tojo Baby, our parish continues to grow in faith, numbers, and community engagement.'],
    ];

    foreach ($milestones as $m) {
        $id = wp_insert_post([
            'post_type'    => 'sjioc_milestone',
            'post_status'  => 'publish',
            'post_title'   => $m['title'],
            'post_content' => $m['content'],
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'milestone_year', $m['year']);
        }
    }

    update_option('sjioc_milestones_seeded', 1);
}
add_action('admin_init', 'sjioc_seed_milestones');

/* ─────────────────────────────────────
   META BOX: About Section key + optional external link
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box('sjioc_about_section_details', 'About Section Settings', 'sjioc_about_section_meta_box_html', 'sjioc_about_section', 'side', 'default');
});

function sjioc_about_section_meta_box_html($post) {
    wp_nonce_field('sjioc_about_section_save', 'sjioc_about_section_nonce');
    $key        = get_post_meta($post->ID, 'about_section_key',   true);
    $link_label = get_post_meta($post->ID, 'about_section_link_label', true);
    $link_url   = get_post_meta($post->ID, 'about_section_link_url',   true);
    ?>
    <p>
        <label for="about_section_key"><strong>Section</strong></label><br>
        <select id="about_section_key" name="about_section_key" style="width:100%">
            <option value="">— none —</option>
            <?php foreach (sjioc_about_section_config() as $slug => $label): ?>
            <option value="<?php echo esc_attr($slug); ?>" <?php selected($key, $slug); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <span style="color:#666;font-style:italic;display:block;margin-top:4px">Only one published post per section is used.</span>
    </p>
    <p>
        <label for="about_section_link_label"><strong>External Link Label</strong></label><br>
        <input type="text" id="about_section_link_label" name="about_section_link_label" style="width:100%"
            value="<?php echo esc_attr($link_label); ?>" placeholder="e.g. Visit Diocese Website">
    </p>
    <p>
        <label for="about_section_link_url"><strong>External Link URL</strong></label><br>
        <input type="url" id="about_section_link_url" name="about_section_link_url" style="width:100%"
            value="<?php echo esc_attr($link_url); ?>" placeholder="https://">
    </p>
    <p style="color:#555">Featured Image (above) is shown alongside the text on the front end.</p>
    <?php
}

add_action('save_post_sjioc_about_section', function ($post_id) {
    if (!isset($_POST['sjioc_about_section_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_about_section_nonce'], 'sjioc_about_section_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $allowed_keys = array_keys(sjioc_about_section_config());
    $key = in_array($_POST['about_section_key'] ?? '', $allowed_keys, true) ? $_POST['about_section_key'] : '';
    update_post_meta($post_id, 'about_section_key', $key);
    update_post_meta($post_id, 'about_section_link_label', sanitize_text_field($_POST['about_section_link_label'] ?? ''));
    update_post_meta($post_id, 'about_section_link_url',   esc_url_raw($_POST['about_section_link_url'] ?? ''));
});

/* ─────────────────────────────────────
   META BOX: Milestone year
───────────────────────────────────── */
add_action('add_meta_boxes', function () {
    add_meta_box('sjioc_milestone_details', 'Milestone Year', 'sjioc_milestone_meta_box_html', 'sjioc_milestone', 'side', 'default');
});

function sjioc_milestone_meta_box_html($post) {
    wp_nonce_field('sjioc_milestone_save', 'sjioc_milestone_nonce');
    $year = get_post_meta($post->ID, 'milestone_year', true);
    ?>
    <p>
        <label for="milestone_year"><strong>Year</strong></label><br>
        <input type="number" id="milestone_year" name="milestone_year" style="width:100%"
            value="<?php echo esc_attr($year); ?>" min="1900" max="2200">
        <span style="color:#666;font-style:italic;display:block;margin-top:4px">Timeline is sorted by year, ascending.</span>
    </p>
    <?php
}

add_action('save_post_sjioc_milestone', function ($post_id) {
    if (!isset($_POST['sjioc_milestone_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_milestone_nonce'], 'sjioc_milestone_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'milestone_year', absint($_POST['milestone_year'] ?? 0));
});

/* ─────────────────────────────────────
   META BOX: on Pages using "About Section" template — which key to show
───────────────────────────────────── */
add_action('add_meta_boxes_page', function ($post) {
    if (get_page_template_slug($post->ID) !== 'template-about-section.php') return;
    add_meta_box('sjioc_about_page_key', 'About Section to Display', 'sjioc_about_page_key_meta_box_html', 'page', 'side', 'high');
});

function sjioc_about_page_key_meta_box_html($post) {
    wp_nonce_field('sjioc_about_page_key_save', 'sjioc_about_page_key_nonce');
    $key = get_post_meta($post->ID, 'sjioc_about_page_key', true);
    ?>
    <select name="sjioc_about_page_key" style="width:100%">
        <option value="">— none —</option>
        <?php foreach (sjioc_about_section_config() as $slug => $label):
            if ($slug === 'about-us') continue; // About Us uses its own dedicated hub template
        ?>
        <option value="<?php echo esc_attr($slug); ?>" <?php selected($key, $slug); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

add_action('save_post_page', function ($post_id) {
    if (!isset($_POST['sjioc_about_page_key_nonce']) ||
        !wp_verify_nonce($_POST['sjioc_about_page_key_nonce'], 'sjioc_about_page_key_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_page', $post_id)) return;

    $allowed_keys = array_keys(sjioc_about_section_config());
    $key = in_array($_POST['sjioc_about_page_key'] ?? '', $allowed_keys, true) ? $_POST['sjioc_about_page_key'] : '';
    update_post_meta($post_id, 'sjioc_about_page_key', $key);
    delete_transient('sjioc_about_nav_map');
});

/* ─────────────────────────────────────
   Data access — transient-cached reads
───────────────────────────────────── */
function sjioc_get_about_sections() {
    $cached = get_transient('sjioc_about_sections_data');
    if ($cached !== false) return $cached;

    $posts = get_posts([
        'post_type'      => 'sjioc_about_section',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    $data = [];
    foreach ($posts as $p) {
        $key = get_post_meta($p->ID, 'about_section_key', true);
        if (!$key || isset($data[$key])) continue; // first published post per key wins
        $data[$key] = [
            'title'      => $p->post_title,
            'content'    => apply_filters('the_content', $p->post_content),
            'image_url'  => get_the_post_thumbnail_url($p->ID, 'medium_large') ?: '',
            'link_label' => (string) get_post_meta($p->ID, 'about_section_link_label', true),
            'link_url'   => (string) get_post_meta($p->ID, 'about_section_link_url',   true),
        ];
    }

    set_transient('sjioc_about_sections_data', $data, DAY_IN_SECONDS);
    return $data;
}

function sjioc_get_about_section($key) {
    $data = sjioc_get_about_sections();
    return $data[$key] ?? false;
}

function sjioc_flush_about_sections_cache() {
    delete_transient('sjioc_about_sections_data');
}
add_action('save_post_sjioc_about_section', 'sjioc_flush_about_sections_cache');
add_action('deleted_post',                  'sjioc_flush_about_sections_cache');

function sjioc_get_milestones() {
    $cached = get_transient('sjioc_milestones_data');
    if ($cached !== false) return $cached;

    $posts = get_posts([
        'post_type'      => 'sjioc_milestone',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'milestone_year',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ]);

    $data = [];
    foreach ($posts as $p) {
        $data[] = [
            'year'    => (int) get_post_meta($p->ID, 'milestone_year', true),
            'title'   => $p->post_title,
            'content' => apply_filters('the_content', $p->post_content),
        ];
    }

    set_transient('sjioc_milestones_data', $data, DAY_IN_SECONDS);
    return $data;
}

function sjioc_flush_milestones_cache() {
    delete_transient('sjioc_milestones_data');
}
add_action('save_post_sjioc_milestone', 'sjioc_flush_milestones_cache');
add_action('deleted_post',              'sjioc_flush_milestones_cache');

/* ─────────────────────────────────────
   Nav map — real page URLs for the About Us hub, keyed by section.
   One cached query covers Our Parish/Diocese/Church/Vicar (via the shared
   template + page meta) and Leadership/Committees/Our History (via their
   dedicated templates) — keeps the hub page within the +1 query budget.
───────────────────────────────────── */
function sjioc_get_about_nav_map() {
    $cached = get_transient('sjioc_about_nav_map');
    if ($cached !== false) return $cached;

    $map = [];
    $template_keys = [
        'page-leadership.php'  => 'leadership',
        'page-committees.php'  => 'committees',
        'page-our-history.php' => 'our-history',
    ];

    $pages = get_pages(['post_status' => 'publish']);
    if ($pages) {
        update_meta_cache('post', wp_list_pluck($pages, 'ID'));
        foreach ($pages as $p) {
            $tpl = get_page_template_slug($p->ID);
            if ($tpl === 'template-about-section.php') {
                $key = get_post_meta($p->ID, 'sjioc_about_page_key', true);
                if ($key && !isset($map[$key])) $map[$key] = get_permalink($p->ID);
            } elseif (isset($template_keys[$tpl]) && !isset($map[$template_keys[$tpl]])) {
                $map[$template_keys[$tpl]] = get_permalink($p->ID);
            }
        }
    }

    set_transient('sjioc_about_nav_map', $map, DAY_IN_SECONDS);
    return $map;
}

function sjioc_flush_about_nav_map() {
    delete_transient('sjioc_about_nav_map');
}
add_action('save_post_page', 'sjioc_flush_about_nav_map');
add_action('deleted_post',   'sjioc_flush_about_nav_map');
