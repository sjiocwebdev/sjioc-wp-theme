<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   WEEKLY BIBLE VERSE — admin uploads a CSV (Reference, Verse Text),
   one new verse is shown each week on the home page hero. Rotation
   rides on the SAME weekly cron already used for Celebrations
   (sjioc_celebrations_cron) — no new scheduled event is created.
───────────────────────────────────── */
function sjioc_bible_verse_admin_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['sjioc_bible_verse_save']) && check_admin_referer('sjioc_bible_verse_save')) {
        if (!empty($_FILES['bible_csv']['tmp_name']) && is_uploaded_file($_FILES['bible_csv']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['bible_csv']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                echo '<div class="notice notice-error is-dismissible"><p>Please upload a .csv file (in Excel, use File → Save As → CSV).</p></div>';
            } else {
                $verses = [];
                $fh = fopen($_FILES['bible_csv']['tmp_name'], 'r');
                if ($fh) {
                    while (($row = fgetcsv($fh)) !== false) {
                        $ref  = sanitize_text_field($row[0] ?? '');
                        $text = sanitize_text_field($row[1] ?? '');
                        if ($ref !== '' && $text !== '') {
                            $verses[] = ['ref' => $ref, 'text' => $text];
                        }
                    }
                    fclose($fh);
                }
                if ($verses) {
                    update_option('sjioc_bible_verses', wp_json_encode($verses), false);
                    update_option('sjioc_bible_verse_index', 0);
                    echo '<div class="notice notice-success is-dismissible"><p>' . count($verses) . ' verses uploaded. Rotation restarted from the first row.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>No valid rows found. Expected two columns: Reference, Verse Text.</p></div>';
                }
            }
        }
    }

    $verses = json_decode(get_option('sjioc_bible_verses', ''), true) ?: [];
    $index  = (int) get_option('sjioc_bible_verse_index', 0);
    ?>
    <div class="wrap">
        <h1>📖 Weekly Bible Verse</h1>
        <p>Upload a CSV with two columns — <strong>Reference</strong> and <strong>Verse Text</strong> — one row per verse. A new verse is shown on the home page automatically every week, in the order uploaded, looping back to the start once the list is finished.</p>

        <form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:600px;margin:16px 0">
            <?php wp_nonce_field('sjioc_bible_verse_save'); ?>
            <p><input type="file" name="bible_csv" accept=".csv" required></p>
            <p class="description">Uploading a new file replaces the current list and restarts the rotation from the first row.</p>
            <p><button type="submit" name="sjioc_bible_verse_save" value="1" class="button button-primary">Upload &amp; Replace</button></p>
        </form>

        <?php if ($verses): ?>
        <h2>Current List (<?php echo count($verses); ?> verses)</h2>
        <table class="widefat striped" style="max-width:800px">
            <thead><tr><th style="width:40px">#</th><th>Reference</th><th>Text</th></tr></thead>
            <tbody>
            <?php foreach ($verses as $i => $v): ?>
                <tr<?php echo $i === $index ? ' style="background:#fcf3d9"' : ''; ?>>
                    <td><?php echo (int) $i + 1; ?><?php echo $i === $index ? ' ▶' : ''; ?></td>
                    <td><strong><?php echo esc_html($v['ref']); ?></strong></td>
                    <td><?php echo esc_html($v['text']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description">Highlighted row is this week's verse.</p>
        <?php else: ?>
        <p><em>No verses uploaded yet — the home page section stays hidden until a CSV is added.</em></p>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────
   Advance to the next verse — hooked onto the existing weekly
   Celebrations cron so it fires at the same time, with no extra
   scheduled event.
───────────────────────────────────── */
add_action('sjioc_celebrations_cron', function () {
    $verses = json_decode(get_option('sjioc_bible_verses', ''), true) ?: [];
    if (!$verses) return;
    $index = ((int) get_option('sjioc_bible_verse_index', 0) + 1) % count($verses);
    update_option('sjioc_bible_verse_index', $index);
});

function sjioc_get_current_bible_verse() {
    $verses = json_decode(get_option('sjioc_bible_verses', ''), true) ?: [];
    if (!$verses) return null;
    $index = (int) get_option('sjioc_bible_verse_index', 0);
    return $verses[$index] ?? $verses[0];
}
