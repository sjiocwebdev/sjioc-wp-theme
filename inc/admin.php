<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   ADMIN MENU — SJIOC
───────────────────────────────────── */
function sjioc_admin_menu() {
    add_menu_page(
        'SJIOC Settings', 'SJIOC', 'manage_options',
        'sjioc', 'sjioc_chat_settings_page',
        'dashicons-church', 58
    );
    add_submenu_page(
        'sjioc', 'Chat & Knowledge Base', 'Chat Settings',
        'manage_options', 'sjioc', 'sjioc_chat_settings_page'
    );
    add_submenu_page(
        'sjioc', 'Celebrations Cache', 'Celebrations',
        'manage_options', 'sjioc-celebrations', 'sjioc_celebrations_admin_page'
    );
    add_submenu_page(
        'sjioc', 'Parish Members', 'Members',
        'manage_options', 'sjioc-members', 'sjioc_members_admin_page'
    );
    add_submenu_page(
        'sjioc', 'Import Members', 'Import Members',
        'manage_options', 'sjioc-import', 'sjioc_import_page'
    );
    add_submenu_page(
        'sjioc', 'Google Calendar Events', 'Events',
        'manage_options', 'sjioc-events', 'sjioc_events_settings_page'
    );
    add_submenu_page(
        'sjioc', 'Vehicle Registry', 'Vehicles',
        'manage_options', 'sjioc-vehicles', 'sjioc_vehicles_admin_page'
    );
    add_submenu_page(
        'sjioc', 'Import Vehicles', 'Import Vehicles',
        'manage_options', 'sjioc-import-vehicles', 'sjioc_import_vehicles_page'
    );
    add_submenu_page(
        'sjioc', 'Parish Directory', 'Parish Directory',
        'manage_options', 'edit.php?post_type=sjioc_contact', ''
    );
    add_submenu_page(
        'sjioc', 'Hall Rental Requests', 'Hall Rentals',
        'manage_options', 'sjioc-rentals', 'sjioc_rentals_admin_page'
    );
    add_submenu_page(
        'sjioc', 'SMTP Email Settings', 'Email (SMTP)',
        'manage_options', 'sjioc-smtp', 'sjioc_smtp_settings_page'
    );
    // Hidden page — edit form not shown in sidebar nav
    add_submenu_page(
        null, 'Edit Member', '', 'manage_options', 'sjioc-member-edit', 'sjioc_member_edit_page'
    );
}
add_action('admin_menu', 'sjioc_admin_menu');

/* ─────────────────────────────────────
   MEMBERS LIST PAGE
───────────────────────────────────── */
function sjioc_members_admin_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';

    // Toggle active/inactive
    if (isset($_POST['sjioc_toggle']) && check_admin_referer('sjioc_toggle')) {
        $mid    = (int) $_POST['member_id'];
        $is_now = (int) $_POST['current_active'];
        $wpdb->update($table, ['is_active' => $is_now ? 0 : 1], ['id' => $mid]);
        $label = $is_now ? 'disabled' : 'enabled';
        echo '<div class="notice notice-success is-dismissible"><p>Member ' . esc_html($label) . '.</p></div>';
    }

    $search   = sanitize_text_field($_GET['s'] ?? '');
    $paged    = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 25;
    $offset   = ($paged - 1) * $per_page;

    if ($search) {
        $like  = '%' . $wpdb->esc_like($search) . '%';
        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE first_name LIKE %s OR last_name LIKE %s
                    OR cardex_no LIKE %s OR phone_number LIKE %s",
                $like, $like, $like, $like
            )
        );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE first_name LIKE %s OR last_name LIKE %s
                    OR cardex_no LIKE %s OR phone_number LIKE %s
                 ORDER BY cardex_no, member_seq
                 LIMIT %d OFFSET %d",
                $like, $like, $like, $like, $per_page, $offset
            )
        );
    } else {
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY cardex_no, member_seq LIMIT %d OFFSET %d",
                $per_page, $offset
            )
        );
    }

    $pages    = $total ? (int) ceil($total / $per_page) : 1;
    $list_url = admin_url('admin.php?page=sjioc-members');
    $edit_url = admin_url('admin.php?page=sjioc-member-edit');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">👥 Parish Members</h1>
        <a href="<?php echo esc_url($edit_url); ?>" class="page-title-action">+ Add Member</a>
        <hr class="wp-header-end">

        <form method="get" style="margin:12px 0 4px">
            <input type="hidden" name="page" value="sjioc-members">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                placeholder="Search name, cardex, phone…" class="regular-text">
            <?php submit_button('Search', 'secondary', '', false); ?>
            <?php if ($search): ?>
                <a href="<?php echo esc_url($list_url); ?>" class="button">Clear</a>
            <?php endif; ?>
        </form>

        <p class="description" style="margin-bottom:12px">
            <?php echo esc_html($total); ?> member<?php echo $total !== 1 ? 's' : ''; ?>
            <?php echo $search ? ' matching "' . esc_html($search) . '"' : ' total'; ?>.
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:80px">Cardex</th>
                    <th style="width:34px">#</th>
                    <th>Name</th>
                    <th style="width:44px">Sex</th>
                    <th style="width:96px">Date of Birth</th>
                    <th style="width:116px">Phone</th>
                    <th style="width:64px;text-align:center">Status</th>
                    <th style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $m): ?>
            <tr style="<?php echo $m->is_active ? '' : 'opacity:.45'; ?>">
                <td><?php echo esc_html($m->cardex_no); ?></td>
                <td style="color:#999"><?php echo esc_html($m->member_seq); ?></td>
                <td>
                    <strong><?php echo esc_html(trim($m->first_name . ' ' . $m->middle_name . ' ' . $m->last_name)); ?></strong>
                    <?php if ($m->email): ?>
                        <br><small style="color:#888"><?php echo esc_html($m->email); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($m->gender); ?></td>
                <td><?php
                    echo ($m->date_of_birth && $m->date_of_birth !== '0000-00-00')
                        ? esc_html(date('M j, Y', strtotime($m->date_of_birth)))
                        : '—';
                ?></td>
                <td><?php echo esc_html($m->phone_number ?: '—'); ?></td>
                <td style="text-align:center">
                    <span style="font-size:11px;font-weight:600;color:<?php echo $m->is_active ? '#16a34a' : '#dc2626'; ?>">
                        <?php echo $m->is_active ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg('id', $m->id, $edit_url)); ?>"
                       class="button button-small">Edit</a>
                    <form method="post" style="display:inline;margin:0">
                        <?php wp_nonce_field('sjioc_toggle'); ?>
                        <input type="hidden" name="member_id" value="<?php echo (int) $m->id; ?>">
                        <input type="hidden" name="current_active" value="<?php echo (int) $m->is_active; ?>">
                        <button type="submit" name="sjioc_toggle" value="1"
                            class="button button-small"
                            style="color:<?php echo $m->is_active ? '#dc2626' : '#16a34a'; ?>">
                            <?php echo $m->is_active ? 'Disable' : 'Enable'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:24px;color:#888">
                    No members found<?php echo $search ? ' for "' . esc_html($search) . '"' : ''; ?>.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div style="margin-top:10px">
            <?php for ($p = 1; $p <= $pages; $p++):
                $url = add_query_arg(['paged' => $p, 's' => $search], $list_url);
            ?>
                <?php if ($p === $paged): ?>
                    <span style="display:inline-block;padding:4px 10px;background:#2271b1;color:#fff;border-radius:3px;margin:0 2px"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url($url); ?>" class="button" style="margin:0 2px"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────
   MEMBER ADD / EDIT PAGE
───────────────────────────────────── */
function sjioc_member_edit_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';
    $id    = (int) ($_GET['id'] ?? 0);

    // Save
    if (isset($_POST['sjioc_save_member']) && check_admin_referer('sjioc_save_member')) {
        $dob = sanitize_text_field($_POST['date_of_birth'] ?? '');
        $wed = sanitize_text_field($_POST['wedding_date']  ?? '');
        $data = [
            'cardex_no'      => sanitize_text_field($_POST['cardex_no']      ?? ''),
            'member_seq'     => max(1, (int) ($_POST['member_seq']            ?? 1)),
            'first_name'     => sanitize_text_field($_POST['first_name']      ?? ''),
            'middle_name'    => sanitize_text_field($_POST['middle_name']     ?? ''),
            'last_name'      => sanitize_text_field($_POST['last_name']       ?? ''),
            'gender'         => in_array($_POST['gender'] ?? '', ['M','F']) ? $_POST['gender'] : 'M',
            'date_of_birth'  => $dob ?: null,
            'marital_status' => in_array($_POST['marital_status'] ?? '', ['M','S','W','D']) ? $_POST['marital_status'] : 'S',
            'wedding_date'   => $wed ?: null,
            'phone_number'   => sanitize_text_field($_POST['phone_number']    ?? ''),
            'email'          => sanitize_email($_POST['email']                ?? ''),
            'address'        => sanitize_text_field($_POST['address']         ?? ''),
            'city'           => sanitize_text_field($_POST['city']            ?? ''),
            'state'          => strtoupper(sanitize_text_field($_POST['state'] ?? '')),
            'zip_code'       => sanitize_text_field($_POST['zip_code']        ?? ''),
            'country'        => sanitize_text_field($_POST['country']         ?? 'USA'),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            $wpdb->update($table, $data, ['id' => $id]);
            echo '<div class="notice notice-success is-dismissible"><p>Member updated.</p></div>';
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
            echo '<div class="notice notice-success is-dismissible"><p>Member added.</p></div>';
        }
    }

    $m        = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) : null;
    $back_url = admin_url('admin.php?page=sjioc-members');

    // Helper: safe attr value from member row or default
    $f = function(string $field, string $default = '') use ($m): string {
        return esc_attr($m ? ($m->$field ?? $default) : $default);
    };
    $date_f = function(string $field) use ($m): string {
        $v = $m->$field ?? '';
        return ($v && $v !== '0000-00-00') ? esc_attr($v) : '';
    };
    ?>
    <div class="wrap">
        <h1><?php echo $m ? '✏️ Edit Member' : '➕ Add Member'; ?></h1>
        <a href="<?php echo esc_url($back_url); ?>" class="button" style="margin-bottom:16px">← Back to Members</a>

        <form method="post" style="max-width:680px">
            <?php wp_nonce_field('sjioc_save_member'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cardex_no">Cardex No <span style="color:red">*</span></label></th>
                    <td><input id="cardex_no" type="text" name="cardex_no" value="<?php echo $f('cardex_no'); ?>"
                        class="regular-text" required placeholder="e.g. A-01"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="member_seq">Member #</label></th>
                    <td><input id="member_seq" type="number" name="member_seq" value="<?php echo $f('member_seq', '1'); ?>"
                        class="small-text" min="1"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="first_name">First Name <span style="color:red">*</span></label></th>
                    <td><input id="first_name" type="text" name="first_name" value="<?php echo $f('first_name'); ?>"
                        class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="middle_name">Middle Name</label></th>
                    <td><input id="middle_name" type="text" name="middle_name" value="<?php echo $f('middle_name'); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="last_name">Last Name</label></th>
                    <td><input id="last_name" type="text" name="last_name" value="<?php echo $f('last_name'); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="gender">Gender</label></th>
                    <td>
                        <select id="gender" name="gender">
                            <option value="M" <?php selected($f('gender','M'), 'M'); ?>>Male</option>
                            <option value="F" <?php selected($f('gender','M'), 'F'); ?>>Female</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="date_of_birth">Date of Birth</label></th>
                    <td><input id="date_of_birth" type="date" name="date_of_birth"
                        value="<?php echo $m ? $date_f('date_of_birth') : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="marital_status">Marital Status</label></th>
                    <td>
                        <select id="marital_status" name="marital_status">
                            <option value="S" <?php selected($f('marital_status','S'), 'S'); ?>>Single</option>
                            <option value="M" <?php selected($f('marital_status','S'), 'M'); ?>>Married</option>
                            <option value="W" <?php selected($f('marital_status','S'), 'W'); ?>>Widowed</option>
                            <option value="D" <?php selected($f('marital_status','S'), 'D'); ?>>Divorced</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wedding_date">Wedding Date</label></th>
                    <td><input id="wedding_date" type="date" name="wedding_date"
                        value="<?php echo $m ? $date_f('wedding_date') : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="phone_number">Phone</label></th>
                    <td><input id="phone_number" type="tel" name="phone_number" value="<?php echo $f('phone_number'); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="email">Email</label></th>
                    <td><input id="email" type="email" name="email" value="<?php echo $f('email'); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="address">Address</label></th>
                    <td><input id="address" type="text" name="address" value="<?php echo $f('address'); ?>"
                        class="large-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="city">City</label></th>
                    <td><input id="city" type="text" name="city" value="<?php echo $f('city'); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="state">State</label></th>
                    <td><input id="state" type="text" name="state" value="<?php echo $f('state'); ?>"
                        class="small-text" maxlength="2" placeholder="PA"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="zip_code">Zip Code</label></th>
                    <td><input id="zip_code" type="text" name="zip_code" value="<?php echo $f('zip_code'); ?>"
                        class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="country">Country</label></th>
                    <td><input id="country" type="text" name="country" value="<?php echo $f('country', 'USA'); ?>"
                        class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" value="1"
                                <?php checked($m ? (int) $m->is_active : 1, 1); ?>>
                            Active member
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button($m ? 'Update Member' : 'Add Member', 'primary', 'sjioc_save_member'); ?>
        </form>
    </div>
    <?php
}

/* ─────────────────────────────────────
   CELEBRATIONS ADMIN PAGE
───────────────────────────────────── */
function sjioc_celebrations_admin_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['sjioc_regen']) && check_admin_referer('sjioc_regen_nonce')) {
        sjioc_run_celebrations_cron();
        echo '<div class="notice notice-success is-dismissible"><p>Celebrations cache regenerated successfully.</p></div>';
    }

    $cache  = get_option('sjioc_celebrations_cache', []);
    $bdayc  = count($cache['birthdays']    ?? []);
    $annivc = count($cache['anniversaries'] ?? []);
    $next   = wp_next_scheduled('sjioc_celebrations_cron');
    ?>
    <div class="wrap">
        <h1>🎂 Celebrations — Weekly Cache</h1>
        <p>The cache rebuilds automatically every Monday at 12:01 AM. Use <em>Regenerate Now</em> to refresh immediately after importing new members.</p>

        <table class="widefat striped" style="max-width:600px;margin-bottom:20px">
            <tr><th>Last generated</th><td><?php echo esc_html($cache['generated_at'] ?? '—'); ?></td></tr>
            <tr><th>Week window</th><td><?php echo esc_html($cache['week_label']    ?? '—'); ?></td></tr>
            <tr><th>Birthdays this week</th><td><?php echo esc_html($bdayc); ?></td></tr>
            <tr><th>Anniversaries this week</th><td><?php echo esc_html($annivc); ?></td></tr>
            <tr><th>Next scheduled run</th><td><?php echo $next ? esc_html(date('D, M j Y g:i A', $next)) : 'Not scheduled'; ?></td></tr>
        </table>

        <form method="post">
            <?php wp_nonce_field('sjioc_regen_nonce'); ?>
            <input type="submit" name="sjioc_regen" class="button button-primary" value="↺ Regenerate Now">
        </form>

        <?php if (!empty($cache['birthdays']) || !empty($cache['anniversaries'])): ?>
        <h2 style="margin-top:30px">Current Cache Contents</h2>

        <?php if (!empty($cache['birthdays'])): ?>
        <h3>Birthdays (<?php echo $bdayc; ?>)</h3>
        <table class="widefat striped" style="max-width:500px">
            <thead><tr><th>Name</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($cache['birthdays'] as $b): ?>
            <tr>
                <td><?php echo esc_html($b['name']); ?></td>
                <td><?php echo esc_html($b['month_name'] . ' ' . $b['day']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($cache['anniversaries'])): ?>
        <h3 style="margin-top:20px">Anniversaries (<?php echo $annivc; ?>)</h3>
        <table class="widefat striped" style="max-width:500px">
            <thead><tr><th>Couple</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($cache['anniversaries'] as $a): ?>
            <tr>
                <td><?php echo esc_html($a['names']); ?></td>
                <td><?php echo esc_html($a['month_name'] . ' ' . $a['day']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────
   SMTP SETTINGS PAGE
───────────────────────────────────── */
function sjioc_smtp_settings_page(): void {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['sjioc_smtp_save']) && check_admin_referer('sjioc_smtp_settings')) {
        $saveable = [
            'sjioc_smtp_host' => 'SJIOC_SMTP_HOST',
            'sjioc_smtp_user' => 'SJIOC_SMTP_USER',
            'sjioc_smtp_port' => 'SJIOC_SMTP_PORT',
            'sjioc_smtp_from' => 'SJIOC_SMTP_FROM',
        ];
        foreach ($saveable as $opt => $const) {
            if (!defined($const)) {
                update_option($opt, sanitize_text_field($_POST[$opt] ?? ''));
            }
        }
        // Only update password if a new value was entered
        if (!defined('SJIOC_SMTP_PASS')) {
            $pass = wp_unslash($_POST['sjioc_smtp_pass'] ?? '');
            if ($pass !== '') {
                update_option('sjioc_smtp_pass', sanitize_text_field($pass));
            }
        }
        echo '<div class="notice notice-success is-dismissible"><p>SMTP settings saved.</p></div>';
    }

    // Returns source info for a setting
    $src = function (string $const, string $opt, mixed $default = '') use (&$src): array {
        if (defined($const)) {
            return ['locked' => true, 'val' => constant($const), 'label' => 'wp-config.php', 'color' => '#16a34a'];
        }
        $v = get_option($opt, $default);
        return $v !== '' && $v !== $default
            ? ['locked' => false, 'val' => $v,       'label' => 'Database',  'color' => '#b45309']
            : ['locked' => false, 'val' => $default, 'label' => 'Not set',   'color' => '#dc2626'];
    };

    $host  = $src('SJIOC_SMTP_HOST', 'sjioc_smtp_host', '');
    $user  = $src('SJIOC_SMTP_USER', 'sjioc_smtp_user', '');
    $pass  = $src('SJIOC_SMTP_PASS', 'sjioc_smtp_pass', '');
    $port  = $src('SJIOC_SMTP_PORT', 'sjioc_smtp_port', 587);
    $from  = $src('SJIOC_SMTP_FROM', 'sjioc_smtp_from', '');
    $nonce = wp_create_nonce('sjioc_smtp_admin');

    $badge = function (array $s): string {
        return '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;'
             . 'background:' . $s['color'] . ';color:#fff;margin-left:8px">' . esc_html($s['label']) . '</span>';
    };

    $field = function (string $id, string $label, array $s, string $type = 'text', string $ph = '') use ($badge): void {
        $dis = $s['locked'] ? ' disabled style="background:#f0f0f1;color:#555"' : '';
        echo '<tr><th scope="row"><label for="' . esc_attr($id) . '">' . esc_html($label) . $badge($s) . '</label></th><td>';
        if ($type === 'password') {
            $ph2 = $s['locked'] ? '(set in wp-config.php)' : ($s['val'] !== '' ? '••••••• (saved — leave blank to keep)' : 'Enter password');
            echo '<input type="password" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="" placeholder="' . esc_attr($ph2) . '" class="regular-text"' . $dis . '>';
        } else {
            $v = $s['locked'] ? '' : esc_attr((string) $s['val']);
            $ph2 = $s['locked'] ? '(set in wp-config.php)' : esc_attr($ph);
            echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . $v . '" placeholder="' . $ph2 . '" class="regular-text"' . $dis . '>';
        }
        echo '</td></tr>';
    };
    ?>
    <div class="wrap" style="max-width:780px">
    <h1>✉️ SMTP Email Settings</h1>
    <p>Outgoing email for the contact form, hall rental notifications, and all theme emails. Uses Microsoft 365 / Outlook SMTP by default.</p>

    <div style="background:#fff3cd;border:1px solid #ffc107;border-left:4px solid #ffc107;border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:13px">
        <strong>Microsoft 365 requirement:</strong> SMTP AUTH must be enabled on the sending mailbox.
        Go to <strong>admin.microsoft.com → Users → [account] → Mail → Manage email apps → tick Authenticated SMTP</strong>.
        If MFA is on, use an App Password (myaccount.microsoft.com → Security → App passwords).
    </div>

    <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:14px 20px;margin-bottom:24px">
        <strong>Status:</strong>
        <?php if (sjioc_smtp_is_configured()): ?>
            <span style="background:#16a34a;color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">✓ Configured</span>
        <?php else: ?>
            <span style="background:#dc2626;color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">✗ Not Configured</span>
        <?php endif; ?>
        &nbsp;
        <span style="font-size:12px;color:#666">Settings in <code>wp-config.php</code> are locked and take priority over anything saved here.</span>
    </div>

    <form method="post">
        <?php wp_nonce_field('sjioc_smtp_settings'); ?>
        <table class="form-table" role="presentation" style="max-width:700px">
            <?php
            $field('sjioc_smtp_host', 'SMTP Host',          $host, 'text',     'smtp.office365.com');
            $field('sjioc_smtp_port', 'SMTP Port',          $port, 'number',   '587');
            $field('sjioc_smtp_user', 'SMTP Username',      $user, 'email',    'info@sjioc.org');
            $field('sjioc_smtp_pass', 'SMTP Password',      $pass, 'password', '');
            $field('sjioc_smtp_from', 'From Email Address', $from, 'email',    'Leave blank to use SMTP Username above');
            ?>
        </table>
        <p class="description" style="margin-bottom:20px">
            <strong>Encryption:</strong> STARTTLS on port 587 (standard for Microsoft 365 / Outlook).<br>
            <strong>From Email:</strong> For Microsoft 365, the From address must match the authenticated account,
            or use a shared mailbox with "Send As" permission configured in Exchange.
        </p>
        <?php submit_button('Save SMTP Settings', 'primary', 'sjioc_smtp_save'); ?>
    </form>

    <hr style="margin:28px 0">
    <h2>Send a Test Email</h2>
    <p>Verify your configuration by sending a live test. The email is sent through your configured SMTP server right now.</p>

    <?php if (!sjioc_smtp_is_configured()): ?>
        <p style="color:#dc2626;font-weight:600">⚠ SMTP is not configured. Fill in the settings above first.</p>
    <?php else: ?>
    <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <label for="sj-test-email" style="display:block;margin-bottom:4px;font-weight:600;font-size:13px">Recipient email</label>
            <input type="email" id="sj-test-email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>"
                   class="regular-text" placeholder="recipient@example.com">
        </div>
        <div style="padding-top:22px">
            <button class="button button-primary" onclick="sjiocTestSmtp(this)">Send Test Email →</button>
        </div>
    </div>
    <div id="sj-smtp-result" style="margin-top:14px;display:none"></div>
    <?php endif; ?>

    </div>

    <script>
    function sjiocTestSmtp(btn) {
        var email  = document.getElementById('sj-test-email');
        var result = document.getElementById('sj-smtp-result');
        if (!email || !email.value.trim()) { alert('Please enter a recipient email address.'); return; }
        btn.disabled = true; btn.textContent = '⏳ Sending…';
        result.style.display = 'none';
        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'sjioc_test_email',
                nonce:  '<?php echo esc_js($nonce); ?>',
                to:     email.value.trim()
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            btn.disabled = false;
            btn.textContent = 'Send Test Email →';
            result.style.display = 'block';
            result.className = d.success ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
            result.innerHTML = '<p>' + (d.data?.msg || (d.success ? 'Sent!' : 'Failed.')) + '</p>';
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Send Test Email →';
            result.style.display = 'block';
            result.className = 'notice notice-error is-dismissible';
            result.innerHTML = '<p>Network error. Please try again.</p>';
        });
    }
    </script>
    <?php
}

add_action('wp_ajax_sjioc_test_email', function () {
    check_ajax_referer('sjioc_smtp_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $to = sanitize_email($_POST['to'] ?? '');
    if (!is_email($to)) {
        wp_send_json_error(['msg' => 'Invalid email address.']);
    }

    $error_msg = '';
    add_action('wp_mail_failed', function (\WP_Error $e) use (&$error_msg) {
        $error_msg = $e->get_error_message();
    });

    $sent = wp_mail(
        $to,
        'SJIOC WordPress — SMTP Test Email',
        '<p>This is a test email from your WordPress site (<strong>' . esc_html(home_url()) . '</strong>).</p>'
        . '<p>If you received this, your SMTP configuration is working correctly.</p>'
        . '<p style="color:#888;font-size:12px">Sent at ' . esc_html(date('D, F j Y g:i A T')) . '</p>',
        ['Content-Type: text/html; charset=UTF-8']
    );

    if ($sent) {
        wp_send_json_success(['msg' => 'Test email sent to <strong>' . esc_html($to) . '</strong>. Check the inbox (and spam folder).']);
    } else {
        $detail = $error_msg ?: 'wp_mail returned false — check SMTP credentials and that SMTP AUTH is enabled on the mailbox.';
        wp_send_json_error(['msg' => 'Send failed: ' . esc_html($detail)]);
    }
});

/* ─────────────────────────────────────
   CHAT SETTINGS PAGE
───────────────────────────────────── */
function sjioc_chat_settings_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    if (isset($_POST['sjioc_chat_save']) && check_admin_referer('sjioc_chat_nonce')) {
        update_option('sjioc_chat_rules',      sanitize_textarea_field(wp_unslash($_POST['sjioc_chat_rules']      ?? '')));
        update_option('sjioc_kb_text',         sanitize_textarea_field(wp_unslash($_POST['sjioc_kb_text']         ?? '')));
        update_option('sjioc_chat_max_tokens', max(50, min(1000, (int) ($_POST['sjioc_chat_max_tokens']  ?? 250))));
        update_option('sjioc_chat_temperature', max(0, min(1,   (float) ($_POST['sjioc_chat_temperature'] ?? 0.4))));
        echo '<div class="notice notice-success is-dismissible"><p>Chat settings saved.</p></div>';
    }

    $rules       = get_option('sjioc_chat_rules', sjioc_default_chat_rules());
    $kb          = get_option('sjioc_kb_text', '');
    $max_tokens  = (int)   get_option('sjioc_chat_max_tokens',  250);
    $temperature = (float) get_option('sjioc_chat_temperature', 0.4);

    $kb_capped = $kb && mb_strlen($kb) > 2000;
    ?>
    <div class="wrap">
        <h1>SJIOC Chat — Settings</h1>
        <form method="post">
            <?php wp_nonce_field('sjioc_chat_nonce'); ?>

            <h2>AI Behavior Rules</h2>
            <p style="color:#555;max-width:720px">Controls how the AI responds. Church name, address, phone, and service times are injected automatically from <a href="<?php echo esc_url(admin_url('customize.php')); ?>">Customizer</a> — no need to repeat them here.</p>
            <textarea name="sjioc_chat_rules" rows="7" style="width:100%;max-width:800px;font-family:monospace;font-size:13px"><?php echo esc_textarea($rules); ?></textarea>

            <h2 style="margin-top:28px">Response Settings</h2>
            <table class="form-table" style="max-width:500px">
                <tr>
                    <th scope="row">Max Response Tokens</th>
                    <td>
                        <input type="number" name="sjioc_chat_max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="50" max="1000" style="width:90px">
                        <p class="description">Max length of AI reply. 200–300 is ideal for a chat widget.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Temperature</th>
                    <td>
                        <input type="number" name="sjioc_chat_temperature" value="<?php echo esc_attr($temperature); ?>" min="0" max="1" step="0.1" style="width:70px">
                        <p class="description">0 = strict/factual &nbsp;|&nbsp; 1 = creative. Recommended: 0.3–0.5 for a church assistant.</p>
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:28px">Actual Token Usage <small style="font-weight:400;color:#888">(real data from LLM responses — last 30 days)</small></h2>
            <?php
            <p style="color:#555;font-size:13px;margin-top:4px">
                Pricing: GPT-4o mini on Azure OpenAI —
                <strong>$0.15 / 1M input tokens</strong> &nbsp;|&nbsp;
                <strong>$0.60 / 1M output tokens</strong>.
                Est. cost = (prompt tokens × $0.15 + completion tokens × $0.60) ÷ 1,000,000.
            </p>
            <?php
            $ut   = $wpdb->prefix . 'sjioc_chat_usage';
            $rows = $wpdb->get_results("SELECT * FROM `{$ut}` ORDER BY usage_date DESC LIMIT 30");
            $mon  = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM(call_count) AS calls, SUM(prompt_tokens) AS prompt, SUM(completion_tokens) AS completion, SUM(total_tokens) AS tokens FROM `{$ut}` WHERE usage_date >= %s",
                gmdate('Y-m-01')
            ));
            if ($rows) :
                $month_label = gmdate('F Y');
                $mon_cost    = ((float)($mon->prompt ?? 0) / 1_000_000 * 0.15)
                             + ((float)($mon->completion ?? 0) / 1_000_000 * 0.60);
            ?>
            <p><strong><?php echo esc_html($month_label); ?>:</strong>
               <?php echo (int) ($mon->calls ?? 0); ?> LLM calls &nbsp;|&nbsp;
               <?php echo number_format((int) ($mon->tokens ?? 0)); ?> tokens &nbsp;|&nbsp;
               est. cost <strong>$<?php echo number_format($mon_cost, 4); ?></strong></p>
            <table class="widefat" style="max-width:680px">
                <thead><tr>
                    <th>Date</th>
                    <th style="text-align:right">LLM Calls</th>
                    <th style="text-align:right">Prompt</th>
                    <th style="text-align:right">Completion</th>
                    <th style="text-align:right">Total</th>
                    <th style="text-align:right">Est. Cost</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $row) :
                    $cost = ((float)$row->prompt_tokens / 1_000_000 * 0.15)
                          + ((float)$row->completion_tokens / 1_000_000 * 0.60);
                ?>
                <tr>
                    <td><?php echo esc_html($row->usage_date); ?></td>
                    <td style="text-align:right"><?php echo (int) $row->call_count; ?></td>
                    <td style="text-align:right"><?php echo number_format((int) $row->prompt_tokens); ?></td>
                    <td style="text-align:right"><?php echo number_format((int) $row->completion_tokens); ?></td>
                    <td style="text-align:right"><?php echo number_format((int) $row->total_tokens); ?></td>
                    <td style="text-align:right">$<?php echo number_format($cost, 4); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p style="color:#888">No LLM calls recorded yet. Usage is tracked from when this feature was deployed.</p>
            <?php endif; ?>

            <h2 style="margin-top:28px">Knowledge Base</h2>
            <p style="color:#555;max-width:720px">Paste concise parish info below. Use structured key:value lines — this uses far fewer tokens than raw paragraphs and the AI reads it just as well. Keep it under 2000 characters for best results.</p>
            <p style="color:#555;max-width:720px"><strong>Current length:</strong> <?php echo mb_strlen($kb); ?> / 2000 characters<?php if ($kb_capped) echo ' — <span style="color:orange">content beyond 2000 chars is not sent to the AI</span>'; ?>.</p>
            <textarea name="sjioc_kb_text" rows="20" style="width:100%;max-width:800px;font-family:monospace;font-size:13px"><?php echo esc_textarea($kb); ?></textarea>

            <br><br>
            <input type="submit" name="sjioc_chat_save" class="button button-primary" value="Save Chat Settings">
        </form>
    </div>
    <?php
}
