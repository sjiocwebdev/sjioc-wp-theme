<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   DB TABLE
───────────────────────────────────── */
function sjioc_rental_create_table(): void {
    global $wpdb;
    $table   = $wpdb->prefix . 'sjioc_rentals';
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$table} (
        id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name       VARCHAR(80)  NOT NULL,
        last_name        VARCHAR(80)  NOT NULL DEFAULT '',
        email            VARCHAR(120) NOT NULL,
        phone            VARCHAR(30)  NOT NULL DEFAULT '',
        address          TEXT         NOT NULL,
        member_status    VARCHAR(20)  NOT NULL DEFAULT 'non-member',
        org_name         VARCHAR(200) NOT NULL DEFAULT '',
        recommended_by   VARCHAR(200) NOT NULL DEFAULT '',
        event_type       VARCHAR(120) NOT NULL,
        event_date       DATE         NOT NULL,
        start_time       TIME         NOT NULL,
        end_time         TIME         NOT NULL,
        setup_date       DATE                  DEFAULT NULL,
        setup_start_time TIME         NOT NULL DEFAULT '18:30:00',
        setup_end_time   TIME         NOT NULL DEFAULT '22:00:00',
        guests           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        event_purpose    TEXT         NOT NULL,
        use_kitchen      TINYINT(1)   NOT NULL DEFAULT 0,
        use_av           TINYINT(1)   NOT NULL DEFAULT 0,
        need_tables      TINYINT(1)   NOT NULL DEFAULT 0,
        use_projector    TINYINT(1)   NOT NULL DEFAULT 0,
        catering         VARCHAR(20)  NOT NULL DEFAULT 'none',
        special_req      TEXT         NOT NULL,
        signature        VARCHAR(200) NOT NULL DEFAULT '',
        status           VARCHAR(20)  NOT NULL DEFAULT 'pending',
        admin_notes      TEXT         NOT NULL,
        payment_method   VARCHAR(20)  NOT NULL DEFAULT '',
        accepted_by      VARCHAR(200) NOT NULL DEFAULT '',
        receipt_no       VARCHAR(50)  NOT NULL DEFAULT '',
        payment_date     DATE                  DEFAULT NULL,
        booking_amount   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        deposit_amount   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        od_folder_url    VARCHAR(600) NOT NULL DEFAULT '',
        created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY      (id),
        KEY idx_date     (event_date),
        KEY idx_status   (status(12)),
        KEY idx_email    (email(20))
    ) {$charset};");
}
add_action('after_switch_theme', 'sjioc_rental_create_table');

// Create table on first load if it doesn't exist yet (handles adding hall-rental.php mid-deployment)
add_action('init', function () {
    if (get_option('sjioc_rentals_db_ver') === '2') return;
    sjioc_rental_create_table();
    update_option('sjioc_rentals_db_ver', '2');
});

/* ─────────────────────────────────────
   AJAX: Submit Rental Request (public)
───────────────────────────────────── */
function sjioc_handle_rental_request(): void {
    check_ajax_referer('sjioc_ajax', 'nonce');

    // Honeypot
    if (!empty($_POST['rf_hp'])) {
        wp_send_json_error(['msg' => 'Submission rejected.']);
    }

    // reCAPTCHA v3
    $rc_token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
    if (!sjioc_recaptcha_verify($rc_token, 'hall_rental')) {
        wp_send_json_error(['msg' => 'Security check failed. Please refresh the page and try again.']);
    }

    $fname          = sanitize_text_field($_POST['fname']          ?? '');
    $lname          = sanitize_text_field($_POST['lname']          ?? '');
    $email          = sanitize_email(     $_POST['email']          ?? '');
    $phone          = sanitize_text_field($_POST['phone']          ?? '');
    $address        = sanitize_textarea_field($_POST['address']    ?? '');
    $member         = in_array($_POST['member_status'] ?? '', ['member','non-member'], true)
                      ? $_POST['member_status'] : 'non-member';
    $org_name       = sanitize_text_field($_POST['org_name']       ?? '');
    $recommended_by = sanitize_text_field($_POST['recommended_by'] ?? '');
    $evtype         = sanitize_text_field($_POST['event_type']     ?? '');
    $evdate         = sanitize_text_field($_POST['event_date']     ?? '');
    $stime          = sanitize_text_field($_POST['start_time']     ?? '');
    $etime          = sanitize_text_field($_POST['end_time']       ?? '');
    $setup_date     = sanitize_text_field($_POST['setup_date']     ?? '');
    $setup_stime    = sanitize_text_field($_POST['setup_start_time'] ?? '18:30');
    $setup_etime    = sanitize_text_field($_POST['setup_end_time']   ?? '22:00');
    $guests         = abs((int) ($_POST['guests']                  ?? 0));
    $purpose        = sanitize_textarea_field($_POST['event_purpose'] ?? '');
    $catering       = in_array($_POST['catering'] ?? '', ['none','self','outside'], true)
                      ? $_POST['catering'] : 'none';
    $special        = sanitize_textarea_field($_POST['special_req'] ?? '');
    $sig            = sanitize_text_field($_POST['signature']      ?? '');
    $booking_amount = (float) sjioc_get('sjioc_hall_booking_amount', '650');
    $deposit_amount = (float) sjioc_get('sjioc_hall_deposit_amount', '100');

    if (!$fname || !$email || !$evtype || !$evdate || !$stime || !$etime || !$guests || !$purpose || !$sig) {
        wp_send_json_error(['msg' => 'Please complete all required fields.']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['msg' => 'Please enter a valid email address.']);
    }

    $min_date = date('Y-m-d', strtotime('+3 days'));
    if ($evdate < $min_date) {
        wp_send_json_error(['msg' => 'Rental requests must be submitted at least 3 days in advance.']);
    }
    if ($etime <= $stime) {
        wp_send_json_error(['msg' => 'End time must be after start time.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_rentals';

    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table}
         WHERE event_date = %s
           AND status NOT IN ('rejected','cancelled')
           AND start_time < %s AND end_time > %s",
        $evdate, $etime, $stime
    ));
    if ($conflict) {
        wp_send_json_error(['msg' => 'That date and time is already booked. Please choose a different date or time.']);
    }

    $inserted = $wpdb->insert($table, [
        'first_name'       => $fname,
        'last_name'        => $lname,
        'email'            => $email,
        'phone'            => $phone,
        'address'          => $address,
        'member_status'    => $member,
        'org_name'         => $org_name,
        'recommended_by'   => $recommended_by,
        'event_type'       => $evtype,
        'event_date'       => $evdate,
        'start_time'       => $stime,
        'end_time'         => $etime,
        'setup_date'       => $setup_date ?: null,
        'setup_start_time' => $setup_stime ?: '18:30:00',
        'setup_end_time'   => $setup_etime ?: '22:00:00',
        'guests'           => $guests,
        'event_purpose'    => $purpose,
        'catering'         => $catering,
        'special_req'      => $special,
        'signature'        => $sig,
        'status'           => 'pending',
        'admin_notes'      => '',
        'booking_amount'   => $booking_amount,
        'deposit_amount'   => $deposit_amount,
    ]);

    if (!$inserted) {
        wp_send_json_error(['msg' => 'Failed to save your request. Please try again or call us directly.']);
    }

    $rental_id = (int) $wpdb->insert_id;

    $d = [
        'fname' => $fname, 'lname' => $lname, 'email' => $email,
        'phone' => $phone, 'address' => $address, 'member' => $member,
        'org_name' => $org_name, 'recommended_by' => $recommended_by,
        'evtype' => $evtype, 'evdate' => $evdate, 'stime' => $stime, 'etime' => $etime,
        'setup_date' => $setup_date, 'setup_stime' => $setup_stime, 'setup_etime' => $setup_etime,
        'guests' => $guests, 'purpose' => $purpose,
        'catering' => $catering, 'special' => $special,
        'sig' => $sig, 'booking_amount' => $booking_amount, 'deposit_amount' => $deposit_amount,
    ];

    $od_file_url = sjioc_rental_upload_summary($rental_id, $d);
    if ($od_file_url) {
        $wpdb->update($table, ['od_folder_url' => $od_file_url], ['id' => $rental_id]);
    }

    $d['od_url'] = $od_file_url;
    sjioc_rental_notify_staff($rental_id, $d);

    sjioc_rental_send_confirmation($rental_id, $fname, $email, $evtype, $evdate, $stime, $etime);

    wp_send_json_success(['msg' => 'Your rental request has been submitted. We will contact you within 2–3 business days.']);
}
add_action('wp_ajax_sjioc_rental_request',        'sjioc_handle_rental_request');
add_action('wp_ajax_nopriv_sjioc_rental_request', 'sjioc_handle_rental_request');

/* ─────────────────────────────────────
   AJAX: Update Status (admin-only)
───────────────────────────────────── */
function sjioc_rental_update_status(): void {
    check_ajax_referer('sjioc_rentals_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $id             = (int) ($_POST['rental_id']     ?? 0);
    $status         = sanitize_text_field($_POST['status']       ?? '');
    $notes          = sanitize_textarea_field($_POST['admin_notes'] ?? '');
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $accepted_by    = sanitize_text_field($_POST['accepted_by']    ?? '');
    $receipt_no     = sanitize_text_field($_POST['receipt_no']     ?? '');
    $payment_date   = sanitize_text_field($_POST['payment_date']   ?? '');
    $booking_amount = (float) ($_POST['booking_amount']            ?? 0);
    $deposit_amount = (float) ($_POST['deposit_amount']            ?? 0);

    $allowed = ['pending','approved','rejected','cancelled'];
    if (!$id || !in_array($status, $allowed, true)) {
        wp_send_json_error(['msg' => 'Invalid data.']);
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'sjioc_rentals';
    $rental = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    if (!$rental) wp_send_json_error(['msg' => 'Rental not found.']);

    $wpdb->update($table, [
        'status'         => $status,
        'admin_notes'    => $notes,
        'payment_method' => $payment_method,
        'accepted_by'    => $accepted_by,
        'receipt_no'     => $receipt_no,
        'payment_date'   => $payment_date ?: null,
        'booking_amount' => $booking_amount,
        'deposit_amount' => $deposit_amount,
    ], ['id' => $id]);

    // Re-upload HTML summary to SharePoint with office info filled in
    $office = [
        'payment_method' => $payment_method, 'accepted_by' => $accepted_by,
        'receipt_no'     => $receipt_no,     'payment_date' => $payment_date,
        'booking_amount' => $booking_amount, 'deposit_amount' => $deposit_amount,
    ];
    $d = [
        'fname' => $rental->first_name, 'lname' => $rental->last_name,
        'email' => $rental->email,      'phone' => $rental->phone,
        'address' => $rental->address,  'member' => $rental->member_status,
        'org_name' => $rental->org_name,  'recommended_by' => $rental->recommended_by,
        'evtype' => $rental->event_type,  'evdate' => $rental->event_date,
        'stime'  => $rental->start_time,  'etime'  => $rental->end_time,
        'setup_date' => $rental->setup_date, 'setup_stime' => $rental->setup_start_time, 'setup_etime' => $rental->setup_end_time,
        'guests' => $rental->guests,    'purpose' => $rental->event_purpose,
        'catering' => $rental->catering,   'special' => $rental->special_req,
        'sig' => $rental->signature,
        'booking_amount' => $rental->booking_amount ?: $booking_amount,
        'deposit_amount' => $rental->deposit_amount ?: $deposit_amount,
    ];
    $new_url = sjioc_rental_upload_summary($id, $d, $office);
    if ($new_url) {
        $wpdb->update($table, ['od_folder_url' => $new_url], ['id' => $id]);
    }

    if (in_array($status, ['approved','rejected','cancelled'], true)) {
        sjioc_rental_notify_requester($rental, $status, $notes);
    }

    wp_send_json_success(['msg' => 'Status updated to <strong>' . esc_html($status) . '</strong>.']);
}
add_action('wp_ajax_sjioc_rental_update_status', 'sjioc_rental_update_status');

/* ─────────────────────────────────────
   EMAIL HELPERS
───────────────────────────────────── */

function sjioc_rental_email_header(string $title, string $subtitle = '', string $badge = ''): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:Arial,sans-serif;background:#f5f0ea;margin:0;padding:20px}
  .wrap{max-width:640px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12)}
  .hdr{background:#5c1010;padding:26px 32px;text-align:center}
  .hdr h1{color:#c9a84c;font-size:20px;margin:0 0 6px;font-family:Georgia,serif;line-height:1.3}
  .hdr p{color:rgba(255,255,255,.72);font-size:13px;margin:0}
  .badge{display:inline-block;background:#c9a84c;color:#5c1010;font-weight:700;font-size:11px;padding:3px 12px;border-radius:20px;letter-spacing:.05em;text-transform:uppercase;margin-top:10px}
  .body{padding:28px 32px}
  .sec-label{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#7b1818;font-weight:700;margin:20px 0 8px;border-bottom:1px solid #f0e8dc;padding-bottom:6px}
  table{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:14px}
  th{text-align:left;color:#7b1818;background:#faf6f0;padding:8px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em}
  td{padding:9px 12px;color:#3d2b1a;border-bottom:1px solid #f5ede3;vertical-align:top}
  .cta{display:block;background:#7b1818;color:#fff !important;text-decoration:none;padding:13px 28px;border-radius:4px;font-weight:700;font-size:14px;text-align:center;margin:20px 0}
  .sp-btn{display:block;background:#faf6f0;border:1px solid #ddd0bf;border-radius:4px;padding:11px 16px;font-size:13px;color:#5c1010 !important;text-decoration:none;margin-bottom:12px}
  .ftr{background:#faf6f0;padding:14px 32px;font-size:11px;color:#6b5744;text-align:center;border-top:1px solid #e8dfd3}
</style></head><body><div class="wrap">
<div class="hdr">
  <h1>' . $title . '</h1>
  ' . ($subtitle ? '<p>' . $subtitle . '</p>' : '') . '
  ' . ($badge ? '<div><span class="badge">' . $badge . '</span></div>' : '') . '
</div><div class="body">';
}

function sjioc_rental_email_footer(): string {
    return '</div><div class="ftr">' . esc_html(sjioc_name()) . ' · ' . esc_html(sjioc_address()) . ' · This is an automated message.</div></div></body></html>';
}

function sjioc_rental_notify_staff(int $id, array $d): void {
    $recipients = array_unique(array_filter([
        sjioc_get('sjioc_email_vicar',     'info@sjioc.org'),
        sjioc_get('sjioc_email_trustee',   'info@sjioc.org'),
        sjioc_get('sjioc_email_secretary', 'info@sjioc.org'),
    ]));

    $admin_url  = admin_url('admin.php?page=sjioc-rentals&view=' . $id);
    $sp_link    = sjioc_get('sjioc_rentals_sp_link', '');
    $cat_map    = ['none' => 'No catering', 'self' => 'Self-catered', 'outside' => 'Outside caterer/vendor'];

    $body  = sjioc_rental_email_header(
        '&#127963; Hall Rental Request — ' . esc_html(sjioc_name()),
        'A new inquiry has been submitted through the website.',
        'Request #' . str_pad($id, 4, '0', STR_PAD_LEFT) . ' &middot; Pending Review'
    );

    $setup_row = '';
    if (!empty($d['setup_date'])) {
        $setup_row = '<tr><th>Setup Day</th><td>'
            . esc_html(date('l, F j, Y', strtotime($d['setup_date'])))
            . ' &nbsp;' . esc_html($d['setup_stime']) . ' &ndash; ' . esc_html($d['setup_etime'])
            . '</td></tr>';
    }

    $body .= '<p class="sec-label">Applicant Information</p>
    <table>
      <tr><th>Name</th><td>' . esc_html($d['fname'] . ' ' . $d['lname']) . '</td></tr>
      <tr><th>Email</th><td><a href="mailto:' . esc_attr($d['email']) . '">' . esc_html($d['email']) . '</a></td></tr>
      <tr><th>Phone</th><td>' . esc_html($d['phone'] ?: '—') . '</td></tr>
      <tr><th>Address</th><td>' . esc_html($d['address'] ?: '—') . '</td></tr>
      <tr><th>Member Status</th><td>' . ($d['member'] === 'member' ? '&#10003; Church Member' : '&#9888; Non-Member') . '</td></tr>
      <tr><th>Organization</th><td>' . esc_html($d['org_name'] ?: '—') . '</td></tr>
      <tr><th>Recommended By</th><td>' . esc_html($d['recommended_by'] ?: '—') . '</td></tr>
    </table>
    <p class="sec-label">Event Details</p>
    <table>
      <tr><th>Event Type</th><td>' . esc_html($d['evtype']) . '</td></tr>
      <tr><th>Date</th><td>' . esc_html(date('l, F j, Y', strtotime($d['evdate']))) . '</td></tr>
      <tr><th>Time</th><td>' . esc_html($d['stime']) . ' &ndash; ' . esc_html($d['etime']) . '</td></tr>
      ' . $setup_row . '
      <tr><th>Expected Guests</th><td>' . esc_html($d['guests']) . '</td></tr>
      <tr><th>Description</th><td>' . nl2br(esc_html($d['purpose'])) . '</td></tr>
    </table>
    <p class="sec-label">Additional Details</p>
    <table>
      <tr><th>Catering</th><td>' . esc_html($cat_map[$d['catering']] ?? $d['catering']) . '</td></tr>
      <tr><th>Special Requests</th><td>' . nl2br(esc_html($d['special'] ?: '—')) . '</td></tr>
    </table>
    <p class="sec-label">Digital Signature</p>
    <p style="font-style:italic;color:#3d2b1a;margin-bottom:22px;padding:10px 14px;background:#faf6f0;border-left:3px solid #c9a84c">&ldquo;' . esc_html($d['sig']) . '&rdquo; &mdash; agreed to all rental terms and conditions.</p>
    <a href="' . esc_url($admin_url) . '" class="cta">View &amp; Manage This Request in WordPress Admin &rarr;</a>';

    if ($sp_link) {
        $body .= '<a href="' . esc_url($sp_link) . '" class="sp-btn">&#128193; Open Hall Rentals Folder in SharePoint &rarr;</a>';
    }
    if (!empty($d['od_url'])) {
        $body .= '<a href="' . esc_url($d['od_url']) . '" class="sp-btn">&#128196; View Uploaded Summary File in OneDrive &rarr;</a>';
    }

    $body .= sjioc_rental_email_footer();

    $subject = 'Hall Rental Request #' . str_pad($id, 4, '0', STR_PAD_LEFT)
             . ' — ' . $d['evtype'] . ' · ' . date('M j, Y', strtotime($d['evdate']));

    foreach ($recipients as $to) {
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
}

function sjioc_rental_send_confirmation(int $id, string $fname, string $email, string $evtype, string $evdate, string $stime, string $etime): void {
    $ref = str_pad($id, 4, '0', STR_PAD_LEFT);

    $body  = sjioc_rental_email_header('&#10003; Request Received', 'Thank you for your Hall Rental inquiry');
    $body .= '<p>Dear ' . esc_html($fname) . ',</p>
    <p>We have received your hall rental request and our team will review it shortly. You should hear back within <strong>2&ndash;3 business days</strong>.</p>
    <table style="margin:20px 0;background:#faf6f0">
      <tr><th>Event</th><td>' . esc_html($evtype) . '</td></tr>
      <tr><th>Date</th><td>' . esc_html(date('l, F j, Y', strtotime($evdate))) . '</td></tr>
      <tr><th>Time</th><td>' . esc_html($stime) . ' &ndash; ' . esc_html($etime) . '</td></tr>
      <tr><th>Reference #</th><td><strong>' . esc_html($ref) . '</strong></td></tr>
    </table>
    <p>Please save your reference number <strong>#' . esc_html($ref) . '</strong> for future correspondence.</p>
    <p>If you have any questions, reach us at <a href="mailto:' . esc_attr(sjioc_get('sjioc_email','info@sjioc.org')) . '">' . esc_html(sjioc_get('sjioc_email','info@sjioc.org')) . '</a> or <a href="tel:' . preg_replace('/\D','',sjioc_phone()) . '">' . esc_html(sjioc_phone()) . '</a>.</p>
    <p>God bless you,<br><strong>' . esc_html(sjioc_name()) . '</strong></p>';
    $body .= sjioc_rental_email_footer();

    wp_mail($email, 'Hall Rental Request Received — Ref #' . $ref, $body, ['Content-Type: text/html; charset=UTF-8']);
}

function sjioc_rental_notify_requester(object $r, string $status, string $notes): void {
    $map = [
        'approved'  => ['icon' => '&#10003;', 'label' => 'Approved', 'color' => '#1a5c2a',
                        'msg'  => 'Great news! Your hall rental request has been <strong>approved</strong>. Our team will be in touch shortly to confirm deposit and payment details.'],
        'rejected'  => ['icon' => '&#10007;', 'label' => 'Not Approved', 'color' => '#7b1818',
                        'msg'  => 'We regret to inform you that we are unable to approve your hall rental request for the requested date. Please contact us for alternative dates.'],
        'cancelled' => ['icon' => '&#9888;', 'label' => 'Cancelled', 'color' => '#5c5044',
                        'msg'  => 'Your hall rental request has been cancelled as requested.'],
    ];
    if (!isset($map[$status])) return;
    $info = $map[$status];

    $body  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:Arial,sans-serif;background:#f5f0ea;margin:0;padding:20px}
    .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12)}
    .hdr{background:' . $info['color'] . ';padding:28px 32px;text-align:center}
    .hdr h1{color:#fff;font-size:22px;margin:0;font-family:Georgia,serif}
    .body{padding:30px 32px;font-size:14px;color:#3d2b1a;line-height:1.7}
    .ftr{background:#faf6f0;padding:14px 32px;font-size:11px;color:#6b5744;text-align:center;border-top:1px solid #e8dfd3}
    .note{background:#faf6f0;border-left:3px solid #c9a84c;padding:12px 16px;margin:16px 0;font-size:13px}
    </style></head><body><div class="wrap">
    <div class="hdr"><h1>' . $info['icon'] . ' Rental Request ' . $info['label'] . '</h1></div>
    <div class="body">
    <p>Dear ' . esc_html($r->first_name) . ',</p>
    <p>' . $info['msg'] . '</p>
    <p><strong>Your Request:</strong> ' . esc_html($r->event_type) . ' &mdash; ' . esc_html(date('F j, Y', strtotime($r->event_date))) . '</p>';

    if ($notes) {
        $body .= '<div class="note"><strong>Message from our team:</strong><br>' . nl2br(esc_html($notes)) . '</div>';
    }

    $body .= '<p>For questions, contact us at <a href="mailto:' . esc_attr(sjioc_get('sjioc_email','info@sjioc.org')) . '">' . esc_html(sjioc_get('sjioc_email','info@sjioc.org')) . '</a> or call ' . esc_html(sjioc_phone()) . '.</p>
    <p>God bless you,<br><strong>' . esc_html(sjioc_name()) . '</strong></p>
    </div><div class="ftr">' . esc_html(sjioc_address()) . '</div></div></body></html>';

    wp_mail($r->email, 'Hall Rental ' . $info['label'] . ' — ' . sjioc_name(), $body, ['Content-Type: text/html; charset=UTF-8']);
}

/* ─────────────────────────────────────
   ONEDRIVE: Upload rental summary to shared folder
   Requires Files.ReadWrite.All permission in Azure AD
   (see setup instructions in SharePoint admin settings)
───────────────────────────────────── */
function sjioc_rental_upload_summary(int $id, array $d, array $office = []): string {
    $folder_id = sjioc_get('sjioc_rentals_od_folder_id', '');
    if (!$folder_id) return '';

    if (!sjioc_od_is_configured()) return '';

    $token = sjioc_od_get_token();
    if (!$token) return '';

    $ref       = str_pad($id, 4, '0', STR_PAD_LEFT);
    $safe_name = preg_replace('/[^A-Za-z0-9\-_]/', '_', $d['fname'] . '_' . $d['lname']);
    $filename  = 'Rental_' . $ref . '_' . $safe_name . '_' . $d['evdate'] . '.html';
    $cat_map   = ['none' => 'No catering', 'self' => 'Self-catered', 'outside' => 'Outside caterer/vendor'];

    $setup_str = '';
    if (!empty($d['setup_date'])) {
        $setup_str = htmlspecialchars(date('l, F j, Y', strtotime($d['setup_date'])))
                   . ' &nbsp;|&nbsp; ' . htmlspecialchars($d['setup_stime'] . ' – ' . $d['setup_etime']);
    }
    $pm_map = ['check' => 'Check', 'cash' => 'Cash', 'direct_deposit' => 'Direct Deposit'];

    $content = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Hall Rental Request #' . $ref . ' — ' . htmlspecialchars($d['fname'] . ' ' . $d['lname'], ENT_QUOTES) . '</title>
<style>
  body{font-family:Arial,sans-serif;max-width:760px;margin:40px auto;color:#2d1a0a;background:#fff;padding:0 20px}
  h1{color:#7b1818;font-size:22px;border-bottom:3px solid #c9a84c;padding-bottom:10px;margin-bottom:6px}
  h2{color:#7b1818;font-size:13px;margin:22px 0 6px;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #f0e8dc;padding-bottom:5px}
  table{width:100%;border-collapse:collapse;margin-bottom:8px}
  th{text-align:left;background:#faf6f0;padding:7px 12px;font-size:12px;color:#7b1818;width:36%;vertical-align:top}
  td{padding:7px 12px;border-bottom:1px solid #f0e8dc;font-size:13px;vertical-align:top}
  .meta{font-size:12px;color:#6b5744;margin:4px 0 22px}
  .sig{font-style:italic;background:#faf6f0;border-left:3px solid #c9a84c;padding:10px 14px;margin:12px 0;font-size:13px}
  .office{background:#fef9f0;border:2px solid #7b1818;border-radius:4px;padding:16px 20px;margin-top:28px}
  .office h2{color:#5c1010;border-color:#c9a84c;margin-top:0}
  .office th{background:#fdf3e3}
  .empty-cell{color:#aaa;font-style:italic}
</style></head><body>
<h1>&#127963; SJIOC Hall Rental Request #' . $ref . '</h1>
<p class="meta">Submitted: ' . htmlspecialchars(date('D, F j, Y g:i A')) . ' &nbsp;&middot;&nbsp; Status: Pending Review</p>

<h2>Applicant Information</h2>
<table>
<tr><th>Name</th><td>' . htmlspecialchars($d['fname'] . ' ' . $d['lname']) . '</td></tr>
<tr><th>Email</th><td>' . htmlspecialchars($d['email']) . '</td></tr>
<tr><th>Phone</th><td>' . htmlspecialchars($d['phone'] ?: '—') . '</td></tr>
<tr><th>Address</th><td>' . htmlspecialchars($d['address'] ?: '—') . '</td></tr>
<tr><th>Member Status</th><td>' . ($d['member'] === 'member' ? '&#10003; Church Member' : 'Non-Member') . '</td></tr>
<tr><th>Organization Name</th><td>' . htmlspecialchars($d['org_name'] ?: '—') . '</td></tr>
<tr><th>Recommended By</th><td>' . htmlspecialchars($d['recommended_by'] ?: '—') . '</td></tr>
</table>

<h2>Event Details</h2>
<table>
<tr><th>Event Type</th><td>' . htmlspecialchars($d['evtype']) . '</td></tr>
<tr><th>Event Date</th><td>' . htmlspecialchars(date('l, F j, Y', strtotime($d['evdate']))) . '</td></tr>
<tr><th>Event Time</th><td>' . htmlspecialchars($d['stime'] . ' – ' . $d['etime']) . '</td></tr>
<tr><th>Setup / Decoration</th><td>' . ($setup_str ?: '<span class="empty-cell">None requested</span>') . '</td></tr>
<tr><th>Expected Guests</th><td>' . htmlspecialchars((string)$d['guests']) . '</td></tr>
<tr><th>Description</th><td>' . nl2br(htmlspecialchars($d['purpose'])) . '</td></tr>
</table>

<h2>Additional Details</h2>
<table>
<tr><th>Catering</th><td>' . htmlspecialchars($cat_map[$d['catering']] ?? $d['catering']) . '</td></tr>
<tr><th>Special Requests</th><td>' . nl2br(htmlspecialchars($d['special'] ?: '—')) . '</td></tr>
</table>

<h2>Digital Signature</h2>
<div class="sig">&ldquo;' . htmlspecialchars($d['sig']) . '&rdquo; &mdash; agreed to all rental terms and conditions on ' . htmlspecialchars(date('F j, Y')) . '.</div>

<div class="office">
<h2>&#128203; For Office Use Only</h2>
<table>
<tr><th>Amount for Booking Hall</th><td>' . ($office['booking_amount'] ?? $d['booking_amount'] ? '$' . number_format((float)($office['booking_amount'] ?? $d['booking_amount']), 2) : '<span class="empty-cell">___________</span>') . '</td></tr>
<tr><th>Security Deposit</th><td>' . ($office['deposit_amount'] ?? $d['deposit_amount'] ? '$' . number_format((float)($office['deposit_amount'] ?? $d['deposit_amount']), 2) : '<span class="empty-cell">___________</span>') . '</td></tr>
<tr><th>Payment Method</th><td>' . (!empty($office['payment_method']) ? htmlspecialchars($pm_map[$office['payment_method']] ?? $office['payment_method']) : '<span class="empty-cell">Check / Cash / Direct Deposit</span>') . '</td></tr>
<tr><th>Application Accepted By</th><td>' . (!empty($office['accepted_by']) ? htmlspecialchars($office['accepted_by']) : '<span class="empty-cell">___________</span>') . '</td></tr>
<tr><th>Receipt No.</th><td>' . (!empty($office['receipt_no']) ? htmlspecialchars($office['receipt_no']) : '<span class="empty-cell">___________</span>') . '</td></tr>
<tr><th>Date of Payment</th><td>' . (!empty($office['payment_date']) ? htmlspecialchars(date('F j, Y', strtotime($office['payment_date']))) : '<span class="empty-cell">___________</span>') . '</td></tr>
</table>
</div>

</body></html>';

    $upload_url = 'https://graph.microsoft.com/v1.0/drives/' . sjioc_od_drive_id()
                . '/items/' . $folder_id . ':/' . rawurlencode($filename) . ':/content';

    $resp = wp_remote_request($upload_url, [
        'method'  => 'PUT',
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'text/html; charset=UTF-8',
        ],
        'body'    => $content,
        'timeout' => 30,
    ]);

    if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) >= 300) return '';
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    return $data['webUrl'] ?? '';
}

/* ─────────────────────────────────────
   ADMIN: Rentals Page
───────────────────────────────────── */
/* ─────────────────────────────────────
   AJAX: Clear cached OAuth token (admin)
   Forces a fresh token after permission changes
───────────────────────────────────── */
add_action('wp_ajax_sjioc_clear_od_token', function () {
    check_ajax_referer('sjioc_rentals_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    delete_transient('sjioc_od_token');
    wp_send_json_success(['msg' => 'Token cache cleared. A fresh token will be fetched on the next upload.']);
});

function sjioc_rentals_admin_page(): void {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_rentals';

    // Export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        check_admin_referer('sjioc_rental_export');
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY event_date DESC", ARRAY_A);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sjioc-hall-rentals-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    $view = isset($_GET['view']) ? (int) $_GET['view'] : 0;
    $status_filter = sanitize_text_field($_GET['sf'] ?? '');
    $paged  = max(1, (int)($_GET['paged'] ?? 1));
    $per    = 20;
    $offset = ($paged - 1) * $per;

    $where = '';
    if ($status_filter && in_array($status_filter, ['pending','approved','rejected','cancelled'], true)) {
        $where = $wpdb->prepare(' WHERE status = %s', $status_filter);
    }

    $total   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}{$where}");
    $rentals = $wpdb->get_results("SELECT * FROM {$table}{$where} ORDER BY created_at DESC LIMIT {$per} OFFSET {$offset}");

    $counts = [];
    foreach (['pending','approved','rejected','cancelled'] as $s) {
        $counts[$s] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $s));
    }

    $status_colors = [
        'pending'   => '#b45309',
        'approved'  => '#166534',
        'rejected'  => '#7b1818',
        'cancelled' => '#4b5563',
    ];

    $od_configured = sjioc_od_is_configured() && sjioc_get('sjioc_rentals_od_folder_id', '');
    ?>
    <div class="wrap">
    <h1 style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">&#127963; Hall Rental Requests
        <?php if ($od_configured): ?>
        <button class="button" style="font-size:12px" onclick="sjiooClearToken(this)">&#8635; Clear OneDrive Token Cache</button>
        <?php endif; ?>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=sjioc-rentals&export=csv'), 'sjioc_rental_export')); ?>"
           class="button" style="font-size:12px;margin-left:auto">&#8659; Export CSV</a>
    </h1>
    <div id="sj-token-msg" style="display:none;margin-bottom:16px"></div>

    <!-- Status filter tabs -->
    <ul class="subsubsub" style="margin-bottom:16px">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-rentals')); ?>"
               <?php echo !$status_filter ? 'class="current"' : ''; ?>>All <span class="count">(<?php echo $total; ?>)</span></a> |</li>
        <?php foreach ($counts as $s => $c): ?>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-rentals&sf=' . $s)); ?>"
               <?php echo $status_filter === $s ? 'class="current"' : ''; ?> style="color:<?php echo $status_colors[$s]; ?>">
               <?php echo ucfirst($s); ?> <span class="count">(<?php echo $c; ?>)</span></a><?php echo array_key_last($counts) !== $s ? ' |' : ''; ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($view && $detail = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $view))): ?>
    <!-- ── Detail View ── -->
    <div style="max-width:860px">
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-rentals')); ?>">&larr; Back to all requests</a></p>
        <div id="sj-rental-msg" style="display:none;margin-bottom:16px"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
            <div>
                <h3 style="color:#7b1818;border-bottom:2px solid #c9a84c;padding-bottom:8px">Request #<?php echo str_pad($detail->id,4,'0',STR_PAD_LEFT); ?></h3>
                <table class="widefat fixed striped" style="margin-bottom:16px">
                    <tr><th>Name</th><td><?php echo esc_html($detail->first_name . ' ' . $detail->last_name); ?></td></tr>
                    <tr><th>Email</th><td><a href="mailto:<?php echo esc_attr($detail->email); ?>"><?php echo esc_html($detail->email); ?></a></td></tr>
                    <tr><th>Phone</th><td><?php echo esc_html($detail->phone ?: '—'); ?></td></tr>
                    <tr><th>Address</th><td><?php echo esc_html($detail->address ?: '—'); ?></td></tr>
                    <tr><th>Member Status</th><td><?php echo esc_html(ucfirst($detail->member_status)); ?></td></tr>
                    <tr><th>Organization</th><td><?php echo esc_html($detail->org_name ?: '—'); ?></td></tr>
                    <tr><th>Recommended By</th><td><?php echo esc_html($detail->recommended_by ?: '—'); ?></td></tr>
                </table>
                <table class="widefat fixed striped" style="margin-bottom:16px">
                    <tr><th>Event Type</th><td><?php echo esc_html($detail->event_type); ?></td></tr>
                    <tr><th>Event Date</th><td><?php echo esc_html(date('D, F j, Y', strtotime($detail->event_date))); ?></td></tr>
                    <tr><th>Event Time</th><td><?php echo esc_html($detail->start_time . ' – ' . $detail->end_time); ?></td></tr>
                    <tr><th>Setup / Decoration</th><td><?php
                        echo $detail->setup_date
                            ? esc_html(date('D, M j Y', strtotime($detail->setup_date)) . ' · ' . $detail->setup_start_time . ' – ' . $detail->setup_end_time)
                            : '—';
                    ?></td></tr>
                    <tr><th>Guests</th><td><?php echo esc_html($detail->guests); ?></td></tr>
                    <tr><th>Description</th><td><?php echo nl2br(esc_html($detail->event_purpose)); ?></td></tr>
                </table>
                <table class="widefat fixed striped">
                    <tr><th>Catering</th><td><?php echo esc_html(ucfirst($detail->catering)); ?></td></tr>
                    <tr><th>Special Requests</th><td><?php echo nl2br(esc_html($detail->special_req ?: '—')); ?></td></tr>
                    <tr><th>Digital Signature</th><td><em><?php echo esc_html($detail->signature); ?></em></td></tr>
                </table>
                <?php if ($detail->od_folder_url): ?>
                <p style="margin-top:12px"><a href="<?php echo esc_url($detail->od_folder_url); ?>" target="_blank" class="button">&#128196; View Summary File in OneDrive</a></p>
                <?php endif; ?>
            </div>

            <div>
                <h3 style="color:#7b1818;border-bottom:2px solid #c9a84c;padding-bottom:8px">Manage Request</h3>
                <p><strong>Current Status:</strong>
                    <span style="background:<?php echo $status_colors[$detail->status]; ?>;color:#fff;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                        <?php echo esc_html(ucfirst($detail->status)); ?>
                    </span>
                </p>
                <p><strong>Submitted:</strong> <?php echo esc_html(date('M j, Y g:i A', strtotime($detail->created_at))); ?></p>

                <div style="background:#faf6f0;border:1px solid #e0d5c5;border-radius:6px;padding:20px;margin-top:16px">
                    <label style="font-weight:700;display:block;margin-bottom:8px">Update Status:</label>
                    <select id="rj-status" style="width:100%;margin-bottom:12px;padding:8px">
                        <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php selected($detail->status, $s); ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label style="font-weight:700;display:block;margin-bottom:8px">Notes to Requestor (sent via email):</label>
                    <textarea id="rj-notes" style="width:100%;min-height:100px;margin-bottom:12px;padding:8px"><?php echo esc_textarea($detail->admin_notes); ?></textarea>
                    <button class="button button-primary" onclick="sjioUpdateRental(<?php echo $detail->id; ?>)">
                        Save &amp; Notify Requestor
                    </button>
                    <p class="description" style="margin-top:8px">An email will be sent to <strong><?php echo esc_html($detail->email); ?></strong> when status changes to Approved, Rejected, or Cancelled.</p>
                </div>

                <!-- For Office Use Only -->
                <div style="background:#fef9f0;border:2px solid #7b1818;border-radius:6px;padding:20px;margin-top:20px">
                    <h4 style="color:#7b1818;margin-top:0;border-bottom:1px solid #c9a84c;padding-bottom:8px">&#128203; For Office Use Only</h4>
                    <p class="description" style="margin-bottom:14px">These fields are saved to the database and included in the SharePoint summary file.</p>

                    <label style="font-weight:700;display:block;margin-bottom:4px">Amount for Booking Hall ($)</label>
                    <input type="number" id="rj-booking-amount" step="0.01" min="0"
                        value="<?php echo esc_attr($detail->booking_amount ?: sjioc_get('sjioc_hall_booking_amount', '650')); ?>"
                        style="width:100%;margin-bottom:12px;padding:7px">

                    <label style="font-weight:700;display:block;margin-bottom:4px">Security Deposit Amount ($)</label>
                    <input type="number" id="rj-deposit-amount" step="0.01" min="0"
                        value="<?php echo esc_attr($detail->deposit_amount ?: sjioc_get('sjioc_hall_deposit_amount', '100')); ?>"
                        style="width:100%;margin-bottom:12px;padding:7px">

                    <label style="font-weight:700;display:block;margin-bottom:4px">Payment Method</label>
                    <select id="rj-payment-method" style="width:100%;margin-bottom:12px;padding:7px">
                        <option value="">-- Select --</option>
                        <?php foreach (['check' => 'Check', 'cash' => 'Cash', 'direct_deposit' => 'Direct Deposit'] as $v => $l): ?>
                        <option value="<?php echo $v; ?>" <?php selected($detail->payment_method ?? '', $v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label style="font-weight:700;display:block;margin-bottom:4px">Date of Payment</label>
                    <input type="date" id="rj-payment-date"
                        value="<?php echo esc_attr($detail->payment_date ?? ''); ?>"
                        style="width:100%;margin-bottom:12px;padding:7px">

                    <label style="font-weight:700;display:block;margin-bottom:4px">Application Accepted By</label>
                    <input type="text" id="rj-accepted-by" placeholder="Name of office member"
                        value="<?php echo esc_attr($detail->accepted_by ?? ''); ?>"
                        style="width:100%;margin-bottom:12px;padding:7px">

                    <label style="font-weight:700;display:block;margin-bottom:4px">Receipt No.</label>
                    <input type="text" id="rj-receipt-no" placeholder="e.g. REC-2025-001"
                        value="<?php echo esc_attr($detail->receipt_no ?? ''); ?>"
                        style="width:100%;margin-bottom:4px;padding:7px">
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── List View ── -->
    <?php if (!$rentals): ?>
        <p>No rental requests found.</p>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:1100px">
        <thead>
            <tr>
                <th style="width:50px">#</th>
                <th>Name</th>
                <th>Event Type</th>
                <th>Date</th>
                <th>Guests</th>
                <th>Status</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rentals as $r): ?>
        <tr>
            <td><?php echo str_pad($r->id, 4, '0', STR_PAD_LEFT); ?></td>
            <td><strong><?php echo esc_html($r->first_name . ' ' . $r->last_name); ?></strong><br>
                <small><?php echo esc_html($r->email); ?></small></td>
            <td><?php echo esc_html($r->event_type); ?></td>
            <td><?php echo esc_html(date('M j, Y', strtotime($r->event_date))); ?></td>
            <td><?php echo esc_html($r->guests); ?></td>
            <td><span style="background:<?php echo $status_colors[$r->status] ?? '#666'; ?>;color:#fff;padding:2px 9px;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap"><?php echo esc_html(ucfirst($r->status)); ?></span></td>
            <td><?php echo esc_html(date('M j, Y', strtotime($r->created_at))); ?></td>
            <td><a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-rentals&view=' . $r->id)); ?>" class="button button-small">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total > $per):
        $pages = ceil($total / $per);
        echo '<div class="tablenav bottom" style="margin-top:12px"><div class="tablenav-pages">';
        for ($p = 1; $p <= $pages; $p++) {
            $url = admin_url('admin.php?page=sjioc-rentals&paged=' . $p . ($status_filter ? '&sf=' . $status_filter : ''));
            $cls = $p === $paged ? 'button button-primary' : 'button';
            echo '<a href="' . esc_url($url) . '" class="' . $cls . '" style="margin-right:4px">' . $p . '</a>';
        }
        echo '</div></div>';
    endif; ?>
    <?php endif; ?>
    <?php endif; ?>

    </div>

    <script>
    function sjiooClearToken(btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Clearing…';
        var msg = document.getElementById('sj-token-msg');
        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'sjioc_clear_od_token',
                nonce: '<?php echo esc_js(wp_create_nonce('sjioc_rentals_admin')); ?>'
            })
        })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            btn.textContent = '↺ Clear OneDrive Token Cache';
            msg.style.display = 'block';
            msg.className = d.success ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
            msg.innerHTML = '<p>' + (d.data?.msg || 'Done.') + '</p>';
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '↺ Clear OneDrive Token Cache';
        });
    }
    function sjioUpdateRental(id) {
        var status         = document.getElementById('rj-status').value;
        var notes          = document.getElementById('rj-notes').value;
        var bookingAmount  = document.getElementById('rj-booking-amount').value;
        var depositAmount  = document.getElementById('rj-deposit-amount').value;
        var paymentMethod  = document.getElementById('rj-payment-method').value;
        var paymentDate    = document.getElementById('rj-payment-date').value;
        var acceptedBy     = document.getElementById('rj-accepted-by').value;
        var receiptNo      = document.getElementById('rj-receipt-no').value;
        var msg            = document.getElementById('sj-rental-msg');
        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action:          'sjioc_rental_update_status',
                nonce:           '<?php echo esc_js(wp_create_nonce('sjioc_rentals_admin')); ?>',
                rental_id:       id,
                status:          status,
                admin_notes:     notes,
                payment_method:  paymentMethod,
                payment_date:    paymentDate,
                accepted_by:     acceptedBy,
                receipt_no:      receiptNo,
                booking_amount:  bookingAmount,
                deposit_amount:  depositAmount
            })
        })
        .then(r => r.json())
        .then(d => {
            msg.style.display = 'block';
            msg.className     = d.success ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
            msg.innerHTML     = '<p>' + (d.data?.msg || d.message || 'Done.') + '</p>';
            window.scrollTo({top: 0, behavior: 'smooth'});
        })
        .catch(() => {
            msg.style.display = 'block';
            msg.className = 'notice notice-error is-dismissible';
            msg.innerHTML = '<p>Network error. Please try again.</p>';
        });
    }
    </script>
    <?php
}