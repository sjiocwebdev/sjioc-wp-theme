<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   IMPORT MEMBERS ADMIN PAGE
───────────────────────────────────── */
function sjioc_import_page() {
    if (!current_user_can('manage_options')) return;

    $result = null;

    if (isset($_POST['sjioc_import']) && check_admin_referer('sjioc_import_nonce')) {
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
                $result = sjioc_import_rows($rows, $on_dup);
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>📥 Import Members</h1>

        <?php if ($result): ?>
            <?php if (isset($result['error'])): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($result['error']); ?></p></div>
            <?php else: ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Import complete.</strong>
                        <?php echo (int) $result['inserted']; ?> inserted &nbsp;·&nbsp;
                        <?php echo (int) $result['updated'];  ?> updated &nbsp;·&nbsp;
                        <?php echo (int) $result['skipped'];  ?> skipped.
                    </p>
                </div>
                <?php if (!empty($result['row_errors'])): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><strong>Rows with issues (skipped):</strong></p>
                    <ul style="margin-left:16px;list-style:disc">
                        <?php foreach ($result['row_errors'] as $e): ?>
                            <li><?php echo esc_html($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width:620px">
            <?php wp_nonce_field('sjioc_import_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="import_file">File</label></th>
                    <td>
                        <input id="import_file" type="file" name="import_file" accept=".csv,.xlsx" required>
                        <p class="description">Excel (.xlsx) or CSV (.csv) — max 5 MB.</p>
                        <?php if (!class_exists('ZipArchive')): ?>
                        <p class="description" style="color:#b32d2e">⚠ ZipArchive not available on this server — .xlsx files will not parse. Use .csv instead.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Duplicates</th>
                    <td>
                        <label style="display:block;margin-bottom:6px">
                            <input type="radio" name="on_dup" value="update" checked>
                            Update existing rows (matched by Cardex No + Member #)
                        </label>
                        <label>
                            <input type="radio" name="on_dup" value="skip">
                            Skip duplicates — only insert new rows
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Upload & Import', 'primary', 'sjioc_import'); ?>
        </form>

        <hr style="margin:28px 0">
        <h3>Expected Column Headers</h3>
        <p>The first row of your file must be a header row. Column order does not matter.</p>
        <table class="widefat striped" style="max-width:480px">
            <thead><tr><th>Header</th><th>Required</th><th>Notes</th></tr></thead>
            <tbody>
                <tr><td>Cardex No</td><td>✔ Yes</td><td></td></tr>
                <tr><td>Member Seq <em>(or Sl)</em></td><td>✔ Yes</td><td>Row number within family</td></tr>
                <tr><td>First Name</td><td>✔ Yes</td><td></td></tr>
                <tr><td>Middle Name</td><td>No</td><td></td></tr>
                <tr><td>Last Name</td><td>No</td><td></td></tr>
                <tr><td>Gender</td><td>No</td><td>M or F</td></tr>
                <tr><td>Date of Birth</td><td>No</td><td>DD/MM/YYYY or YYYY-MM-DD</td></tr>
                <tr><td>Marital Status</td><td>No</td><td>M / S / W / D</td></tr>
                <tr><td>Wedding Date</td><td>No</td><td>DD/MM/YYYY or YYYY-MM-DD</td></tr>
                <tr><td>Phone Number</td><td>No</td><td></td></tr>
                <tr><td>Email Address</td><td>No</td><td></td></tr>
                <tr><td>Address</td><td>No</td><td></td></tr>
                <tr><td>City</td><td>No</td><td></td></tr>
                <tr><td>State</td><td>No</td><td>2-letter, e.g. PA</td></tr>
                <tr><td>Zip Code</td><td>No</td><td></td></tr>
                <tr><td>Country</td><td>No</td><td>Defaults to USA</td></tr>
            </tbody>
        </table>
    </div>
    <?php
}

/* ─────────────────────────────────────
   ROW PROCESSOR
───────────────────────────────────── */
function sjioc_import_rows(array $rows, string $on_dup): array {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';

    if (count($rows) < 2) {
        return ['error' => 'File has no data rows (only a header or is empty).'];
    }

    // Normalize headers and map to DB columns
    $headers = array_map('sjioc_normalize_header', $rows[0]);
    $aliases  = [
        'cardex_no'      => ['cardex_no', 'cardex'],
        'member_seq'     => ['member_seq', 'sl', 'seq', 'member_number', 'member_no'],
        'first_name'     => ['first_name'],
        'middle_name'    => ['middle_name'],
        'last_name'      => ['last_name'],
        'gender'         => ['gender'],
        'date_of_birth'  => ['date_of_birth', 'dob', 'birth_date'],
        'marital_status' => ['marital_status'],
        'wedding_date'   => ['wedding_date'],
        'phone_number'   => ['phone_number', 'phone', 'mobile'],
        'email'          => ['email', 'email_address'],
        'address'        => ['address'],
        'city'           => ['city'],
        'state'          => ['state'],
        'zip_code'       => ['zip_code', 'zip', 'postal_code'],
        'country'        => ['country'],
    ];

    $col_map = [];
    foreach ($aliases as $db_col => $names) {
        foreach ($names as $name) {
            $idx = array_search($name, $headers, true);
            if ($idx !== false) { $col_map[$db_col] = $idx; break; }
        }
    }

    if (!isset($col_map['cardex_no'], $col_map['first_name'])) {
        return ['error' => 'Required columns "Cardex No" and "First Name" not found. Check your header row.'];
    }

    $get = fn($row, $col) => isset($col_map[$col]) ? trim((string) ($row[$col_map[$col]] ?? '')) : '';

    $inserted   = 0;
    $updated    = 0;
    $skipped    = 0;
    $row_errors = [];

    foreach (array_slice($rows, 1) as $line => $row) {
        $cardex = $get($row, 'cardex_no');
        $fname  = $get($row, 'first_name');
        if ($cardex === '' || $fname === '') { $skipped++; continue; }

        $seq = (int) $get($row, 'member_seq');
        if ($seq < 1) $seq = 1;

        $gender = strtoupper($get($row, 'gender'));
        if (!in_array($gender, ['M', 'F'], true)) $gender = 'M';

        $data = [
            'cardex_no'      => $cardex,
            'member_seq'     => $seq,
            'first_name'     => $fname,
            'middle_name'    => $get($row, 'middle_name'),
            'last_name'      => $get($row, 'last_name'),
            'gender'         => $gender,
            'date_of_birth'  => sjioc_import_parse_date($get($row, 'date_of_birth')),
            'marital_status' => sjioc_import_normalize_marital($get($row, 'marital_status')),
            'wedding_date'   => sjioc_import_parse_date($get($row, 'wedding_date')),
            'phone_number'   => $get($row, 'phone_number'),
            'email'          => sanitize_email($get($row, 'email')),
            'address'        => $get($row, 'address'),
            'city'           => $get($row, 'city'),
            'state'          => strtoupper($get($row, 'state')),
            'zip_code'       => $get($row, 'zip_code'),
            'country'        => $get($row, 'country') ?: 'USA',
            'is_active'      => 1,
        ];

        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE cardex_no = %s AND member_seq = %d",
                $data['cardex_no'], $data['member_seq']
            )
        );

        if ($existing_id) {
            if ($on_dup === 'skip') { $skipped++; continue; }
            $ok = $wpdb->update($table, $data, ['id' => $existing_id]);
            if ($ok !== false) { $updated++; } else {
                $row_errors[] = "Row " . ($line + 2) . " ($cardex/$seq): " . $wpdb->last_error;
            }
        } else {
            $ok = $wpdb->insert($table, $data);
            if ($ok) { $inserted++; } else {
                $row_errors[] = "Row " . ($line + 2) . " ($cardex/$seq): " . $wpdb->last_error;
            }
        }
    }

    return compact('inserted', 'updated', 'skipped', 'row_errors');
}

/* ─────────────────────────────────────
   HELPERS
───────────────────────────────────── */
function sjioc_normalize_header(string $h): string {
    $h = strtolower(trim($h));
    $h = preg_replace('/[^a-z0-9]+/', '_', $h);
    return trim($h, '_');
}

function sjioc_import_parse_date(string $val): ?string {
    $val = trim($val);
    if ($val === '' || $val === '0' || $val === '00/00/0000' || $val === '0000-00-00') return null;

    // DD/MM/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
        if ($m[3] < 1900 || $m[2] > 12 || $m[1] > 31) return null;
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    // YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;

    // Excel date serial (numeric, plausible range: 1900–2100)
    if (is_numeric($val)) {
        $serial = (float) $val;
        if ($serial > 1 && $serial < 80000) {
            // Adjust for Excel's leap-year bug
            if ($serial > 60) $serial--;
            $ts = (int) round(($serial - 25569) * 86400);
            return date('Y-m-d', $ts);
        }
        return null;
    }

    // Fallback: PHP strtotime
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : null;
}

function sjioc_import_normalize_marital(string $val): string {
    $val = strtoupper(trim($val));
    if (in_array($val, ['M', 'S', 'W', 'D'], true)) return $val;
    $first = $val[0] ?? '';
    return in_array($first, ['M', 'S', 'W', 'D'], true) ? $first : 'S';
}

/* ─────────────────────────────────────
   XLSX PARSER (no external library)
   Reads sheet1, shared strings, returns
   array of rows (each row = array of strings)
───────────────────────────────────── */
function sjioc_parse_xlsx(string $path): array {
    if (!class_exists('ZipArchive')) return [];

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];

    // Shared strings
    $strings = [];
    $ss_xml  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_xml) {
        $ss = @simplexml_load_string($ss_xml);
        if ($ss) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } else {
                    $parts = [];
                    foreach ($si->r as $r) $parts[] = (string) $r->t;
                    $strings[] = implode('', $parts);
                }
            }
        }
    }

    // Sheet 1
    $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheet_xml) return [];

    $sheet = @simplexml_load_string($sheet_xml);
    if (!$sheet) return [];

    $rows    = [];
    $max_col = 0;

    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            // Parse column index from ref (e.g. "AB3" → col index 27)
            preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
            $col_idx = 0;
            foreach (str_split($m[1]) as $ch) {
                $col_idx = $col_idx * 26 + (ord($ch) - 64);
            }
            $col_idx--; // 0-indexed

            $val = '';
            if ((string) $c['t'] === 'inlineStr' && isset($c->is->t)) {
                $val = (string) $c->is->t;  // inline string — value in <is><t> not <v>
            } elseif (isset($c->v)) {
                $val = (string) $c->v;
                if ((string) $c['t'] === 's') {
                    $val = $strings[(int) $val] ?? '';
                }
            }
            $cells[$col_idx] = $val;
            $max_col = max($max_col, $col_idx);
        }

        // Fill sparse cells so every row is the same width
        $filled = [];
        for ($i = 0; $i <= $max_col; $i++) {
            $filled[] = $cells[$i] ?? '';
        }
        $rows[] = $filled;
    }

    return $rows;
}

/* ─────────────────────────────────────
   CSV PARSER
───────────────────────────────────── */
function sjioc_parse_csv(string $path): array {
    $rows = [];
    $fh   = fopen($path, 'r');
    if (!$fh) return [];
    // Skip BOM if present
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($fh);
    while (($row = fgetcsv($fh)) !== false) {
        $rows[] = array_map('trim', $row);
    }
    fclose($fh);
    return $rows;
}
