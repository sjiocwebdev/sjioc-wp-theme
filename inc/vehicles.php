<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   AUTO-CREATE TABLE
───────────────────────────────────── */
add_action('after_switch_theme', 'sjioc_create_vehicles_table');

function sjioc_create_vehicles_table(): void {
    global $wpdb;
    $table   = $wpdb->prefix . 'sjioc_vehicles';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
        license_plate VARCHAR(20)     NOT NULL,
        owner_name    VARCHAR(100)    NOT NULL,
        owner_phone   VARCHAR(20)     NOT NULL DEFAULT '',
        owner_email   VARCHAR(100)    DEFAULT NULL,
        vehicle_desc  VARCHAR(150)    DEFAULT NULL,
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_plate (license_plate)
    ) ENGINE=InnoDB {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* ─────────────────────────────────────
   VEHICLES LIST / MANAGE PAGE
───────────────────────────────────── */
function sjioc_vehicles_admin_page(): void {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_vehicles';

    $notice = '';

    // ── Delete ─────────────────────────────────────────
    if (isset($_POST['sjioc_vehicle_delete']) && check_admin_referer('sjioc_vehicle_delete')) {
        $id = (int) ($_POST['vehicle_id'] ?? 0);
        if ($id) { $wpdb->delete($table, ['id' => $id], ['%d']); }
        $notice = '<div class="notice notice-success is-dismissible"><p>Vehicle deleted.</p></div>';
    }

    // ── Add ────────────────────────────────────────────
    if (isset($_POST['sjioc_vehicle_add']) && check_admin_referer('sjioc_vehicle_add')) {
        $plate = strtoupper(sanitize_text_field(wp_unslash($_POST['veh_plate'] ?? '')));
        $name  = sanitize_text_field(wp_unslash($_POST['veh_name']  ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['veh_phone'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['veh_email'] ?? ''));
        $desc  = sanitize_text_field(wp_unslash($_POST['veh_desc']  ?? ''));

        if (!$plate || !$name) {
            $notice = '<div class="notice notice-error is-dismissible"><p>License Plate and Owner Name are required.</p></div>';
        } else {
            $ok = $wpdb->insert($table, [
                'license_plate' => $plate,
                'owner_name'    => $name,
                'owner_phone'   => $phone,
                'owner_email'   => $email ?: null,
                'vehicle_desc'  => $desc  ?: null,
            ]);
            if ($ok) {
                $notice = '<div class="notice notice-success is-dismissible"><p>Vehicle added.</p></div>';
            } else {
                $err = $wpdb->last_error;
                $notice = '<div class="notice notice-error is-dismissible"><p>Could not add — ' .
                    (str_contains($err, 'Duplicate') ? 'plate <strong>' . esc_html($plate) . '</strong> already exists.' : esc_html($err)) .
                    '</p></div>';
            }
        }
    }

    // ── Edit save ──────────────────────────────────────
    if (isset($_POST['sjioc_vehicle_edit']) && check_admin_referer('sjioc_vehicle_edit')) {
        $id    = (int) ($_POST['vehicle_id'] ?? 0);
        $plate = strtoupper(sanitize_text_field(wp_unslash($_POST['veh_plate'] ?? '')));
        $name  = sanitize_text_field(wp_unslash($_POST['veh_name']  ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['veh_phone'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['veh_email'] ?? ''));
        $desc  = sanitize_text_field(wp_unslash($_POST['veh_desc']  ?? ''));

        if ($id && $plate && $name) {
            $wpdb->update($table, [
                'license_plate' => $plate,
                'owner_name'    => $name,
                'owner_phone'   => $phone,
                'owner_email'   => $email ?: null,
                'vehicle_desc'  => $desc  ?: null,
            ], ['id' => $id]);
            $notice = '<div class="notice notice-success is-dismissible"><p>Vehicle updated.</p></div>';
        }
    }

    // ── Search & pagination ────────────────────────────
    $search  = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    $per     = 30;
    $page    = max(1, (int) ($_GET['paged'] ?? 1));
    $offset  = ($page - 1) * $per;

    $where = $search
        ? $wpdb->prepare("WHERE license_plate LIKE %s OR owner_name LIKE %s", "%{$search}%", "%{$search}%")
        : '';

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
    $rows  = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY owner_name ASC LIMIT {$per} OFFSET {$offset}");
    $pages = (int) ceil($total / $per);

    // ── Inline edit target ─────────────────────────────
    $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
    $edit_row = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id)) : null;
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">🚗 Vehicle Registry</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-import-vehicles')); ?>" class="page-title-action">Import CSV / Excel</a>
        <hr class="wp-header-end">

        <?php echo $notice; ?>

        <!-- ── Add / Edit form ───────────────────────────── -->
        <?php
        $form_action = $edit_row ? 'sjioc_vehicle_edit' : 'sjioc_vehicle_add';
        $form_nonce  = $edit_row ? 'sjioc_vehicle_edit' : 'sjioc_vehicle_add';
        $btn_label   = $edit_row ? 'Update Vehicle' : 'Add Vehicle';
        $v = $edit_row ?? (object)['id'=>0,'license_plate'=>'','owner_name'=>'','owner_phone'=>'','owner_email'=>'','vehicle_desc'=>''];
        ?>
        <div style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin:16px 0;max-width:700px">
            <h3 style="margin-top:0"><?php echo $edit_row ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h3>
            <form method="post">
                <?php wp_nonce_field($form_nonce); ?>
                <?php if ($edit_row): ?><input type="hidden" name="vehicle_id" value="<?php echo esc_attr($v->id); ?>"><?php endif; ?>
                <table class="form-table" style="margin:0">
                    <tr>
                        <th style="width:160px"><label for="veh_plate">License Plate *</label></th>
                        <td><input id="veh_plate" type="text" name="veh_plate" value="<?php echo esc_attr($v->license_plate); ?>" style="width:160px;text-transform:uppercase" required placeholder="e.g. ABC 1234"></td>
                    </tr>
                    <tr>
                        <th><label for="veh_name">Owner Name *</label></th>
                        <td><input id="veh_name" type="text" name="veh_name" value="<?php echo esc_attr($v->owner_name); ?>" style="width:280px" required></td>
                    </tr>
                    <tr>
                        <th><label for="veh_phone">Phone</label></th>
                        <td><input id="veh_phone" type="text" name="veh_phone" value="<?php echo esc_attr($v->owner_phone); ?>" style="width:200px" placeholder="(610) 555-0100"></td>
                    </tr>
                    <tr>
                        <th><label for="veh_email">Email</label></th>
                        <td><input id="veh_email" type="email" name="veh_email" value="<?php echo esc_attr($v->owner_email ?? ''); ?>" style="width:280px"></td>
                    </tr>
                    <tr>
                        <th><label for="veh_desc">Vehicle Description</label></th>
                        <td><input id="veh_desc" type="text" name="veh_desc" value="<?php echo esc_attr($v->vehicle_desc ?? ''); ?>" style="width:320px" placeholder="e.g. Blue Toyota Camry 2022"></td>
                    </tr>
                </table>
                <p style="margin-top:12px">
                    <?php submit_button($btn_label, 'primary', $form_action, false); ?>
                    <?php if ($edit_row): ?>
                    &nbsp;<a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-vehicles')); ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- ── Search bar ────────────────────────────────── -->
        <form method="get" style="margin-bottom:12px">
            <input type="hidden" name="page" value="sjioc-vehicles">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search plate or owner…" style="width:260px">
            <?php submit_button('Search', 'secondary', '', false); ?>
            <?php if ($search): ?> <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-vehicles')); ?>">Clear</a><?php endif; ?>
        </form>

        <!-- ── Vehicle table ─────────────────────────────── -->
        <p style="color:#666;font-size:13px"><?php echo $total; ?> vehicle<?php echo $total !== 1 ? 's' : ''; ?> <?php echo $search ? 'matching' : 'total'; ?>.</p>
        <table class="widefat striped" style="max-width:900px">
            <thead>
                <tr>
                    <th>License Plate</th>
                    <th>Owner</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Vehicle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" style="text-align:center;color:#999;padding:24px">No vehicles found.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><strong><?php echo esc_html($r->license_plate); ?></strong></td>
                    <td><?php echo esc_html($r->owner_name); ?></td>
                    <td><?php echo $r->owner_phone ? '<a href="tel:'.esc_attr(preg_replace('/\D/','',$r->owner_phone)).'">'.esc_html($r->owner_phone).'</a>' : '—'; ?></td>
                    <td><?php echo $r->owner_email ? '<a href="mailto:'.esc_attr($r->owner_email).'">'.esc_html($r->owner_email).'</a>' : '—'; ?></td>
                    <td><?php echo esc_html($r->vehicle_desc ?? '—'); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-vehicles&edit='.$r->id)); ?>" class="button button-small">Edit</a>
                        &nbsp;
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this vehicle?')">
                            <?php wp_nonce_field('sjioc_vehicle_delete'); ?>
                            <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($r->id); ?>">
                            <button type="submit" name="sjioc_vehicle_delete" class="button button-small" style="color:#b32d2e">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- ── Pagination ────────────────────────────────── -->
        <?php if ($pages > 1): ?>
        <div style="margin-top:12px">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-vehicles&paged='.$p.($search?'&s='.urlencode($search):''))); ?>"
               class="button <?php echo $p === $page ? 'button-primary' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────
   VEHICLE IMPORT PAGE
───────────────────────────────────── */
function sjioc_import_vehicles_page(): void {
    if (!current_user_can('manage_options')) return;

    $result = null;

    if (isset($_POST['sjioc_vehicle_import']) && check_admin_referer('sjioc_vehicle_import_nonce')) {
        if (empty($_FILES['import_file']['tmp_name'])) {
            $result = ['error' => 'No file uploaded.'];
        } else {
            $file = $_FILES['import_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['csv', 'xlsx'], true)) {
                $result = ['error' => 'Only .csv and .xlsx files are accepted.'];
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $result = ['error' => 'File exceeds 5 MB limit.'];
            } else {
                $on_dup = ($_POST['on_dup'] ?? 'update') === 'skip' ? 'skip' : 'update';
                $rows   = $ext === 'xlsx'
                    ? sjioc_parse_xlsx($file['tmp_name'])
                    : sjioc_parse_csv($file['tmp_name']);
                $result = sjioc_import_vehicle_rows($rows, $on_dup);
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>📥 Import Vehicles</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-vehicles')); ?>" class="page-title-action">← Back to Vehicle Registry</a>
        <hr class="wp-header-end" style="margin-bottom:16px">

        <?php if ($result): ?>
            <?php if (isset($result['error'])): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($result['error']); ?></p></div>
            <?php else: ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Import complete.</strong>
                        <?php echo (int)$result['inserted']; ?> inserted &nbsp;·&nbsp;
                        <?php echo (int)$result['updated'];  ?> updated &nbsp;·&nbsp;
                        <?php echo (int)$result['skipped'];  ?> skipped.
                    </p>
                </div>
                <?php if (!empty($result['row_errors'])): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><strong>Rows with issues (skipped):</strong></p>
                    <ul style="margin-left:16px;list-style:disc">
                        <?php foreach ($result['row_errors'] as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width:620px">
            <?php wp_nonce_field('sjioc_vehicle_import_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="import_file">File</label></th>
                    <td>
                        <input id="import_file" type="file" name="import_file" accept=".csv,.xlsx" required>
                        <p class="description">Excel (.xlsx) or CSV (.csv) — max 5 MB.</p>
                        <?php if (!class_exists('ZipArchive')): ?>
                        <p class="description" style="color:#b32d2e">⚠ ZipArchive not available — .xlsx will not parse. Use .csv instead.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Duplicates</th>
                    <td>
                        <label style="display:block;margin-bottom:6px">
                            <input type="radio" name="on_dup" value="update" checked>
                            Update existing rows (matched by License Plate)
                        </label>
                        <label>
                            <input type="radio" name="on_dup" value="skip">
                            Skip duplicates — only insert new rows
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Upload & Import', 'primary', 'sjioc_vehicle_import'); ?>
        </form>

        <hr style="margin:28px 0">
        <h3>Expected Column Headers</h3>
        <p>First row must be a header row. Column order does not matter. Headers are case-insensitive.</p>
        <table class="widefat striped" style="max-width:480px">
            <thead><tr><th>Header</th><th>Required</th><th>Notes</th></tr></thead>
            <tbody>
                <tr><td>License Plate</td><td>✔ Yes</td><td>Spaces and hyphens are ignored when matching</td></tr>
                <tr><td>Owner Name</td><td>✔ Yes</td><td></td></tr>
                <tr><td>Owner Phone <em>(or Phone)</em></td><td>No</td><td></td></tr>
                <tr><td>Owner Email <em>(or Email)</em></td><td>No</td><td></td></tr>
                <tr><td>Vehicle Description <em>(or Vehicle)</em></td><td>No</td><td>e.g. Blue Toyota Camry 2022</td></tr>
            </tbody>
        </table>

        <hr style="margin:28px 0">
        <h3>Sample CSV</h3>
        <pre style="background:#f6f7f7;padding:12px;font-size:12px;max-width:620px">License Plate,Owner Name,Owner Phone,Owner Email,Vehicle Description
ABC 1234,Thomas Abraham,(610) 555-0182,thomas@example.com,Blue Toyota Camry 2022
XYZ 9876,Susan Mathew,(610) 555-0247,,Silver Honda CR-V</pre>
    </div>
    <?php
}

/* ─────────────────────────────────────
   IMPORT ROW PROCESSOR
───────────────────────────────────── */
function sjioc_import_vehicle_rows(array $rows, string $on_dup): array {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_vehicles';

    if (count($rows) < 2) {
        return ['error' => 'File has no data rows (only a header or is empty).'];
    }

    $headers = array_map('sjioc_normalize_header', $rows[0]);
    $aliases = [
        'license_plate' => ['license_plate', 'plate', 'license', 'reg', 'registration'],
        'owner_name'    => ['owner_name', 'owner', 'name', 'full_name'],
        'owner_phone'   => ['owner_phone', 'phone', 'mobile', 'contact'],
        'owner_email'   => ['owner_email', 'email'],
        'vehicle_desc'  => ['vehicle_desc', 'vehicle', 'description', 'vehicle_description', 'car'],
    ];

    $col_map = [];
    foreach ($aliases as $db_col => $names) {
        foreach ($names as $name) {
            $idx = array_search($name, $headers, true);
            if ($idx !== false) { $col_map[$db_col] = $idx; break; }
        }
    }

    if (!isset($col_map['license_plate'], $col_map['owner_name'])) {
        return ['error' => 'Required columns "License Plate" and "Owner Name" not found. Check your header row.'];
    }

    $get = fn($row, $col) => isset($col_map[$col]) ? trim((string)($row[$col_map[$col]] ?? '')) : '';

    $inserted = $updated = $skipped = 0;
    $row_errors = [];

    foreach (array_slice($rows, 1) as $line => $row) {
        $plate = strtoupper(preg_replace('/[\s\-]/', '', $get($row, 'license_plate')));
        $name  = $get($row, 'owner_name');

        if (!$plate || !$name) { $skipped++; continue; }

        $data = [
            'license_plate' => $plate,
            'owner_name'    => $name,
            'owner_phone'   => $get($row, 'owner_phone'),
            'owner_email'   => sanitize_email($get($row, 'owner_email')) ?: null,
            'vehicle_desc'  => $get($row, 'vehicle_desc') ?: null,
        ];

        $existing_id = (int)$wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE license_plate = %s", $plate)
        );

        if ($existing_id) {
            if ($on_dup === 'skip') { $skipped++; continue; }
            $ok = $wpdb->update($table, $data, ['id' => $existing_id]);
            $ok !== false ? $updated++ : ($row_errors[] = "Row ".($line+2).": ".$wpdb->last_error);
        } else {
            $ok = $wpdb->insert($table, $data);
            $ok ? $inserted++ : ($row_errors[] = "Row ".($line+2).": ".$wpdb->last_error);
        }
    }

    return compact('inserted', 'updated', 'skipped', 'row_errors');
}
