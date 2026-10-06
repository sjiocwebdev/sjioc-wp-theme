<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   LECTIONARY — admin uploads one CSV per year (Date, Title, Note, Evening,
   Morning, Before Holy Qurbana, Holy Qurbana). Stored as a single
   non-autoloaded option keyed by date; page-lectionary.php reads it with
   one query. Readings link out to Bible Gateway (English) and bible.com's
   Malayalam Old Version (BSI) — plain links, no frontend HTTP calls.
───────────────────────────────────── */

const SJIOC_LECT_SECTIONS = [
    'evening' => 'Evening',
    'morning' => 'Morning',
    'before'  => 'Before Holy Qurbana',
    'qurbana' => 'Holy Qurbana',
];

add_action('admin_menu', function () {
    add_submenu_page('sjioc', 'Lectionary', 'Lectionary',
        'manage_options', 'sjioc-lectionary', 'sjioc_lectionary_admin_page');
}, 20);

// CSV template download — must run before any HTML output.
add_action('admin_init', function () {
    if (($_GET['page'] ?? '') !== 'sjioc-lectionary'
        || ($_GET['action'] ?? '') !== 'csv_template'
        || !current_user_can('manage_options')
        || !isset($_GET['_wpnonce'])
        || !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'sjioc_lect_template')) {
        return;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lectionary-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Title', 'Note', 'Evening', 'Morning', 'Before Holy Qurbana', 'Holy Qurbana']);
    fputcsv($out, ['2027-01-01', "New Year's Day, Circumcision of our Lord", '', 'Luke 13:6-9', 'John 9:4-7',
        'Genesis 12:1-9; Deuteronomy 10:12-11:1; Ezekiel 18:21-24', '1 John 3:13-18; Romans 2:28-3:8; John 15:5-19']);
    fclose($out);
    exit;
});

function sjioc_get_lectionary(): array {
    $data = get_option('sjioc_lectionary', []);
    return is_array($data) ? $data : [];
}

function sjioc_lect_bible_version(): string {
    return get_option('sjioc_lect_bible_version', '') ?: 'RSV';
}

function sjioc_lect_parse_csv(string $file): array {
    $fh = fopen($file, 'r');
    if (!$fh) return ['entries' => [], 'errors' => ['Could not open file.']];

    $entries = [];
    $errors  = [];
    $row_num = 0;
    while (($row = fgetcsv($fh)) !== false) {
        $row_num++;
        $row[0] = trim(preg_replace('/^\xEF\xBB\xBF/', '', $row[0] ?? ''));
        if ($row[0] === '' || strcasecmp($row[0], 'Date') === 0) continue;

        [$date, $title, $note, $evening, $morning, $before, $qurbana] = array_pad($row, 7, '');
        $ts = strtotime($date);
        if (!$ts) {
            $errors[] = "Row {$row_num}: invalid date '{$date}' — use YYYY-MM-DD.";
            continue;
        }
        $title = sanitize_text_field($title);
        if ($title === '') {
            $errors[] = "Row {$row_num}: Title is required.";
            continue;
        }
        $split = fn($cell) => array_values(array_filter(array_map('sanitize_text_field', preg_split('/[;\r\n]+/', $cell))));
        $entries[date('Y-m-d', $ts)] = [
            'title'   => $title,
            'note'    => sanitize_textarea_field($note),
            'evening' => $split($evening),
            'morning' => $split($morning),
            'before'  => $split($before),
            'qurbana' => $split($qurbana),
        ];
    }
    fclose($fh);
    return ['entries' => $entries, 'errors' => $errors];
}

// Book name (lowercase, as typed after normalising) => [English name, bible.com
// USFM code or null]. null = not in the Malayalam Old Version (deuterocanon),
// so that reading gets an English link only.
function sjioc_lect_books(): array {
    $books = [
        'genesis' => 'GEN', 'exodus' => 'EXO', 'leviticus' => 'LEV', 'numbers' => 'NUM', 'deuteronomy' => 'DEU',
        'joshua' => 'JOS', 'judges' => 'JDG', 'ruth' => 'RUT', '1 samuel' => '1SA', '2 samuel' => '2SA',
        '1 kings' => '1KI', '2 kings' => '2KI', '1 chronicles' => '1CH', '2 chronicles' => '2CH', 'ezra' => 'EZR',
        'nehemiah' => 'NEH', 'esther' => 'EST', 'job' => 'JOB', 'psalms' => 'PSA', 'proverbs' => 'PRO',
        'ecclesiastes' => 'ECC', 'song of solomon' => 'SNG', 'isaiah' => 'ISA', 'jeremiah' => 'JER',
        'lamentations' => 'LAM', 'ezekiel' => 'EZK', 'daniel' => 'DAN', 'hosea' => 'HOS', 'joel' => 'JOL',
        'amos' => 'AMO', 'obadiah' => 'OBA', 'jonah' => 'JON', 'micah' => 'MIC', 'nahum' => 'NAM',
        'habakkuk' => 'HAB', 'zephaniah' => 'ZEP', 'haggai' => 'HAG', 'zechariah' => 'ZEC', 'malachi' => 'MAL',
        'matthew' => 'MAT', 'mark' => 'MRK', 'luke' => 'LUK', 'john' => 'JHN', 'acts' => 'ACT', 'romans' => 'ROM',
        '1 corinthians' => '1CO', '2 corinthians' => '2CO', 'galatians' => 'GAL', 'ephesians' => 'EPH',
        'philippians' => 'PHP', 'colossians' => 'COL', '1 thessalonians' => '1TH', '2 thessalonians' => '2TH',
        '1 timothy' => '1TI', '2 timothy' => '2TI', 'titus' => 'TIT', 'philemon' => 'PHM', 'hebrews' => 'HEB',
        'james' => 'JAS', '1 peter' => '1PE', '2 peter' => '2PE', '1 john' => '1JN', '2 john' => '2JN',
        '3 john' => '3JN', 'jude' => 'JUD', 'revelation' => 'REV',
    ];
    $out = [];
    foreach ($books as $name => $usfm) $out[$name] = [ucwords($name), $usfm];

    $aliases = [
        'mathew' => 'matthew', 'ester' => 'esther', 'zachariah' => 'zechariah', 'revelations' => 'revelation',
        'psalm' => 'psalms', 'song of songs' => 'song of solomon', 'acts of the apostles' => 'acts',
    ];
    foreach ($aliases as $alias => $name) $out[$alias] = $out[$name];

    foreach ([
        'wisdom' => 'Wisdom', 'great wisdom' => 'Wisdom', 'wisdom of solomon' => 'Wisdom',
        'barazeera' => 'Sirach', 'sirach' => 'Sirach', 'ecclesiasticus' => 'Sirach', 'ben sira' => 'Sirach',
        '1 maccabees' => '1 Maccabees', '2 maccabees' => '2 Maccabees', 'tobit' => 'Tobit',
        'judith' => 'Judith', 'baruch' => 'Baruch',
    ] as $name => $english) {
        $out[$name] = [$english, null];
    }
    return $out;
}

// "St. Luke 13: 6 - 9" / "II Kings 2:1-15" => ['en' => url, 'ml' => url|null], or null if unrecognised.
function sjioc_lect_links(string $ref): ?array {
    static $books = null;
    $books ??= sjioc_lect_books();

    $r = preg_replace('/^(St\.?|Saint)\s+/i', '', trim($ref));
    $r = preg_replace(['/^III\s+/', '/^II\s+/', '/^I\s+/'], ['3 ', '2 ', '1 '], $r);
    if (!preg_match('/^(\d?\s*[A-Za-z][A-Za-z.\s]*?)\s*(\d+)\s*(?::\s*(.+))?$/', $r, $m)) return null;

    $book = $books[strtolower(preg_replace('/\s+/', ' ', trim($m[1], " .")))] ?? null;
    if (!$book) return null;

    $chapter = $m[2];
    $verses  = isset($m[3]) ? preg_replace('/\s+/', '', $m[3]) : '';
    $search  = $book[0] . ' ' . $chapter . ($verses !== '' ? ':' . $verses : '');

    $ml = null;
    if ($book[1]) {
        // bible.com can't open a range that crosses chapters — fall back to the whole starting chapter.
        $ml = 'https://www.bible.com/bible/1693/' . $book[1] . '.' . $chapter
            . (preg_match('/^\d+(-\d+)?$/', $verses) ? '.' . $verses : '');
    }
    return [
        'en' => 'https://www.biblegateway.com/passage/?search=' . rawurlencode($search) . '&version=' . rawurlencode(sjioc_lect_bible_version()),
        'ml' => $ml,
    ];
}

function sjioc_lectionary_admin_page(): void {
    if (!current_user_can('manage_options')) return;

    $notice = '';
    if (isset($_POST['sjioc_lect_upload'])) {
        check_admin_referer('sjioc_lect_admin');
        $name = sanitize_file_name($_FILES['lect_csv']['name'] ?? '');
        if (empty($_FILES['lect_csv']['tmp_name']) || !is_uploaded_file($_FILES['lect_csv']['tmp_name'])) {
            $notice = '<div class="notice notice-error"><p>No file selected or upload failed.</p></div>';
        } elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') {
            $notice = '<div class="notice notice-error"><p>Please upload a .csv file (in Excel, use File → Save As → CSV UTF-8).</p></div>';
        } else {
            $result = sjioc_lect_parse_csv($_FILES['lect_csv']['tmp_name']);
            if ($result['entries']) {
                // Replace every year present in the file; other years stay as they are.
                $years = array_unique(array_map(fn($d) => substr($d, 0, 4), array_keys($result['entries'])));
                $data  = array_filter(sjioc_get_lectionary(), fn($d) => !in_array(substr($d, 0, 4), $years, true), ARRAY_FILTER_USE_KEY);
                $data  = array_merge($data, $result['entries']);
                ksort($data);
                update_option('sjioc_lectionary', $data, false);
                $msg = count($result['entries']) . ' day(s) uploaded for ' . implode(', ', $years) . '.';
                if ($result['errors']) $msg .= ' Skipped: ' . implode(' | ', array_slice($result['errors'], 0, 5));
                $notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            } else {
                $notice = '<div class="notice notice-error"><p>' . esc_html('No valid rows found. ' . implode(' | ', array_slice($result['errors'], 0, 5))) . '</p></div>';
            }
        }
    }

    if (isset($_POST['sjioc_lect_settings'])) {
        check_admin_referer('sjioc_lect_admin');
        $version = strtoupper(sanitize_text_field(wp_unslash($_POST['lect_version'] ?? '')));
        update_option('sjioc_lect_bible_version', preg_match('/^[A-Z0-9-]{2,12}$/', $version) ? $version : 'RSV');
        update_option('sjioc_lect_credit', sanitize_text_field(wp_unslash($_POST['lect_credit'] ?? '')));
        $notice = '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    if (isset($_POST['sjioc_lect_delete_year'])) {
        check_admin_referer('sjioc_lect_admin');
        $year = (string) absint($_POST['sjioc_lect_delete_year']);
        update_option('sjioc_lectionary', array_filter(sjioc_get_lectionary(), fn($d) => substr($d, 0, 4) !== $year, ARRAY_FILTER_USE_KEY), false);
        $notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html($year) . ' removed.</p></div>';
    }

    $years = [];
    foreach (array_keys(sjioc_get_lectionary()) as $d) {
        $years[substr($d, 0, 4)] = ($years[substr($d, 0, 4)] ?? 0) + 1;
    }
    $template_url = wp_nonce_url(admin_url('admin.php?page=sjioc-lectionary&action=csv_template'), 'sjioc_lect_template');
    ?>
    <div class="wrap">
    <h1>Lectionary</h1>
    <?php echo $notice; ?>

    <h2 class="title">Upload a Year</h2>
    <p>One row per day: <strong>Date, Title, Note, Evening, Morning, Before Holy Qurbana, Holy Qurbana</strong>.
       Put several readings in one cell separated by a semicolon, e.g. <code>Genesis 12:1-9; Isaiah 40:1-8</code>.
       <a href="<?php echo esc_url($template_url); ?>">Download template</a>.</p>
    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('sjioc_lect_admin'); ?>
      <p><input type="file" name="lect_csv" accept=".csv,text/csv" required>
      <?php submit_button('Upload Lectionary', 'primary', 'sjioc_lect_upload', false); ?></p>
      <p class="description">Uploading replaces every year that appears in the file. Other years are kept.</p>
    </form>

    <hr>

    <h2 class="title">Years on the Site</h2>
    <?php if ($years) : ?>
    <form method="post">
      <?php wp_nonce_field('sjioc_lect_admin'); ?>
      <table class="widefat striped" style="max-width:420px">
        <thead><tr><th>Year</th><th>Days</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($years as $year => $count) : ?>
          <tr>
            <td><?php echo esc_html($year); ?></td>
            <td><?php echo (int) $count; ?></td>
            <td><button type="submit" class="button-link-delete" name="sjioc_lect_delete_year" value="<?php echo esc_attr($year); ?>">Remove</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </form>
    <?php else : ?>
    <p><em>No lectionary uploaded yet — the Lectionary page shows a "coming soon" message until one is.</em></p>
    <?php endif; ?>

    <hr>

    <h2 class="title">Settings</h2>
    <form method="post">
      <?php wp_nonce_field('sjioc_lect_admin'); ?>
      <table class="form-table" style="max-width:700px"><tbody>
        <tr>
          <th><label for="lect_version">English Bible version</label></th>
          <td><input type="text" id="lect_version" name="lect_version" value="<?php echo esc_attr(sjioc_lect_bible_version()); ?>" class="small-text">
            <p class="description">Bible Gateway version code. RSV includes the deuterocanonical books (Wisdom, Sirach, Maccabees) used in the lectionary.</p></td>
        </tr>
        <tr>
          <th><label for="lect_credit">Source / credit line</label></th>
          <td><input type="text" id="lect_credit" name="lect_credit" value="<?php echo esc_attr(get_option('sjioc_lect_credit', '')); ?>" class="regular-text">
            <p class="description">Shown under the lectionary, e.g. where this year's readings came from.</p></td>
        </tr>
      </tbody></table>
      <?php submit_button('Save Settings', 'secondary', 'sjioc_lect_settings'); ?>
    </form>
    </div>
    <?php
}
