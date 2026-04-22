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
            'add_new_item'  => __('Add Contact',       'sjioc'),
            'menu_name'     => __('Directory',         'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 7,
        'supports'      => ['title','thumbnail','custom-fields'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'sjioc_register_contacts');

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

    $to      = sjioc_get('sjioc_email', 'info@sjioc.org');
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
