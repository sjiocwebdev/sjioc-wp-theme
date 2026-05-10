<?php
/**
 * SJIOC Delaware Valley — WordPress Theme Functions
 * St. John's Indian Orthodox Church of Delaware Valley
 * 4400 State Road, Drexel Hill, PA 19026 | (610) 822-0033
 */

defined('ABSPATH') || exit;

define('SJIOC_VER', '2.0.0');
define('SJIOC_DIR', get_template_directory());
define('SJIOC_URI', get_template_directory_uri());

/* ─────────────────────────────────────
   THEME SETUP
───────────────────────────────────── */
function sjioc_setup() {
    load_theme_textdomain('sjioc', SJIOC_DIR . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','script','style']);
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 200,
        'flex-width'  => true,
        'flex-height' => true,
    ]);
    add_image_size('sjioc-hero',   1800, 900, true);
    add_image_size('sjioc-card',    600, 400, true);
    add_image_size('sjioc-thumb',   400, 300, true);
    add_image_size('sjioc-square',  300, 300, true);

    register_nav_menus([
        'primary' => __('Primary Navigation', 'sjioc'),
        'footer'  => __('Footer Navigation',  'sjioc'),
    ]);
}
add_action('after_setup_theme', 'sjioc_setup');

/* ─────────────────────────────────────
   ENQUEUE SCRIPTS & STYLES
───────────────────────────────────── */
function sjioc_assets() {
    // Google Fonts
    wp_enqueue_style('sjioc-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Lato:wght@300;400;700&display=swap',
        [], null
    );
    // Main stylesheet
    wp_enqueue_style('sjioc-style', get_stylesheet_uri(), ['sjioc-fonts'], SJIOC_VER);
    // Main JS
    wp_enqueue_script('sjioc-main', SJIOC_URI . '/assets/js/main.js', [], SJIOC_VER, true);
    // Pass data to JS
    wp_localize_script('sjioc-main', 'sjioData', [
        'phone'   => sjioc_get('sjioc_phone', '(610) 822-0033'),
        'email'   => sjioc_get('sjioc_email', 'info@sjioc.org'),
        'address' => sjioc_get('sjioc_address', '4400 State Road, Drexel Hill, PA 19026'),
        'qurbana' => sjioc_get('sjioc_qurbana', '8:30 AM'),
        'school'  => sjioc_get('sjioc_school',  '12:00 PM'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('sjioc_ajax'),
    ]);
    // Comment reply
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'sjioc_assets');

/* ─────────────────────────────────────
   CUSTOM POST TYPE: Events
───────────────────────────────────── */
function sjioc_register_events() {
    register_post_type('sjioc_event', [
        'labels'        => [
            'name'          => __('Events',     'sjioc'),
            'singular_name' => __('Event',      'sjioc'),
            'add_new_item'  => __('Add New Event', 'sjioc'),
            'menu_name'     => __('Events',     'sjioc'),
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
   CUSTOM POST TYPE: Gallery
───────────────────────────────────── */
function sjioc_register_gallery() {
    register_post_type('sjioc_gallery', [
        'labels'        => [
            'name'          => __('Gallery',       'sjioc'),
            'singular_name' => __('Photo',         'sjioc'),
            'add_new_item'  => __('Add New Photo',  'sjioc'),
            'menu_name'     => __('Gallery',       'sjioc'),
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
   CUSTOM POST TYPE: Contacts (Directory)
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
   CUSTOM POST TYPE: Celebrations
───────────────────────────────────── */
function sjioc_register_celebrations() {
    register_post_type('sjioc_celeb', [
        'labels'        => [
            'name'          => __('Birthdays & Anniversaries', 'sjioc'),
            'singular_name' => __('Celebration',               'sjioc'),
            'add_new_item'  => __('Add Celebration',            'sjioc'),
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
   CUSTOMIZER SETTINGS
───────────────────────────────────── */
function sjioc_customizer($wp_customize) {

    $wp_customize->add_section('sjioc_info', [
        'title'       => __('Church Information', 'sjioc'),
        'description' => __('Core church details used throughout the theme.', 'sjioc'),
        'priority'    => 30,
    ]);

    $wp_customize->add_section('sjioc_hero', [
        'title'    => __('Home Page Hero', 'sjioc'),
        'priority' => 31,
    ]);

    $settings = [
        // Church Info
        'sjioc_church_name'  => ['label' => 'Full Church Name',     'default' => "St. John's Indian Orthodox Church Of Delaware Valley", 'section' => 'sjioc_info'],
        'sjioc_abbr'         => ['label' => 'Abbreviation',         'default' => 'SJIOC',                                                 'section' => 'sjioc_info'],
        'sjioc_address'      => ['label' => 'Address',              'default' => '4400 State Road, Drexel Hill, PA 19026',               'section' => 'sjioc_info'],
        'sjioc_phone'        => ['label' => 'Phone Number',         'default' => '(610) 822-0033',                                       'section' => 'sjioc_info'],
        'sjioc_email'        => ['label' => 'Email Address',        'default' => 'info@sjioc.org',                                       'section' => 'sjioc_info'],
        'sjioc_qurbana'      => ['label' => 'Holy Qurbana Time',    'default' => '8:30 AM',                                              'section' => 'sjioc_info'],
        'sjioc_school'       => ['label' => 'Sunday School Time',   'default' => '12:00 PM',                                             'section' => 'sjioc_info'],
        'sjioc_saturday'     => ['label' => 'Saturday Office Hours','default' => '5:00 PM – 7:30 PM',                                   'section' => 'sjioc_info'],
        'sjioc_facebook'     => ['label' => 'Facebook URL',         'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_youtube'      => ['label' => 'YouTube URL',          'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_maps_url'     => ['label' => 'Google Maps URL',      'default' => 'https://share.google/zTkW7YSgj41LVTwW9',              'section' => 'sjioc_info'],
        // Contact Routing Emails
        'sjioc_email_vicar'     => ['label' => 'Vicar Email',          'default' => 'info@sjioc.org',  'section' => 'sjioc_info'],
        'sjioc_email_trustee'   => ['label' => 'Trustee Email',        'default' => 'info@sjioc.org',  'section' => 'sjioc_info'],
        'sjioc_email_secretary' => ['label' => 'Secretary Email',      'default' => 'info@sjioc.org',  'section' => 'sjioc_info'],
        // Hero
        'sjioc_hero_title'   => ['label' => 'Hero Headline',        'default' => "St. John's Indian Orthodox Church",                   'section' => 'sjioc_hero'],
        'sjioc_hero_sub'     => ['label' => 'Hero Subtitle',        'default' => 'A Faith Community Rooted in Tradition · Delaware Valley', 'section' => 'sjioc_hero'],
        'sjioc_hero_eyebrow' => ['label' => 'Eyebrow Text',         'default' => '✦ Est. 2006 · Drexel Hill, PA ✦',                    'section' => 'sjioc_hero'],
    ];

    foreach ($settings as $id => $args) {
        $wp_customize->add_setting($id, [
            'default'           => $args['default'],
            'sanitize_callback' => 'wp_kses_post',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control($id, [
            'label'   => __($args['label'], 'sjioc'),
            'section' => $args['section'],
            'type'    => 'text',
        ]);
    }
}
add_action('customize_register', 'sjioc_customizer');

/* ─────────────────────────────────────
   WIDGET AREAS
───────────────────────────────────── */
function sjioc_widgets_init() {
    register_sidebar([
        'name'          => __('Footer Widget Area', 'sjioc'),
        'id'            => 'footer-widgets',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ]);
}
add_action('widgets_init', 'sjioc_widgets_init');

/* ─────────────────────────────────────
   SMTP — Outlook / Microsoft 365
───────────────────────────────────── */
add_action('phpmailer_init', function ($phpmailer) {
    if (!defined('SJIOC_SMTP_HOST') || !defined('SJIOC_SMTP_USER') || !defined('SJIOC_SMTP_PASS')) return;
    $phpmailer->isSMTP();
    $phpmailer->Host       = SJIOC_SMTP_HOST;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = SJIOC_SMTP_USER;
    $phpmailer->Password   = SJIOC_SMTP_PASS;
    $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $phpmailer->Port       = defined('SJIOC_SMTP_PORT') ? SJIOC_SMTP_PORT : 587;
    $phpmailer->From       = SJIOC_SMTP_USER;
    $phpmailer->FromName   = sjioc_name();
});

/* ─────────────────────────────────────
   AJAX: Contact Form
───────────────────────────────────── */
function sjioc_handle_contact() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    $fname   = sanitize_text_field($_POST['fname']   ?? '');
    $lname   = sanitize_text_field($_POST['lname']   ?? '');
    $email   = sanitize_email($_POST['email']         ?? '');
    $phone   = sanitize_text_field($_POST['phone']   ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($fname) || empty($email) || empty($message)) {
        wp_send_json_error(['msg' => __('Please fill in your name, email, and message.', 'sjioc')]);
    }

    if (!is_email($email)) {
        wp_send_json_error(['msg' => __('Please enter a valid email address.', 'sjioc')]);
    }

    $routing = [
        'Contact the Vicar'     => sjioc_get('sjioc_email_vicar',     'info@sjioc.org'),
        'Contact the Trustee'   => sjioc_get('sjioc_email_trustee',   'info@sjioc.org'),
        'Contact the Secretary' => sjioc_get('sjioc_email_secretary', 'info@sjioc.org'),
    ];
    $to      = $routing[$subject] ?? sjioc_get('sjioc_email', 'info@sjioc.org');
    $headers = ['Content-Type: text/html; charset=UTF-8', "Reply-To: {$email}"];
    $body    = "<p><strong>From:</strong> {$fname} {$lname}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Phone:</strong> {$phone}</p>
                <p><strong>Subject:</strong> {$subject}</p><hr>
                <p><strong>Message:</strong></p><p>" . nl2br($message) . "</p>";

    $sent = wp_mail($to, "SJIOC Website Contact: {$subject}", $body, $headers);

    if ($sent) {
        wp_send_json_success(['msg' => __("Thank you! Your message has been sent. We'll be in touch soon.", 'sjioc')]);
    } else {
        wp_send_json_error(['msg' => __('Sorry, there was an issue sending your message. Please call us directly.', 'sjioc')]);
    }
}
add_action('wp_ajax_sjioc_contact',        'sjioc_handle_contact');
add_action('wp_ajax_nopriv_sjioc_contact', 'sjioc_handle_contact');

/* ─────────────────────────────────────
   HELPER FUNCTIONS
───────────────────────────────────── */
function sjioc_get($key, $fallback = '') {
    return get_theme_mod($key, $fallback);
}
function sjioc_phone()   { return sjioc_get('sjioc_phone',   '(610) 822-0033'); }
function sjioc_email()   { return sjioc_get('sjioc_email',   'info@sjioc.org'); }
function sjioc_address() { return sjioc_get('sjioc_address', '4400 State Road, Drexel Hill, PA 19026'); }
function sjioc_name()    { return sjioc_get('sjioc_church_name', "St. John's Indian Orthodox Church Of Delaware Valley"); }
function sjioc_abbr()    { return sjioc_get('sjioc_abbr',    'SJIOC'); }
function sjioc_qurbana() { return sjioc_get('sjioc_qurbana', '8:30 AM'); }
function sjioc_school()  { return sjioc_get('sjioc_school',  '12:00 PM'); }
function sjioc_maps()    { return sjioc_get('sjioc_maps_url','https://share.google/zTkW7YSgj41LVTwW9'); }
function sjioc_fb()      { return sjioc_get('sjioc_facebook','#'); }
function sjioc_yt()      { return sjioc_get('sjioc_youtube', '#'); }

function sjioc_footer() { ?>
<footer id="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <span class="footer-brand-name"><?php echo esc_html(sjioc_name()); ?></span>
        <p><?php echo esc_html(sjioc_address()); ?></p>
        <p>A faith community rooted in the apostolic Orthodox tradition, serving Delaware Valley since 2006.</p>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Service Times</span>
        <p>Sunday Holy Qurbana</p>
        <p><strong style="color:var(--go)"><?php echo esc_html(sjioc_qurbana()); ?></strong></p>
        <br><p>Sunday School</p>
        <p><strong style="color:var(--go)"><?php echo esc_html(sjioc_school()); ?></strong></p>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Quick Links</span>
        <?php wp_nav_menu(['theme_location'=>'footer','container'=>false,'fallback_cb'=>'sjioc_footer_links']); ?>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Contact Us</span>
        <p>📍 <?php echo esc_html(sjioc_address()); ?></p>
        <br>
        <p>📞 <a href="tel:<?php echo preg_replace('/\D/','',(sjioc_phone())); ?>"><?php echo esc_html(sjioc_phone()); ?></a></p>
        <br>
        <p>✉ <a href="mailto:<?php echo esc_attr(sjioc_email()); ?>"><?php echo esc_html(sjioc_email()); ?></a></p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(sjioc_name()); ?>. All rights reserved. Hosted on <span style="color:rgba(255,255,255,.5)">Microsoft Azure</span>.</p>
      <div class="social-links">
        <a class="social-link" href="<?php echo esc_url(sjioc_fb()); ?>" target="_blank" rel="noopener" aria-label="Facebook">f</a>
        <a class="social-link" href="<?php echo esc_url(sjioc_yt()); ?>" target="_blank" rel="noopener" aria-label="YouTube">▶</a>
      </div>
    </div>
  </div>
</footer>
<?php }

function sjioc_footer_links() {
    $pages = [
        home_url('/')                    => 'Home',
        home_url('/about-us/')           => 'About Us',
        home_url('/worship-services/')   => 'Worship & Services',
        home_url('/ministries/')         => 'Ministries',
        home_url('/events/')             => 'Events',
        home_url('/photos/')             => 'Photos',
        home_url('/contact-us/')         => 'Contact',
    ];
    echo '<ul>';
    foreach ($pages as $url => $label) {
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

 function sjioc_default_nav() {
    sjioc_footer_links();
}

/* ─────────────────────────────────────
   FALLBACK NAV MENU
───────────────────────────────────── */
 function sjioc_primary_nav_fallback() {
    $pages = [
        home_url('/')                    => 'Home',
        home_url('/about-us/')           => 'About',
        home_url('/worship-services/')   => 'Worship',
        home_url('/ministries/')         => 'Ministries',
        home_url('/events/')             => 'Events',
        home_url('/photos/')             => 'Photos',
        home_url('/contact-us/')         => 'Contact',
    ];
    echo '<ul id="primary-menu">';
    foreach ($pages as $url => $label) {
        $cls = (get_permalink() === $url) ? ' class="current_page_item"' : '';
        echo '<li' . $cls . '><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

/* ─────────────────────────────────────
   ADMIN MENU — SJIOC
───────────────────────────────────── */
function sjioc_admin_menu() {
    add_menu_page(
        'SJIOC Settings', 'SJIOC', 'manage_options',
        'sjioc-chat', 'sjioc_chat_settings_page',
        'dashicons-church', 58
    );
    add_submenu_page(
        'sjioc-chat', 'Chat & Knowledge Base', 'Chat Settings',
        'manage_options', 'sjioc-chat', 'sjioc_chat_settings_page'
    );
}
add_action('admin_menu', 'sjioc_admin_menu');

function sjioc_chat_settings_page() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['sjioc_kb_save']) && check_admin_referer('sjioc_kb_nonce')) {
        update_option('sjioc_kb_text', sanitize_textarea_field(wp_unslash($_POST['sjioc_kb_text'] ?? '')));
        echo '<div class="notice notice-success is-dismissible"><p>Knowledge base saved.</p></div>';
    }
    $kb = get_option('sjioc_kb_text', '');
    ?>
    <div class="wrap">
        <h1>SJIOC Chat — Knowledge Base</h1>
        <p>Open your church PDF, copy all the text, and paste it below. The AI assistant uses this to answer parish questions.</p>
        <form method="post">
            <?php wp_nonce_field('sjioc_kb_nonce'); ?>
            <textarea name="sjioc_kb_text" rows="22"
                style="width:100%;font-family:monospace;font-size:13px"><?php echo esc_textarea($kb); ?></textarea>
            <br><br>
            <input type="submit" name="sjioc_kb_save" class="button button-primary" value="Save Knowledge Base">
        </form>
    </div>
    <?php
}

/* ─────────────────────────────────────
   AJAX: Chat
───────────────────────────────────── */
add_action('wp_ajax_sjioc_chat',        'sjioc_chat_ajax');
add_action('wp_ajax_nopriv_sjioc_chat', 'sjioc_chat_ajax');

function sjioc_chat_ajax() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    $message = sanitize_text_field(wp_unslash($_POST['message'] ?? ''));
    if (!$message) {
        wp_send_json_error('empty');
    }

    // Normalize and check if it looks like a license plate
    $stripped = strtoupper(preg_replace('/[\s\-]/', '', $message));
    $is_plate_like = preg_match('/^[A-Z]{1,4}[0-9]{1,4}[A-Z0-9]{0,3}$/', $stripped)
                  || preg_match('/^[0-9]{1,4}[A-Z]{1,4}[A-Z0-9]{0,3}$/', $stripped);

    if ($is_plate_like) {
        $vehicle = sjioc_lookup_plate($stripped);
        if ($vehicle) {
            wp_send_json_success(['html' => sjioc_plate_html($vehicle)]);
            return;
        }
        wp_send_json_success(['html' =>
            '&#10060; No vehicle registered with plate <strong>' . esc_html(strtoupper($message)) . '</strong>.<br><br>' .
            'Please contact the <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . esc_html(sjioc_phone())
        ]);
        return;
    }

    // General question → Azure OpenAI
    wp_send_json_success(['html' => sjioc_azure_oai($message)]);
}

function sjioc_lookup_plate($normalized) {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_vehicles';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM `{$table}` WHERE UPPER(REPLACE(REPLACE(license_plate,' ',''),'-','')) = %s",
        $normalized
    ));
}

function sjioc_plate_html($v) {
    $tel = preg_replace('/\D/', '', $v->owner_phone);
    $html  = '&#128663; <strong>Vehicle Found</strong><br><br>';
    $html .= 'Owner: <strong>' . esc_html($v->owner_name) . '</strong><br>';
    $html .= 'Phone: <a href="tel:' . esc_attr($tel) . '" style="color:var(--go)">' . esc_html($v->owner_phone) . '</a><br>';
    if (!empty($v->vehicle_desc)) {
        $html .= 'Vehicle: ' . esc_html($v->vehicle_desc) . '<br>';
    }
    $html .= '<br>Please contact the owner directly to resolve the parking situation. &#128591;';
    return $html;
}

function sjioc_azure_oai($message) {
    $endpoint = defined('SJIOC_AZURE_OAI_ENDPOINT') ? SJIOC_AZURE_OAI_ENDPOINT : '';
    $key      = defined('SJIOC_AZURE_OAI_KEY')      ? SJIOC_AZURE_OAI_KEY      : '';
    $deploy   = defined('SJIOC_AZURE_OAI_DEPLOY')   ? SJIOC_AZURE_OAI_DEPLOY   : 'gpt-4o';

    if (!$endpoint || !$key) {
        return 'The assistant is not fully configured yet. Please contact the <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . esc_html(sjioc_phone());
    }

    $url  = rtrim($endpoint, '/') . '/openai/deployments/' . rawurlencode($deploy) . '/chat/completions?api-version=2024-02-01';
    $kb   = get_option('sjioc_kb_text', '');
    $body = wp_json_encode([
        'messages'    => [
            ['role' => 'system', 'content' => sjioc_chat_system_prompt($kb)],
            ['role' => 'user',   'content' => $message],
        ],
        'max_tokens'  => 250,
        'temperature' => 0.4,
    ]);

    $res = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json', 'api-key' => $key],
        'body'    => $body,
        'timeout' => 20,
    ]);

    if (is_wp_error($res)) {
        return 'Sorry, I\'m having trouble connecting. Please call us at <strong>' . esc_html(sjioc_phone()) . '</strong>.';
    }

    $data  = json_decode(wp_remote_retrieve_body($res), true);
    $reply = trim($data['choices'][0]['message']['content'] ?? '');

    if (!$reply) {
        return 'I\'m not sure about that. Please contact our <strong>Secretary</strong> or a <strong>Trustee</strong> at ' . esc_html(sjioc_phone()) . '.';
    }

    return wp_kses($reply, [
        'strong' => [], 'em' => [], 'br' => [],
        'a'      => ['href' => [], 'target' => [], 'style' => []],
    ]);
}

function sjioc_chat_system_prompt($kb = '') {
    $prompt = sprintf(
        "You are a friendly parish assistant for %s, an Indian Orthodox Christian church at %s.\n" .
        "Phone: %s | Email: %s\n" .
        "Service Times: Holy Qurbana %s | Sunday School %s | Saturday %s\n\n" .
        "Answer questions about the church warmly and concisely (2-4 sentences max). " .
        "If you don't know something, direct the person to contact the Secretary or a Trustee at %s. " .
        "Never invent information.",
        sjioc_name(), sjioc_address(),
        sjioc_phone(), sjioc_email(),
        sjioc_qurbana(), sjioc_school(), sjioc_get('sjioc_saturday', '5:00–7:30 PM'),
        sjioc_phone()
    );

    if ($kb) {
        $prompt .= "\n\nAdditional parish info:\n" . mb_substr($kb, 0, 3000);
    }

    return $prompt;
}
