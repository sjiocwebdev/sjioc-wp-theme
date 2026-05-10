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
        'sjioc', 'Events Calendar', 'Events',
        'manage_options', 'sjioc-events', 'sjioc_events_admin_page'
    );
    add_submenu_page(
        'sjioc', 'Import Events', 'Import Events',
        'manage_options', 'sjioc-import-events', 'sjioc_import_events_page'
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
   CHAT SETTINGS PAGE
───────────────────────────────────── */
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
