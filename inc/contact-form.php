<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   AJAX: Contact Form
───────────────────────────────────── */
function sjioc_handle_contact() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    // Honeypot — bots fill hidden fields; humans never see them
    if (!empty($_POST['cf_hp'])) {
        wp_send_json_error(['msg' => 'Submission rejected.']);
    }

    // reCAPTCHA v3
    $rc_token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
    if (!sjioc_recaptcha_verify($rc_token, 'contact')) {
        wp_send_json_error(['msg' => 'Security check failed. Please refresh the page and try again.']);
    }

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
