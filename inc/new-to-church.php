<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_nopriv_sjioc_new_to_church', 'sjioc_handle_new_to_church');
add_action('wp_ajax_sjioc_new_to_church',        'sjioc_handle_new_to_church');

function sjioc_handle_new_to_church(): void {
    check_ajax_referer('sjioc_ntc', 'nonce');

    $rc_token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
    if (!sjioc_recaptcha_verify($rc_token, 'new_to_church', 0.5)) {
        wp_send_json_error(['msg' => 'Security check failed. Please refresh and try again.']);
    }

    $fname  = sanitize_text_field($_POST['ntc_fname']   ?? '');
    $lname  = sanitize_text_field($_POST['ntc_lname']   ?? '');
    $phone  = sanitize_text_field($_POST['ntc_phone']   ?? '');
    $addr   = sanitize_textarea_field($_POST['ntc_address']  ?? '');
    $visit  = sanitize_text_field($_POST['ntc_visit']   ?? '');
    $count  = abs((int)($_POST['ntc_family']            ?? 0));
    $kerala = sanitize_text_field($_POST['ntc_kerala']  ?? '');
    $parish = sanitize_text_field($_POST['ntc_parish']  ?? '');
    $call   = sanitize_text_field($_POST['ntc_call']    ?? '');

    if (!$fname || !$lname || !$phone) {
        wp_send_json_error(['msg' => 'Please fill in your name and phone number.']);
    }

    $recipients = array_unique(array_filter([
        sjioc_get('sjioc_email_secretary', ''),
        sjioc_get('sjioc_email_vicar',     ''),
        sjioc_get('sjioc_email',           'info@sjioc.org'),
    ]));

    $rows = [
        'Name'               => trim($fname . ' ' . $lname),
        'Phone'              => $phone,
        'Address'            => $addr   ?: '—',
        'Planning to Visit Church'  => $visit  ?: 'Not specified',
        'Family Members'     => $count  ?: '—',
        'Location in Kerala' => $kerala ?: '—',
        'Family Parish'      => $parish ?: '—',
        'Best Time to Call'  => $call   ?: '—',
    ];

    $body  = '<html><body style="font-family:Georgia,serif;color:#3d2b1a;background:#faf6f0;margin:0;padding:24px">';
    $body .= '<div style="max-width:560px;margin:0 auto;background:#fff;border-top:4px solid #7B1818;padding:28px 32px;border-radius:4px">';
    $body .= '<h2 style="color:#5C1010;margin:0 0 4px">&#128139; New to SJIOC — Visitor Inquiry</h2>';
    $body .= '<p style="color:#888;font-size:.82rem;margin:0 0 20px">Received: ' . date('D, F j, Y g:i A') . '</p>';
    $body .= '<table style="width:100%;border-collapse:collapse;font-size:.88rem">';
    foreach ($rows as $label => $value) {
        $body .= '<tr><th style="text-align:left;padding:8px 10px;background:#faf6f0;color:#5C1010;width:38%;border-bottom:1px solid #eee">' . esc_html($label) . '</th>'
               . '<td style="padding:8px 10px;border-bottom:1px solid #eee">' . nl2br(esc_html($value)) . '</td></tr>';
    }
    $body .= '</table></div></body></html>';

    $subject = 'New to SJIOC — ' . esc_html(trim($fname . ' ' . $lname));
    foreach ($recipients as $to) {
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    wp_send_json_success(['msg' => 'Thank you, ' . esc_html($fname) . '! We will reach out to you soon. God bless you.']);
}
