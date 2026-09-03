<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CONTACT FORM SUBJECTS — admin-configurable list of
   {label, email} pairs. Stored as one option (JSON), edited via a
   plain textarea (Settings → Contact Form) — one "Label | email" per
   line, same pattern as the Vicar Timeline field. Falls back to the
   original hardcoded 9-subject list (unchanged behavior) until an
   admin actually saves something.
───────────────────────────────────── */
function sjioc_contact_subjects_default() {
    return [
        ['label' => 'Contact the Vicar',     'email' => sjioc_get('sjioc_email_vicar',     sjioc_email())],
        ['label' => 'Contact the Trustee',   'email' => sjioc_get('sjioc_email_trustee',   sjioc_email())],
        ['label' => 'Contact the Secretary', 'email' => sjioc_get('sjioc_email_secretary', sjioc_email())],
        ['label' => 'General Inquiry',       'email' => sjioc_email()],
        ['label' => 'Prayer Request',        'email' => sjioc_email()],
        ['label' => 'Baptism / Marriage',    'email' => sjioc_email()],
        ['label' => 'Ministry Information',  'email' => sjioc_email()],
        ['label' => 'Pastoral Counseling',   'email' => sjioc_email()],
        ['label' => 'Other',                 'email' => sjioc_email()],
    ];
}

function sjioc_contact_subjects() {
    $raw  = get_option('sjioc_contact_subjects', '');
    $rows = $raw !== '' ? json_decode($raw, true) : null;
    return (is_array($rows) && $rows) ? $rows : sjioc_contact_subjects_default();
}

function sjioc_contact_subject_options_html() {
    $html = '<option value="">Select a subject…</option>';
    foreach (sjioc_contact_subjects() as $s) {
        if (empty($s['label'])) continue;
        $html .= '<option value="' . esc_attr($s['label']) . '">' . esc_html($s['label']) . '</option>';
    }
    return $html;
}

function sjioc_contact_subjects_admin_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['sjioc_contact_subjects_save']) && check_admin_referer('sjioc_contact_subjects_save')) {
        $lines = preg_split('/\r\n|\r|\n/', (string) wp_unslash($_POST['cs_lines'] ?? ''));
        $rows  = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = explode('|', $line, 3);
            $label = sanitize_text_field(trim($parts[0] ?? ''));
            $email = sanitize_email(trim($parts[1] ?? ''));
            if ($label === '' || !is_email($email)) continue;
            $cc_addrs = array_filter(array_map('trim', explode(',', $parts[2] ?? '')));
            $cc_addrs = array_filter(array_map('sanitize_email', $cc_addrs), 'is_email');
            $rows[] = ['label' => $label, 'email' => $email, 'cc' => implode(', ', $cc_addrs)];
        }
        update_option('sjioc_contact_subjects', wp_json_encode($rows));
        echo '<div class="notice notice-success is-dismissible"><p>Contact form subjects saved.</p></div>';
    }

    $text = implode("\n", array_map(
        fn($s) => ($s['label'] ?? '') . ' | ' . ($s['email'] ?? '') . (!empty($s['cc']) ? ' | ' . $s['cc'] : ''),
        sjioc_contact_subjects()
    ));
    ?>
    <div class="wrap">
        <h1>Contact Form Subjects</h1>
        <p>One subject per line, in the format <code>Subject Label | destination@email.com</code> — add a third part for CC, comma-separated if more than one: <code>Subject Label | destination@email.com | cc1@email.com, cc2@email.com</code>. CC is optional; leave it off if not needed.</p>
        <p>This list drives the "Subject" dropdown on the Contact Us page — each submission is emailed to the address on its matching line.</p>
        <form method="post">
            <?php wp_nonce_field('sjioc_contact_subjects_save'); ?>
            <textarea name="cs_lines" rows="12" style="width:100%;max-width:640px;font-family:monospace"><?php echo esc_textarea($text); ?></textarea>
            <p><button type="submit" name="sjioc_contact_subjects_save" value="1" class="button button-primary">Save Subjects</button></p>
        </form>
    </div>
    <?php
}

/* ─────────────────────────────────────
   AJAX: Contact Form
───────────────────────────────────── */
function sjioc_handle_contact() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    // Honeypot — bots fill hidden fields; humans never see them
    if (!empty($_POST['cf_hp'])) {
        wp_send_json_error(['msg' => 'Submission rejected.']);
    }

    // Rate limit — caps how many emails one IP can trigger, independent of reCAPTCHA
    $ip_key = 'sjioc_rl_contact_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits   = (int) get_transient($ip_key);
    if ($hits >= 3) {
        wp_send_json_error(['msg' => 'Too many messages sent. Please wait 10 minutes before trying again, or call us directly.']);
    }
    set_transient($ip_key, $hits + 1, 10 * MINUTE_IN_SECONDS);

    // reCAPTCHA v3
    $rc_token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
    if (!sjioc_recaptcha_verify($rc_token, 'contact')) {
        wp_send_json_error(['msg' => 'Security check failed. Please refresh the page and try again.']);
    }

    $fname   = sanitize_text_field(wp_unslash($_POST['fname']   ?? ''));
    $lname   = sanitize_text_field(wp_unslash($_POST['lname']   ?? ''));
    $email   = sanitize_email($_POST['email']         ?? '');
    $phone   = sanitize_text_field(wp_unslash($_POST['phone']   ?? ''));
    $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

    if (empty($fname) || empty($email) || empty($message)) {
        wp_send_json_error(['msg' => __('Please fill in your name, email, and message.', 'sjioc')]);
    }

    if (!is_email($email)) {
        wp_send_json_error(['msg' => __('Please enter a valid email address.', 'sjioc')]);
    }

    $to = sjioc_email();
    $cc = '';
    foreach (sjioc_contact_subjects() as $s) {
        if (($s['label'] ?? '') === $subject && !empty($s['email'])) {
            $to = $s['email'];
            $cc = $s['cc'] ?? '';
            break;
        }
    }
    $headers = ['Content-Type: text/html; charset=UTF-8', "Reply-To: {$email}"];
    if ($cc) $headers[] = "Cc: {$cc}";
    $body    = "<p><strong>From:</strong> " . esc_html("{$fname} {$lname}") . "</p>
                <p><strong>Email:</strong> " . esc_html($email) . "</p>
                <p><strong>Phone:</strong> " . esc_html($phone) . "</p>
                <p><strong>Subject:</strong> " . esc_html($subject) . "</p><hr>
                <p><strong>Message:</strong></p><p>" . nl2br(esc_html($message)) . "</p>";

    $sent = wp_mail($to, "SJIOC Website Contact: {$subject}", $body, $headers);

    if ($sent) {
        wp_send_json_success(['msg' => __("Thank you! Your message has been sent. We'll be in touch soon.", 'sjioc')]);
    } else {
        wp_send_json_error(['msg' => __('Sorry, there was an issue sending your message. Please call us directly.', 'sjioc')]);
    }
}
add_action('wp_ajax_sjioc_contact',        'sjioc_handle_contact');
add_action('wp_ajax_nopriv_sjioc_contact', 'sjioc_handle_contact');
