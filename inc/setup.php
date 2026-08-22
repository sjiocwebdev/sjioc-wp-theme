<?php
defined('ABSPATH') || exit;

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
    wp_enqueue_style('sjioc-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Lato:wght@300;400;700&display=swap',
        [], null
    );
    wp_enqueue_style('sjioc-style', get_stylesheet_uri(), ['sjioc-fonts'], SJIOC_VER);
    wp_enqueue_script('sjioc-main', SJIOC_URI . '/assets/js/main.js', [], SJIOC_VER, true);
    wp_localize_script('sjioc-main', 'sjioData', [
        'phone'         => sjioc_get('sjioc_phone', '(610) 822-0033'),
        'email'         => sjioc_get('sjioc_email', 'info@sjioc.org'),
        'address'       => sjioc_get('sjioc_address', '4400 State Road, Drexel Hill, PA 19026'),
        'qurbana'       => sjioc_get('sjioc_qurbana', '9:30 AM'),
        'school'        => sjioc_get('sjioc_school',  '12:00 PM'),
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('sjioc_ajax'),
        'recaptchaKey'  => sjioc_get('sjioc_recaptcha_site_key', ''),
    ]);
    // Load reCAPTCHA v3 only on pages that have forms
    if (is_page_template(['page-contact-us.php', 'page-hall-rental.php'])) {
        $rc_key = sjioc_get('sjioc_recaptcha_site_key', '');
        if ($rc_key) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($rc_key),
                [],
                null,
                true
            );
        }
    }
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'sjioc_assets');

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

    $wp_customize->add_section('sjioc_rental', [
        'title'       => __('Hall Rental Settings', 'sjioc'),
        'description' => __('Configure the Hall Rental page and notifications.', 'sjioc'),
        'priority'    => 32,
    ]);

    $wp_customize->add_section('sjioc_recaptcha', [
        'title'       => __('CAPTCHA — Bot Protection', 'sjioc'),
        'description' => __('Google reCAPTCHA v3 (invisible). Get free keys at g.co/recaptcha — choose reCAPTCHA v3, register your domain. Protects the Contact and Hall Rental forms.', 'sjioc'),
        'priority'    => 33,
    ]);

    $settings = [
        'sjioc_church_name'     => ['label' => 'Full Church Name',      'default' => "St. John's Indian Orthodox Church Of Delaware Valley", 'section' => 'sjioc_info'],
        'sjioc_abbr'            => ['label' => 'Abbreviation',          'default' => 'SJIOC',                                                 'section' => 'sjioc_info'],
        'sjioc_address'         => ['label' => 'Address',               'default' => '4400 State Road, Drexel Hill, PA 19026',               'section' => 'sjioc_info'],
        'sjioc_phone'           => ['label' => 'Phone Number',          'default' => '(610) 822-0033',                                       'section' => 'sjioc_info'],
        'sjioc_email'           => ['label' => 'Email Address',         'default' => 'info@sjioc.org',                                       'section' => 'sjioc_info'],
        'sjioc_morning_prayer'  => ['label' => 'Sunday Morning Prayer Time', 'default' => '8:30 AM',                                        'section' => 'sjioc_info'],
        'sjioc_qurbana'         => ['label' => 'Sunday Holy Qurbana Time', 'default' => '9:30 AM',                                           'section' => 'sjioc_info'],
        'sjioc_school'          => ['label' => 'Spiritual Organizations Time', 'default' => '12:00 PM',                                      'section' => 'sjioc_info'],
        'sjioc_saturday'        => ['label' => 'Saturday Evening Prayer Time', 'default' => '6:00 PM',                                       'section' => 'sjioc_info'],
        'sjioc_first_wed_qurbana' => ['label' => 'First Wednesday Holy Qurbana Time', 'default' => '6:00 PM',                                'section' => 'sjioc_info'],
        'sjioc_facebook'        => ['label' => 'Facebook URL',          'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_instagram'       => ['label' => 'Instagram URL',         'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_youtube'         => ['label' => 'YouTube URL',           'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_zoom'            => ['label' => 'Zoom Meeting URL',      'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_calendar_url'    => ['label' => 'Online Calendar URL',   'default' => '#',                                                    'section' => 'sjioc_info'],
        'sjioc_maps_url'        => ['label' => 'Google Maps URL',       'default' => 'https://share.google/zTkW7YSgj41LVTwW9',              'section' => 'sjioc_info'],
        'sjioc_email_vicar'     => ['label' => 'Vicar Email',           'default' => 'info@sjioc.org',                                       'section' => 'sjioc_info'],
        'sjioc_email_trustee'   => ['label' => 'Trustee Email',         'default' => 'info@sjioc.org',                                       'section' => 'sjioc_info'],
        'sjioc_email_secretary' => ['label' => 'Secretary Email',       'default' => 'info@sjioc.org',                                       'section' => 'sjioc_info'],
        'sjioc_hero_title'      => ['label' => 'Hero Headline',         'default' => "St. John's Indian Orthodox Church",                   'section' => 'sjioc_hero'],
        'sjioc_hero_sub'        => ['label' => 'Hero Subtitle',         'default' => 'A Faith Community Rooted in Tradition · Delaware Valley', 'section' => 'sjioc_hero'],
        'sjioc_hero_eyebrow'    => ['label' => 'Eyebrow Text',          'default' => '✦ Est. 2006 · Drexel Hill, PA ✦',                    'section' => 'sjioc_hero'],
        // Hall Rental
        'sjioc_hall_name'            => ['label' => 'Hall Name',                 'default' => 'Parish Hall',       'section' => 'sjioc_rental'],
        'sjioc_hall_capacity'        => ['label' => 'Hall Capacity (persons)',   'default' => '200',               'section' => 'sjioc_rental'],
        'sjioc_rentals_sp_link'      => ['label' => 'SharePoint Rentals Folder URL (shown in notification emails)', 'default' => '', 'section' => 'sjioc_rental'],
        'sjioc_rentals_od_folder_id' => ['label' => 'OneDrive Rentals Folder ID (for auto-upload; requires Files.ReadWrite.All permission)', 'default' => '', 'section' => 'sjioc_rental'],
        'sjioc_hall_booking_amount'  => ['label' => 'Hall Booking Fee ($)',  'default' => '650', 'section' => 'sjioc_rental'],
        'sjioc_hall_deposit_amount'  => ['label' => 'Security Deposit ($)',  'default' => '100', 'section' => 'sjioc_rental'],
        // reCAPTCHA
        'sjioc_recaptcha_site_key'   => ['label' => 'reCAPTCHA v3 Site Key (public — paste from Google)',   'default' => '', 'section' => 'sjioc_recaptcha'],
        'sjioc_recaptcha_secret_key' => ['label' => 'reCAPTCHA v3 Secret Key (private — never share this)', 'default' => '', 'section' => 'sjioc_recaptcha'],
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

    // Footer logo — separate upload, independent of the nav logo
    $wp_customize->add_setting('sjioc_footer_logo', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_footer_logo', [
        'label'       => __('Footer Logo Image', 'sjioc'),
        'section'     => 'sjioc_info',
        'mime_type'   => 'image',
        'description' => __('Upload a logo for the footer. Falls back to the site logo, then the cross SVG.', 'sjioc'),
    ]));

    // Zelle QR code image for the Support Us / Give page
    $wp_customize->add_setting('sjioc_zelle_qr', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_zelle_qr', [
        'label'       => __('Zelle QR Code Image', 'sjioc'),
        'section'     => 'sjioc_info',
        'mime_type'   => 'image',
        'description' => __('Upload the Zelle QR code displayed on the Support Us / Give page.', 'sjioc'),
    ]));

    // Patron Saint icon — Worship & Services "Feast of the Patron Saint" section
    $wp_customize->add_setting('sjioc_patron_saint_icon', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_patron_saint_icon', [
        'label'       => __('Patron Saint Icon (St. John the Baptist)', 'sjioc'),
        'section'     => 'sjioc_info',
        'mime_type'   => 'image',
        'description' => __('Shown on the Worship & Services page, Feast of the Patron Saint section.', 'sjioc'),
    ]));

    // Hero watermark — separate upload, independent of the nav logo
    $wp_customize->add_setting('sjioc_hero_watermark', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_hero_watermark', [
        'label'     => __('Hero Watermark Image', 'sjioc'),
        'section'   => 'sjioc_hero',
        'mime_type' => 'image',
        'description' => __('Upload a high-res image (PNG with transparency recommended). Falls back to the site logo, then the cross SVG.', 'sjioc'),
    ]));

    // Home page Welcome section image
    $wp_customize->add_setting('sjioc_welcome_img', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_welcome_img', [
        'label'       => __('Home Page Welcome Image', 'sjioc'),
        'section'     => 'sjioc_hero',
        'mime_type'   => 'image',
        'description' => __('Shown in the "Welcome" section on the home page, below the hero banner.', 'sjioc'),
    ]));

    // About Us page intro image
    $wp_customize->add_setting('sjioc_about_img', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_about_img', [
        'label'       => __('About Us Page Image', 'sjioc'),
        'section'     => 'sjioc_info',
        'mime_type'   => 'image',
        'description' => __('Shown alongside the intro text on the About Us page.', 'sjioc'),
    ]));

    // Worship & Services page image
    $wp_customize->add_setting('sjioc_worship_img', [
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'sjioc_worship_img', [
        'label'       => __('Worship & Services Page Image', 'sjioc'),
        'section'     => 'sjioc_info',
        'mime_type'   => 'image',
        'description' => __('Shown on the Worship & Services page, next to the Sunday Schedule.', 'sjioc'),
    ]));
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
   SMTP HELPERS — wp-config.php → DB fallback
───────────────────────────────────── */
function sjioc_smtp_host(): string {
    return defined('SJIOC_SMTP_HOST') ? SJIOC_SMTP_HOST : (string) get_option('sjioc_smtp_host', '');
}
function sjioc_smtp_user(): string {
    return defined('SJIOC_SMTP_USER') ? SJIOC_SMTP_USER : (string) get_option('sjioc_smtp_user', '');
}
function sjioc_smtp_pass(): string {
    return defined('SJIOC_SMTP_PASS') ? SJIOC_SMTP_PASS : (string) get_option('sjioc_smtp_pass', '');
}
function sjioc_smtp_port(): int {
    return defined('SJIOC_SMTP_PORT') ? (int) SJIOC_SMTP_PORT : (int) get_option('sjioc_smtp_port', 587);
}
function sjioc_smtp_from(): string {
    if (defined('SJIOC_SMTP_FROM')) return SJIOC_SMTP_FROM;
    $v = (string) get_option('sjioc_smtp_from', '');
    return $v ?: sjioc_smtp_user();
}
function sjioc_smtp_is_configured(): bool {
    return sjioc_smtp_host() !== '' && sjioc_smtp_user() !== '' && sjioc_smtp_pass() !== '';
}

/* ─────────────────────────────────────
   SMTP — Outlook / Microsoft 365
───────────────────────────────────── */
add_action('phpmailer_init', function ($phpmailer) {
    if (!sjioc_smtp_is_configured()) return;
    $phpmailer->isSMTP();
    $phpmailer->Host       = sjioc_smtp_host();
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = sjioc_smtp_user();
    $phpmailer->Password   = sjioc_smtp_pass();
    $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $phpmailer->Port       = sjioc_smtp_port();
    $phpmailer->From       = sjioc_smtp_from();
    $phpmailer->FromName   = sjioc_name();
});

/* ─────────────────────────────────────
   GRAPH API MAIL HELPERS — wp-config.php → DB fallback
───────────────────────────────────── */
function sjioc_mail_tenant_id(): string {
    return defined('SJIOC_MAIL_TENANT_ID') ? SJIOC_MAIL_TENANT_ID : (string) get_option('sjioc_mail_tenant_id', '');
}
function sjioc_mail_client_id(): string {
    return defined('SJIOC_MAIL_CLIENT_ID') ? SJIOC_MAIL_CLIENT_ID : (string) get_option('sjioc_mail_client_id', '');
}
function sjioc_mail_client_secret(): string {
    return defined('SJIOC_MAIL_CLIENT_SECRET') ? SJIOC_MAIL_CLIENT_SECRET : (string) get_option('sjioc_mail_client_secret', '');
}
function sjioc_mail_from(): string {
    return defined('SJIOC_MAIL_FROM') ? SJIOC_MAIL_FROM : (string) get_option('sjioc_mail_from', '');
}
function sjioc_mail_is_configured(): bool {
    return sjioc_mail_tenant_id() !== '' && sjioc_mail_client_id() !== ''
        && sjioc_mail_client_secret() !== '' && sjioc_mail_from() !== '';
}

function sjioc_graph_get_mail_token(): string|false {
    $cached = get_transient('sjioc_mail_token');
    if ($cached) return $cached;

    $resp = wp_remote_post(
        'https://login.microsoftonline.com/' . sjioc_mail_tenant_id() . '/oauth2/v2.0/token',
        ['body' => [
            'grant_type'    => 'client_credentials',
            'client_id'     => sjioc_mail_client_id(),
            'client_secret' => sjioc_mail_client_secret(),
            'scope'         => 'https://graph.microsoft.com/.default',
        ]]
    );

    if (is_wp_error($resp)) return false;
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['access_token'])) return false;

    $ttl = max(60, (int) ($data['expires_in'] ?? 3600) - 60);
    set_transient('sjioc_mail_token', $data['access_token'], $ttl);
    return $data['access_token'];
}

function sjioc_graph_send_mail(array|string $to, string $subject, string $body_html, array $headers = []): bool {
    $token = sjioc_graph_get_mail_token();
    if (!$token) return false;

    $to_list    = is_array($to) ? $to : array_filter(array_map('trim', explode(',', $to)));
    $recipients = array_map(fn($a) => ['emailAddress' => ['address' => $a]], $to_list);

    $reply_to = '';
    $cc_list  = [];
    foreach ($headers as $h) {
        if (stripos($h, 'Reply-To:') === 0) { $reply_to = trim(substr($h, 9)); continue; }
        if (stripos($h, 'Cc:') === 0) {
            $cc_list = array_filter(array_map('trim', explode(',', substr($h, 3))));
        }
    }

    $from_addr = sjioc_mail_from();
    $message   = [
        'subject'      => $subject,
        'from'         => ['emailAddress' => ['name' => sjioc_name(), 'address' => $from_addr]],
        'body'         => ['contentType' => 'HTML', 'content' => $body_html],
        'toRecipients' => $recipients,
    ];
    if ($reply_to) {
        $message['replyTo'] = [['emailAddress' => ['address' => $reply_to]]];
    }
    if ($cc_list) {
        $message['ccRecipients'] = array_map(fn($a) => ['emailAddress' => ['address' => $a]], $cc_list);
    }

    $resp = wp_remote_post(
        'https://graph.microsoft.com/v1.0/users/' . rawurlencode($from_addr) . '/sendMail',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(['message' => $message, 'saveToSentItems' => false]),
            'timeout' => 15,
        ]
    );

    if (is_wp_error($resp)) return false;
    $code = (int) wp_remote_retrieve_response_code($resp);
    if ($code === 401) delete_transient('sjioc_mail_token');
    return $code === 202;
}

// Intercept wp_mail() and route through Graph API when configured
add_filter('pre_wp_mail', function ($result, array $atts) {
    if ($result !== null || !sjioc_mail_is_configured()) return $result;

    $headers = (array) ($atts['headers'] ?? []);
    $is_html = false;
    foreach ($headers as $h) {
        if (stripos($h, 'text/html') !== false) { $is_html = true; break; }
    }
    $body = $is_html ? $atts['message'] : nl2br(esc_html($atts['message']));

    return sjioc_graph_send_mail($atts['to'], $atts['subject'], $body, $headers);
}, 10, 2);

/* ─────────────────────────────────────
   HELPER FUNCTIONS
───────────────────────────────────── */
function sjioc_get($key, $fallback = '') {
    return get_theme_mod($key, $fallback);
}
function sjioc_phone()   { return sjioc_get('sjioc_phone',       '(610) 822-0033'); }
function sjioc_email()   { return sjioc_get('sjioc_email',       'info@sjioc.org'); }
function sjioc_address() { return sjioc_get('sjioc_address',     '4400 State Road, Drexel Hill, PA 19026'); }
function sjioc_name()    { return sjioc_get('sjioc_church_name', "St. John's Indian Orthodox Church Of Delaware Valley"); }
function sjioc_abbr()    { return sjioc_get('sjioc_abbr',        'SJIOC'); }
function sjioc_qurbana() { return sjioc_get('sjioc_qurbana',     '9:30 AM'); }
function sjioc_school()  { return sjioc_get('sjioc_school',      '12:00 PM'); }
function sjioc_maps()    { return sjioc_get('sjioc_maps_url',    'https://share.google/zTkW7YSgj41LVTwW9'); }
function sjioc_fb()      { return sjioc_get('sjioc_facebook',    '#'); }
function sjioc_yt()      { return sjioc_get('sjioc_youtube',     '#'); }
function sjioc_ig()      { return sjioc_get('sjioc_instagram',   '#'); }
function sjioc_zoom()    { return sjioc_get('sjioc_zoom',        '#'); }
function sjioc_cal()     { return sjioc_get('sjioc_calendar_url','#'); }
function sjioc_morning_prayer()  { return sjioc_get('sjioc_morning_prayer',     '8:30 AM'); }
function sjioc_sat_evening()     { return sjioc_get('sjioc_saturday',           '6:00 PM'); }
function sjioc_first_wed_qurbana() { return sjioc_get('sjioc_first_wed_qurbana','6:00 PM'); }

function sjioc_get_worship_times() {
    return [
        ['label' => 'Saturday Evening Prayer',     'time' => sjioc_sat_evening()],
        ['label' => 'Sunday Morning Prayer',        'time' => sjioc_morning_prayer()],
        ['label' => 'Sunday Holy Qurbana',          'time' => sjioc_qurbana()],
        ['label' => 'Spiritual Organizations',      'time' => sjioc_school()],
        ['label' => 'First Wednesday Holy Qurbana', 'time' => sjioc_first_wed_qurbana()],
    ];
}

function sjioc_social_links_data($context) {
    $ig_grad_id = 'sjioc-ig-grad-' . $context;

    return [
        'facebook'  => [
            'url'   => sjioc_fb(),
            'label' => 'Facebook',
            'size'  => 18,
            'svg'   => '<rect width="24" height="24" rx="12" fill="#1877F2"/><path d="M16.3 12.5h-2.15V20h-3.06v-7.5H9.45V9.83h1.64V8.16c0-1.98.92-3.29 3.32-3.29h2.13v2.79h-1.34c-.9 0-.97.3-.97.98v1.19h2.35l-.28 2.67z" fill="#fff"/>',
        ],
        'instagram' => [
            'url'   => sjioc_ig(),
            'label' => 'Instagram',
            'size'  => 18,
            'svg'   => '<defs><linearGradient id="' . esc_attr($ig_grad_id) . '" x1="0" y1="24" x2="24" y2="0">'
                . '<stop offset="0" stop-color="#FED576"/><stop offset=".26" stop-color="#F47133"/>'
                . '<stop offset=".61" stop-color="#BC3081"/><stop offset="1" stop-color="#4C63D2"/></linearGradient></defs>'
                . '<rect width="24" height="24" rx="7" fill="url(#' . esc_attr($ig_grad_id) . ')"/>'
                . '<rect x="6.2" y="6.2" width="11.6" height="11.6" rx="3.6" fill="none" stroke="#fff" stroke-width="1.6"/>'
                . '<circle cx="12" cy="12" r="3.1" fill="none" stroke="#fff" stroke-width="1.6"/>'
                . '<circle cx="16.1" cy="7.9" r="1" fill="#fff"/>',
        ],
        'youtube'   => [
            'url'   => sjioc_yt(),
            'label' => 'YouTube',
            'size'  => 18,
            'svg'   => '<rect width="24" height="24" rx="7" fill="#FF0000"/><path d="M9.7 8.4l6.2 3.6-6.2 3.6z" fill="#fff"/>',
        ],
        'zoom'      => [
            'url'   => sjioc_zoom(),
            'label' => 'Zoom',
            'size'  => 18,
            'svg'   => '<rect width="24" height="24" rx="7" fill="#2D8CFF"/><rect x="4.3" y="8" width="10.4" height="8" rx="2" fill="#fff"/><path d="M15.9 10.5l3.6-2.3v7.6l-3.6-2.3z" fill="#fff"/>',
        ],
        'calendar'  => [
            'url'   => sjioc_cal(),
            'label' => 'Calendar',
            'size'  => 18,
            'svg'   => '<rect width="24" height="24" rx="7" fill="#C9A84C"/><rect x="5" y="6" width="14" height="13" rx="1.5" fill="none" stroke="#fff" stroke-width="1.6"/><line x1="5" y1="10" x2="19" y2="10" stroke="#fff" stroke-width="1.6"/><line x1="8.5" y1="4" x2="8.5" y2="7.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/><line x1="15.5" y1="4" x2="15.5" y2="7.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>',
        ],
    ];
}

function sjioc_social_icons($context = 'footer') {
    $links      = sjioc_social_links_data($context);
    $base_class = $context === 'header' ? 'nav-social-link' : 'social-link';
    foreach ($links as $l) {
        if (empty($l['url']) || $l['url'] === '#') continue;
        $class = $base_class . (empty($l['mono']) ? ' social-badge' : '');
        printf(
            '<a class="%s" href="%s" target="_blank" rel="noopener" aria-label="%s"><svg viewBox="0 0 24 24" width="%d" height="%d" aria-hidden="true">%s</svg></a>',
            esc_attr($class), esc_url($l['url']), esc_attr($l['label']), $l['size'], $l['size'], $l['svg']
        );
    }
}

/* ─────────────────────────────────────
   FOLLOW US PANEL ROWS — same brand icons as sjioc_social_icons(),
   rendered as a labeled list for the widget-bar "Follow" panel.
───────────────────────────────────── */
function sjioc_follow_rows() {
    $links = sjioc_social_links_data('panel');
    $any   = false;
    foreach ($links as $key => $l) {
        if (empty($l['url']) || $l['url'] === '#') continue;
        $any = true;
        printf(
            '<a class="follow-row" href="%s" target="_blank" rel="noopener"><span class="follow-icon"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">%s</svg></span><span class="follow-label">%s</span><span class="follow-arrow" aria-hidden="true">&rarr;</span></a>',
            esc_url($l['url']), $l['svg'], esc_html($l['label'])
        );
    }
    if (!$any) {
        echo '<p style="padding:20px 16px;color:#888;text-align:center;font-size:13px">Social links not set up yet.<br><span style="font-size:11px;color:var(--tl)">Add them in Appearance → Customize → Church Information</span></p>';
    }
}

/* ─────────────────────────────────────
   FOOTER HTML
───────────────────────────────────── */
function sjioc_footer() { ?>
<footer id="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <?php
        // Dedicated footer logo → site logo → SVG fallback
        $fl_att = get_theme_mod('sjioc_footer_logo');
        $fl_url = $fl_att ? wp_get_attachment_image_url($fl_att, 'medium') : '';
        if (!$fl_url) {
            $fl_id  = get_theme_mod('custom_logo');
            $fl_url = $fl_id ? wp_get_attachment_image_url($fl_id, 'medium') : '';
        }
        ?>
        <?php if ($fl_url): ?>
          <img src="<?php echo esc_url($fl_url); ?>" alt="<?php echo esc_attr(sjioc_name()); ?>" class="footer-logo">
        <?php else: ?>
          <svg class="footer-logo-svg" viewBox="0 0 46 46" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <circle cx="23" cy="23" r="21" fill="none" stroke="#C9A84C" stroke-width="1.5"/>
            <line x1="23" y1="4"  x2="23" y2="42" stroke="#C9A84C" stroke-width="2.6"/>
            <line x1="8"  y1="15" x2="38" y2="15" stroke="#C9A84C" stroke-width="2.6"/>
            <line x1="12" y1="25" x2="34" y2="25" stroke="#C9A84C" stroke-width="1.5"/>
            <circle cx="23" cy="23" r="2.6" fill="#C9A84C" opacity=".5"/>
          </svg>
        <?php endif; ?>
        <div class="footer-lockup">
          <span class="footer-lockup-name"><?php echo esc_html(sjioc_abbr() ?: 'SJIOC'); ?></span>
          <span class="footer-lockup-rule"></span>
          <span class="footer-lockup-tag">Faith &bull; Community &bull; Service</span>
        </div>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Service Times</span>
        <?php foreach (sjioc_get_worship_times() as $wt): ?>
        <p><?php echo esc_html($wt['label']); ?>: <strong style="color:var(--go);white-space:nowrap"><?php echo esc_html($wt['time']); ?></strong></p>
        <?php endforeach; ?>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Quick Links</span>
        <?php wp_nav_menu(['theme_location'=>'footer','container'=>false,'fallback_cb'=>'sjioc_footer_links']); ?>
      </div>
      <div class="footer-col">
        <span class="footer-col-title">Contact Us</span>
        <p class="footer-contact-row"><svg class="footer-contact-icon" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" fill="var(--go)"/></svg> <?php echo esc_html(sjioc_address()); ?></p>
        <p class="footer-contact-row"><svg class="footer-contact-icon" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.86 11a19.79 19.79 0 01-3.07-8.67A2 2 0 012.77 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 8.59a16 16 0 006.5 6.5l1.95-1.35a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" fill="var(--go)"/></svg> <a href="tel:<?php echo preg_replace('/\D/', '', sjioc_phone()); ?>"><?php echo esc_html(sjioc_phone()); ?></a></p>
        <p class="footer-contact-row"><svg class="footer-contact-icon" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="var(--go)"/></svg> <a href="mailto:<?php echo esc_attr(sjioc_email()); ?>"><?php echo esc_html(sjioc_email()); ?></a></p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(sjioc_name()); ?>. All rights reserved. Hosted on <span style="color:rgba(255,255,255,.5)">Microsoft Azure</span>.</p>
      <div class="social-links">
        <?php sjioc_social_icons('footer'); ?>
      </div>
    </div>
  </div>
</footer>
<?php }

function sjioc_footer_links() {
    $pages = [
        home_url('/')                  => 'Home',
        home_url('/about-us/')         => 'About Us',
        home_url('/worship-services/') => 'Worship & Services',
        home_url('/ministries/')       => 'Ministries',
        home_url('/events/')           => 'Events',
        home_url('/photos/')           => 'Parish Life',
        home_url('/outreach/')         => 'Outreach',
        home_url('/contact-us/')       => 'Contact',
        home_url('/give/')             => 'Support Us',
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
   FALLBACK PRIMARY NAV
───────────────────────────────────── */
function sjioc_primary_nav_fallback() {
    $current = get_permalink();
    $about   = home_url('/about-us/');
    $contact = home_url('/contact-us/');

    $pages = [
        home_url('/')                  => 'Home',
        $about                         => 'About',
        home_url('/worship-services/') => 'Worship',
        home_url('/ministries/')       => 'Ministries',
        home_url('/events/')           => 'Events',
        home_url('/photos/')           => 'Parish Life',
        home_url('/outreach/')         => 'Outreach',
        $contact                       => 'Contact',
    ];

    $about_children = [
        $about . '#our-story'   => 'Our Story',
        $about . '#core-values' => 'Core Values',
        $about . '#leadership'  => 'Leadership',
        $about . '#committees'  => 'Committees',
        $about . '#history'     => 'Our History',
    ];

    $contact_children = [
        $contact                      => 'Contact Us',
        home_url('/hall-rental/')     => 'MBM Hall Rental',
        home_url('/#new-to-sjioc')   => 'New to SJIOC',
    ];

    echo '<ul id="primary-menu">';
    foreach ($pages as $url => $label) {
        $is_about   = ($url === $about);
        $is_contact = ($url === $contact);
        $is_current = ($current === $url);
        $cls = [];
        if ($is_current)              $cls[] = 'current_page_item';
        if ($is_about || $is_contact) $cls[] = 'menu-item-has-children';
        $attr = $cls ? ' class="' . implode(' ', $cls) . '"' : '';

        echo '<li' . $attr . '>';
        echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';

        if ($is_about) {
            echo '<ul class="sub-menu">';
            foreach ($about_children as $curl => $clabel) {
                echo '<li><a href="' . esc_url($curl) . '">' . esc_html($clabel) . '</a></li>';
            }
            echo '</ul>';
        }

        if ($is_contact) {
            echo '<ul class="sub-menu">';
            foreach ($contact_children as $curl => $clabel) {
                echo '<li><a href="' . esc_url($curl) . '">' . esc_html($clabel) . '</a></li>';
            }
            echo '</ul>';
        }

        echo '</li>';
    }
    echo '</ul>';
}
