# SJIOC Widget Bar — Implementation Plan (Final)
**Version:** 2.0  
**Date:** 2026-04-21  
**Decisions locked:** Custom member table · Azure OpenAI · WP Media Library photos · Weekly-window celebrations

---

## Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Database — Custom Members Table](#2-database--custom-members-table)
3. [Widget 1 — Contacts Directory](#3-widget-1--contacts-directory)
4. [Widget 2 — Celebrations (Birthdays & Anniversaries)](#4-widget-2--celebrations)
5. [Widget 3 — AI Chat (Azure OpenAI)](#5-widget-3--ai-chat)
6. [File Change Map](#6-file-change-map)
7. [Deployment Checklist](#7-deployment-checklist)

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│  BROWSER (existing panel UI — zero visual changes)              │
│  footer.php · main.js · style.css                               │
└────────────────────┬────────────────────────────────────────────┘
                     │ HTTP / AJAX (fetch)
┌────────────────────▼────────────────────────────────────────────┐
│  WORDPRESS PHP LAYER                                            │
│  functions.php + inc/members-db.php + inc/chat.php             │
│                                                                 │
│  ┌─────────────────┐  ┌──────────────────┐  ┌───────────────┐  │
│  │ Contacts        │  │ Celebrations     │  │ Chat          │  │
│  │ Transient cache │  │ Transient cache  │  │ AJAX handler  │  │
│  │ 7-day TTL       │  │ Mon–Sun window   │  │ Azure OpenAI  │  │
│  └────────┬────────┘  └────────┬─────────┘  └──────┬────────┘  │
└───────────┼────────────────────┼───────────────────┼───────────┘
            │                    │                   │
┌───────────▼────────────────────▼───────────────────▼───────────┐
│  AZURE MYSQL DATABASE                                           │
│                                                                 │
│  wp_posts (sjioc_contact CPT)   wp_sjioc_members (custom)      │
│  wp_postmeta                    wp_sjioc_events (CPT)          │
│  wp_options (transient store)   wp_options (AI key, KB text)   │
└─────────────────────────────────────────────────────────────────┘
                                            │
                               ┌────────────▼────────────┐
                               │  AZURE OPENAI SERVICE   │
                               │  (same Azure subscription│
                               │   same billing portal)  │
                               └─────────────────────────┘
```

**Caching strategy in one sentence:** The PHP layer caches rendered HTML in WordPress transients. DB is only queried when cache expires or data changes. The browser never talks to the DB.

---

## 2. Database — Custom Members Table

### 2A. Why a custom table (not the CPT)?

The `sjioc_celeb` CPT stores one post per celebration entry — it has no concept of a "person" with both a birthday and a wedding date. A custom table stores a **person** with all their dates, allowing us to derive both birthday and anniversary rows from a single record. It also makes bulk import from Excel much cleaner.

### 2B. Table Schema

**Create this table once** using the code in `inc/members-db.php` (run on theme activation):

```sql
CREATE TABLE IF NOT EXISTS `wp_sjioc_members` (
  `id`               INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name`        VARCHAR(255) NOT NULL,
  `gender`           ENUM('male','female','other') NOT NULL DEFAULT 'other',
  `date_of_birth`    DATE         NULL COMMENT 'YYYY-MM-DD — year is stored but only month+day used for birthday',
  `date_of_wedding`  DATE         NULL COMMENT 'NULL if not married or not applicable',
  `spouse_name`      VARCHAR(255) NULL COMMENT 'Used in anniversary display e.g. "John & Mary Thomas"',
  `phone`            VARCHAR(30)  NULL,
  `email`            VARCHAR(255) NULL,
  `role`             VARCHAR(255) NULL COMMENT 'e.g. Parish Member, Trustee, Sunday School Teacher',
  `photo_url`        VARCHAR(500) NULL COMMENT 'WordPress attachment URL — from Media Library',
  `is_active`        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '0 = soft-deleted / moved away',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2C. PHP Table Creation (runs on theme activation)

In `inc/members-db.php`:

```php
function sjioc_create_members_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'sjioc_members';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name        VARCHAR(255) NOT NULL,
        gender           ENUM('male','female','other') NOT NULL DEFAULT 'other',
        date_of_birth    DATE NULL,
        date_of_wedding  DATE NULL,
        spouse_name      VARCHAR(255) NULL,
        phone            VARCHAR(30)  NULL,
        email            VARCHAR(255) NULL,
        role             VARCHAR(255) NULL,
        photo_url        VARCHAR(500) NULL,
        is_active        TINYINT(1)   NOT NULL DEFAULT 1,
        created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql); // WordPress-safe CREATE TABLE
}
add_action('after_setup_theme', 'sjioc_create_members_table');
```

### 2D. CSV Column Map (for Excel/CSV import)

When exporting from your existing Excel sheet, use these exact column headers:

```
full_name | gender | date_of_birth | date_of_wedding | spouse_name | phone | email | role
```

- `date_of_birth` format: `YYYY-MM-DD` (e.g., `1985-03-15`) — year is stored but only month+day is used for display
- `date_of_wedding` format: `YYYY-MM-DD` — leave blank if not applicable
- `gender`: `male` / `female` / `other`
- `spouse_name`: only needed if `date_of_wedding` is filled

**Example CSV:**
```csv
full_name,gender,date_of_birth,date_of_wedding,spouse_name,phone,email,role
John Thomas,male,1978-06-15,,,,john@email.com,Parish Member
Thomas Philip,male,1970-05-12,1995-05-20,Mary Philip,6105551234,,Trustee
Sosamma George,female,1965-07-07,,,,info@sjioc.org,Sunday School Ministry
```

---

## 3. Widget 1 — Contacts Directory

### 3A. Current Problem
`footer.php` line 64 calls `get_posts('sjioc_contact')` on **every single page load** — no caching. The static fallback uses external `sjioc.org/images/` URLs that are fragile.

### 3B. Solution: Transient Cache

**New function in `functions.php`:**

```php
/* ─────────────────────────────────────
   CONTACTS — Cached HTML Render
───────────────────────────────────── */
function sjioc_get_contacts_html() {
    $cache_key = 'sjioc_contacts_html';
    $cached    = get_transient($cache_key);
    if ($cached !== false) {
        return $cached; // ← serve from cache, zero DB query
    }

    ob_start();
    $contacts = get_posts([
        'post_type'      => 'sjioc_contact',
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true, // performance: skip COUNT(*) query
    ]);

    if ($contacts) {
        foreach ($contacts as $c) {
            $phone = get_post_meta($c->ID, 'contact_phone', true);
            $email = get_post_meta($c->ID, 'contact_email', true);
            $role  = get_post_meta($c->ID, 'contact_role',  true);
            $img   = get_the_post_thumbnail_url($c->ID, 'sjioc-square');
            $init  = strtoupper(substr($c->post_title, 0, 1));
            $name_slug = esc_attr(strtolower($c->post_title . ' ' . $role));
            ?>
            <div class="c-item" data-name="<?php echo $name_slug; ?>">
              <div class="c-avatar">
                <?php if ($img): ?>
                  <img src="<?php echo esc_url($img); ?>"
                       alt="<?php echo esc_attr($c->post_title); ?>"
                       loading="lazy">
                <?php else: echo esc_html($init); endif; ?>
              </div>
              <div class="c-info">
                <h4><?php echo esc_html($c->post_title); ?></h4>
                <p><?php echo esc_html($role); ?></p>
              </div>
              <div class="c-actions">
                <?php if ($phone): ?>
                  <button class="c-btn" title="Call"
                    onclick="window.location='tel:<?php echo esc_attr(preg_replace('/\D/','',$phone)); ?>'">📞</button>
                <?php endif; ?>
                <?php if ($email): ?>
                  <button class="c-btn" title="Email"
                    onclick="window.location='mailto:<?php echo esc_attr($email); ?>'">✉</button>
                <?php endif; ?>
              </div>
            </div>
            <?php
        }
    } else {
        // Static fallback (existing code in footer.php lines 84–107 — move here)
        sjioc_contacts_static_fallback();
    }

    $html = ob_get_clean();
    // Cache for 7 days — busted automatically by save_post hook below
    set_transient($cache_key, $html, 7 * DAY_IN_SECONDS);
    return $html;
}

// Auto-bust when any sjioc_contact post is saved, updated, or deleted
function sjioc_bust_contacts_cache($post_id) {
    if (get_post_type($post_id) === 'sjioc_contact') {
        delete_transient('sjioc_contacts_html');
        delete_transient('sjioc_contacts_count');
    }
}
add_action('save_post',   'sjioc_bust_contacts_cache');
add_action('delete_post', 'sjioc_bust_contacts_cache');

// Real badge count (cached)
function sjioc_contacts_count() {
    $cached = get_transient('sjioc_contacts_count');
    if ($cached !== false) return (int) $cached;
    $count = wp_count_posts('sjioc_contact')->publish ?? 0;
    $count = $count ?: 8; // fallback to 8 if no CPT entries yet
    set_transient('sjioc_contacts_count', $count, 7 * DAY_IN_SECONDS);
    return (int) $count;
}
```

**In `footer.php`**, replace the entire block from line 62 to 108:
```php
<div id="contacts-list">
  <?php echo sjioc_get_contacts_html(); ?>
</div>
```

**Badge in `footer.php`** line 14:
```php
<span class="wbar-badge" id="badge-contacts"><?php echo sjioc_contacts_count(); ?></span>
```

---

### 3C. Contact Photos — Yearly Update Workflow

**How photos are stored:**
Each contact in **WP Admin → Directory** has a "Featured Image." When you set it, WordPress stores the image in the Media Library and generates a 300×300 crop (`sjioc-square`) automatically. Azure CDN caches these images at the edge.

**Yearly photo update process** (e.g., after Annual General Meeting when committee changes):

#### Updating a single contact's photo:
1. Go to **WP Admin → Directory**
2. Find the contact (e.g., "Mr. Tijo Joseph") → click **Edit**
3. In the right sidebar, find **Featured Image** → click **"Set featured image"**
4. Click **"Upload Files"** → choose the new photo from your computer
5. Click **"Set featured image"**
6. Click **Update** (top right)
7. Done — cache auto-busts, new photo appears within seconds on the site

#### Replacing the full committee after AGM (bulk update):
When the entire committee changes annually (e.g., new Trustee, new Secretary):

1. Collect all new photos (WhatsApp/email) into a folder on your computer
2. Resize photos to ≤600×600px before upload (free tool: squoosh.app)
3. **WP Admin → Media → Add New** → upload all photos at once (drag and drop)
4. For each changed contact: **Directory → Edit → Set featured image → select from library → Update**
5. For new contacts: **Directory → Add New** → enter name, role, phone, email → set featured image → Publish
6. For departed members: **Directory → Edit → Change "Published" to "Draft"** (hides them without deletion)

**The transient cache auto-busts every time you click Update** — no manual cache clearing needed.

#### For the members table (Celebrations data):
1. Open your Excel file
2. Update the rows (new members, correct dates, mark departures by deleting rows)
3. Export as CSV
4. **WP Admin → Tools → Import Members** (new admin page we will build)
5. Upload the CSV → click "Preview" → click "Import"
6. Done — the Monday-night cron picks up the change on next run, or you can click "Refresh Now"

---

## 4. Widget 2 — Celebrations

### 4A. How It Works (Final Design)

- Every **Monday at 12:00 AM**, the site queries `wp_sjioc_members`
- It finds everyone whose **birthday** or **wedding anniversary** (month + day only, ignoring year) falls within that **Monday–Sunday window**
- Results are cached for the week
- The panel shows **only that week's celebrations** — nothing more
- Badge count reflects the number of people celebrating that week
- If nobody celebrates that week, the panel shows a friendly "No celebrations this week" message

### 4B. The Monday-Window Query

The key logic — find records where the month/day of birth or wedding falls within the current Mon–Sun:

```php
function sjioc_get_weekly_celebs() {
    $cached = get_transient('sjioc_weekly_celebs');
    if ($cached !== false) return $cached;

    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';

    // Calculate Monday and Sunday of current week
    $dow        = (int) date('N'); // 1=Mon … 7=Sun
    $monday     = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days'));
    $sunday     = date('Y-m-d', strtotime('+' . (7 - $dow) . ' days'));
    $curr_year  = (int) date('Y');

    // Build a DATE using current year + stored month/day, then check if it falls Mon–Sun
    // This correctly handles the birthday/anniversary regardless of birth year
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                id,
                full_name,
                spouse_name,
                date_of_birth,
                date_of_wedding,
                photo_url,
                role,
                'bday' AS cel_type,
                DAY(date_of_birth)   AS cel_day,
                MONTH(date_of_birth) AS cel_month,
                YEAR(date_of_birth)  AS birth_year
             FROM {$table}
             WHERE is_active = 1
               AND date_of_birth IS NOT NULL
               AND DATE(CONCAT(%d, '-', LPAD(MONTH(date_of_birth),2,'0'), '-', LPAD(DAY(date_of_birth),2,'0')))
                   BETWEEN %s AND %s

             UNION ALL

             SELECT
                id,
                CONCAT(full_name, COALESCE(CONCAT(' & ', spouse_name), '')) AS full_name,
                spouse_name,
                date_of_birth,
                date_of_wedding,
                photo_url,
                role,
                'anniv' AS cel_type,
                DAY(date_of_wedding)   AS cel_day,
                MONTH(date_of_wedding) AS cel_month,
                YEAR(date_of_wedding)  AS birth_year
             FROM {$table}
             WHERE is_active = 1
               AND date_of_wedding IS NOT NULL
               AND DATE(CONCAT(%d, '-', LPAD(MONTH(date_of_wedding),2,'0'), '-', LPAD(DAY(date_of_wedding),2,'0')))
                   BETWEEN %s AND %s

             ORDER BY cel_month, cel_day",
            $curr_year, $monday, $sunday,
            $curr_year, $monday, $sunday
        ),
        ARRAY_A
    );

    // Cache until next Monday midnight
    $seconds_until_next_monday = strtotime('next Monday midnight') - time();
    set_transient('sjioc_weekly_celebs', $rows, $seconds_until_next_monday);

    return $rows;
}
```

**Edge case — leap year (Feb 29 birthdays):**
Add this after the query result — if no results and it's a non-leap year, do a second pass with Feb 28. This is a rare case but worth handling gracefully with a code comment.

### 4C. Render the Celebration Panel

Replace the inline CPT query block in `footer.php` (lines 136–189) with:

```php
<?php
$celebs = sjioc_get_weekly_celebs();

// Month names for display
$month_names = [
    1=>'JAN',2=>'FEB',3=>'MAR',4=>'APR',5=>'MAY',6=>'JUN',
    7=>'JUL',8=>'AUG',9=>'SEP',10=>'OCT',11=>'NOV',12=>'DEC'
];

if (empty($celebs)) : ?>
  <div class="cel-empty">
    <span style="font-size:2rem">🕊️</span>
    <p>No birthdays or anniversaries this week.</p>
    <p>Check back next Monday!</p>
  </div>
<?php else :
    $this_week_mon = date('D M j', strtotime('last Monday'));
    $this_week_sun = date('D M j', strtotime('next Sunday'));
    ?>
  <div class="cel-section-head">
    Week of <?php echo esc_html($this_week_mon . ' – ' . $this_week_sun); ?>
  </div>
  <?php foreach ($celebs as $cel) :
    $type = $cel['cel_type'];
    $cls  = ($type === 'anniv') ? 'anniv' : 'bday';
    $icon = ($type === 'anniv') ? '💍' : '🎂';
    $mon_label = $month_names[(int)$cel['cel_month']] ?? '';

    // Calculate age / years married
    $ref_year = (int) date('Y');
    $start_year = (int) $cel['birth_year'];
    $years = ($start_year > 1900) ? ($ref_year - $start_year) : null;
    $years_label = '';
    if ($years && $type === 'bday')   $years_label = 'Turning ' . $years;
    if ($years && $type === 'anniv')  $years_label = $years . ' Years';
  ?>
  <div class="cel-row" data-t="<?php echo esc_attr($cls); ?>">
    <div class="cel-badge <?php echo esc_attr($cls); ?>">
      <span class="cmon"><?php echo esc_html($mon_label); ?></span>
      <span class="cday"><?php echo esc_html($cel['cel_day']); ?></span>
    </div>
    <div class="cel-info">
      <span class="cel-type"><?php echo ($type === 'anniv') ? 'Anniversary ' . $icon : 'Birthday ' . $icon; ?></span>
      <h4><?php echo esc_html($cel['full_name']); ?></h4>
      <?php if ($years_label) : ?><p><?php echo esc_html($years_label); ?></p><?php endif; ?>
    </div>
    <button class="cel-wish"
      onclick="sjiocWishCeleb('<?php echo esc_attr($cel['full_name']); ?>','<?php echo esc_attr($type); ?>')">
      Wish ✉
    </button>
  </div>
  <?php endforeach; endif; ?>
```

**Badge count** (replace hardcoded `3`):
```php
<span class="wbar-badge" id="badge-celeb"><?php
  $celebs_count = get_transient('sjioc_weekly_celebs');
  echo is_array($celebs_count) ? count($celebs_count) : '0';
?></span>
```

### 4D. Monday Midnight Cron Job

```php
/* ─── Register schedule ─── */
function sjioc_add_cron_schedules($schedules) {
    $schedules['sjioc_weekly_monday'] = [
        'interval' => WEEK_IN_SECONDS,
        'display'  => 'Every Monday',
    ];
    return $schedules;
}
add_filter('cron_schedules', 'sjioc_add_cron_schedules');

/* ─── Schedule on theme load (idempotent) ─── */
function sjioc_schedule_weekly_refresh() {
    if (!wp_next_scheduled('sjioc_weekly_celeb_refresh')) {
        $next_monday_midnight = strtotime('next Monday midnight');
        wp_schedule_event($next_monday_midnight, 'sjioc_weekly_monday', 'sjioc_weekly_celeb_refresh');
    }
}
add_action('init', 'sjioc_schedule_weekly_refresh');

/* ─── The job: delete old transient — fresh data loads on first page hit Monday ─── */
add_action('sjioc_weekly_celeb_refresh', function () {
    delete_transient('sjioc_weekly_celebs');
    // Optionally pre-warm the cache immediately (don't wait for page load):
    sjioc_get_weekly_celebs();
});
```

**Azure WebJob for true cron (recommended):**
WP-Cron only fires when someone visits the site. On Azure App Service:

1. **Azure Portal → Your App Service → WebJobs → Add**
2. Name: `sjioc-weekly-refresh`
3. Upload a file `run.sh` containing:
   ```bash
   #!/bin/bash
   curl -s "https://your-site.azurewebsites.net/wp-cron.php?doing_wp_cron" > /dev/null
   ```
4. Type: **Triggered**
5. CRON expression: `0 0 * * 1` (every Monday at 00:00 UTC)
6. Click **OK**

This guarantees exact midnight firing regardless of site traffic.

### 4E. CSV Import Admin Page

Create `inc/admin-import-members.php`:

```php
// Register admin menu under Tools
function sjioc_members_import_menu() {
    add_management_page(
        'Import Parish Members',
        'Import Members',
        'manage_options',     // admins only
        'sjioc-import-members',
        'sjioc_render_import_page'
    );
}
add_action('admin_menu', 'sjioc_members_import_menu');

function sjioc_render_import_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file'])) {
        check_admin_referer('sjioc_import_members');
        $result = sjioc_import_members_csv($_FILES['csv_file']['tmp_name']);
    }
    ?>
    <div class="wrap">
      <h1>Import Parish Members</h1>
      <?php if ($result): ?>
        <div class="notice notice-success"><p>
          Imported/updated <strong><?php echo (int)$result['imported']; ?></strong> members.
          <?php if (!empty($result['errors'])): ?>
            Skipped rows: <?php echo implode(', ', array_map('intval', $result['errors'])); ?>
          <?php endif; ?>
        </p></div>
      <?php endif; ?>
      <p>Upload a CSV with columns: <code>full_name, gender, date_of_birth, date_of_wedding, spouse_name, phone, email, role</code></p>
      <p>Dates must be in <code>YYYY-MM-DD</code> format. Leave date_of_wedding blank if not applicable.</p>
      <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('sjioc_import_members'); ?>
        <input type="file" name="csv_file" accept=".csv" required>
        <p><input type="submit" class="button button-primary" value="Import CSV"></p>
      </form>
    </div>
    <?php
}

function sjioc_import_members_csv($file_path) {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_members';

    $handle   = fopen($file_path, 'r');
    $header   = array_map('trim', fgetcsv($handle)); // read header row
    $imported = 0;
    $errors   = [];
    $row_num  = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        if (count($row) < 1) continue;

        // Map CSV columns to keys
        $data = array_combine($header, array_pad($row, count($header), ''));

        $name = sanitize_text_field(trim($data['full_name'] ?? ''));
        if (empty($name)) { $errors[] = $row_num; continue; }

        $dob     = !empty($data['date_of_birth'])   ? sanitize_text_field($data['date_of_birth'])   : null;
        $wed     = !empty($data['date_of_wedding'])  ? sanitize_text_field($data['date_of_wedding'])  : null;
        $gender  = in_array($data['gender'] ?? '', ['male','female','other']) ? $data['gender'] : 'other';
        $spouse  = sanitize_text_field($data['spouse_name']  ?? '');
        $phone   = sanitize_text_field($data['phone']        ?? '');
        $email   = sanitize_email($data['email']             ?? '');
        $role    = sanitize_text_field($data['role']         ?? '');

        // Validate date formats
        if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) { $errors[] = $row_num; continue; }
        if ($wed && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $wed)) { $errors[] = $row_num; continue; }

        // Check for existing record by name (case-insensitive)
        $existing_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE LOWER(full_name) = LOWER(%s) LIMIT 1", $name)
        );

        $record = [
            'full_name'       => $name,
            'gender'          => $gender,
            'date_of_birth'   => $dob,
            'date_of_wedding' => $wed,
            'spouse_name'     => $spouse ?: null,
            'phone'           => $phone ?: null,
            'email'           => $email ?: null,
            'role'            => $role  ?: null,
            'is_active'       => 1,
        ];
        $format = ['%s','%s','%s','%s','%s','%s','%s','%s','%d'];

        if ($existing_id) {
            $wpdb->update($table, $record, ['id' => $existing_id], $format, ['%d']);
        } else {
            $wpdb->insert($table, $record, $format);
        }
        $imported++;
    }

    fclose($handle);

    // Bust celebrations cache after import
    delete_transient('sjioc_weekly_celebs');

    return ['imported' => $imported, 'errors' => $errors];
}
```

**Load this file from `functions.php`:**
```php
if (is_admin()) {
    require_once SJIOC_DIR . '/inc/admin-import-members.php';
}
```

---

## 5. Widget 3 — AI Chat (Azure OpenAI)

### 5A. Overview

The existing chat panel UI, CSS, animations, and quick-reply buttons are kept exactly as-is. Only `sjiocSendChat()` in `main.js` changes — it now POSTs to a PHP AJAX endpoint instead of doing a local keyword lookup.

The AI receives:
1. A **system prompt** with church identity and behavior rules
2. **Live church data** from the DB (service times, upcoming events, contacts)
3. The **user's message**

It returns a concise answer. The PDF knowledge base can be added later when it is ready.

### 5B. Azure OpenAI Setup

1. **Azure Portal → Create a resource → Azure OpenAI**
2. Region: East US (same as your App Service)
3. After creation: **Azure OpenAI Studio → Deployments → Create**
   - Model: `gpt-4o-mini` (cheapest, fast, sufficient for FAQ chat)
   - Deployment name: `sjioc-chat` (you will reference this in code)
4. Note down:
   - **Endpoint:** `https://your-resource.openai.azure.com/`
   - **API Key:** from Keys and Endpoints blade
   - **Deployment name:** `sjioc-chat`
   - **API Version:** `2024-02-01` (current stable)

**Cost estimate:** gpt-4o-mini at ~$0.15/1M tokens. A typical parish chat message is ~200 tokens total. At 100 messages/day that is ~$0.003/day — effectively free at parish scale.

### 5C. Store Credentials (Never in Theme Files)

In `wp-config.php` (above `/* That's all, stop editing! */`):
```php
define('SJIOC_AOAI_ENDPOINT',    'https://your-resource.openai.azure.com/openai/deployments/sjioc-chat/chat/completions?api-version=2024-02-01');
define('SJIOC_AOAI_KEY',         'your-azure-openai-api-key-here');
```

These are read by PHP — the browser never sees them.

### 5D. Live Context Builder

```php
function sjioc_build_chat_context() {
    $ctx = [];

    // Church basics (from Customizer)
    $ctx[] = '=== CHURCH INFORMATION ===';
    $ctx[] = 'Name: ' . sjioc_name();
    $ctx[] = 'Address: ' . sjioc_address();
    $ctx[] = 'Phone: ' . sjioc_phone();
    $ctx[] = 'Email: ' . sjioc_email();
    $ctx[] = 'Sunday Holy Qurbana: ' . sjioc_qurbana();
    $ctx[] = 'Sunday School: ' . sjioc_school();
    $ctx[] = 'Saturday Office Hours: ' . sjioc_get('sjioc_saturday', '5:00 PM – 7:30 PM');
    $ctx[] = 'Facebook: ' . sjioc_fb();
    $ctx[] = 'YouTube: ' . sjioc_yt();
    $ctx[] = 'Maps: ' . sjioc_maps();

    // Upcoming events from DB (next 8)
    $events = get_posts([
        'post_type'      => 'sjioc_event',
        'posts_per_page' => 8,
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'event_date',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
        ]],
        'no_found_rows'  => true,
    ]);

    if ($events) {
        $ctx[] = '';
        $ctx[] = '=== UPCOMING EVENTS ===';
        foreach ($events as $e) {
            $date     = get_post_meta($e->ID, 'event_date',     true);
            $time     = get_post_meta($e->ID, 'event_time',     true);
            $location = get_post_meta($e->ID, 'event_location', true);
            $ctx[] = '- ' . $e->post_title
                   . ' | Date: ' . $date
                   . ($time     ? ' at ' . $time     : '')
                   . ($location ? ' | ' . $location  : '');
        }
    }

    // Key contacts from CPT
    $contacts = get_posts([
        'post_type'      => 'sjioc_contact',
        'posts_per_page' => 10,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);

    if ($contacts) {
        $ctx[] = '';
        $ctx[] = '=== KEY CONTACTS ===';
        foreach ($contacts as $c) {
            $phone = get_post_meta($c->ID, 'contact_phone', true);
            $role  = get_post_meta($c->ID, 'contact_role',  true);
            $ctx[] = '- ' . $c->post_title . ' (' . $role . ')'
                   . ($phone ? ' — ' . $phone : '');
        }
    }

    return implode("\n", $ctx);
}
```

### 5E. AJAX Handler (in `functions.php`)

```php
/* ─────────────────────────────────────
   CHAT — Azure OpenAI AJAX Handler
───────────────────────────────────── */
function sjioc_chat_query() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    $user_msg = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (empty($user_msg) || strlen($user_msg) > 1000) {
        wp_send_json_error(['reply' => 'Invalid message.']);
    }

    // Rate limit: 20 chat messages per IP per hour
    $rate_key = 'sjioc_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $count    = (int) get_transient($rate_key);
    if ($count >= 20) {
        wp_send_json_error([
            'reply' => 'Too many messages this hour. Please call us at ' . sjioc_phone() . '.',
        ]);
    }
    set_transient($rate_key, $count + 1, HOUR_IN_SECONDS);

    // Build system prompt
    $live_context  = sjioc_build_chat_context();
    $system_prompt = "You are the friendly parish assistant for St. John's Indian Orthodox Church of Delaware Valley (SJIOC). You answer warmly, concisely, and in the spirit of the Malankara Orthodox faith. Keep answers under 120 words. Only answer questions about the church and parish life. For anything outside that scope, kindly redirect to calling " . sjioc_phone() . " or emailing " . sjioc_email() . ". Do not make up events, dates, or contact information — use only what is in the context below.\n\n" . $live_context;

    // Call Azure OpenAI
    $endpoint = defined('SJIOC_AOAI_ENDPOINT') ? SJIOC_AOAI_ENDPOINT : '';
    $api_key  = defined('SJIOC_AOAI_KEY')      ? SJIOC_AOAI_KEY      : '';

    if (empty($endpoint) || empty($api_key)) {
        // Graceful fallback — treat like old keyword system
        wp_send_json_success(['reply' => 'Thank you! Please call us at ' . sjioc_phone() . ' or email ' . sjioc_email() . '. Glory to God! 🙏']);
    }

    $payload = wp_json_encode([
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $user_msg],
        ],
        'max_tokens'  => 200,
        'temperature' => 0.4,
    ]);

    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
            'api-key'      => $api_key,
        ],
        'body'    => $payload,
        'timeout' => 25,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['reply' => 'I\'m having trouble connecting right now. Please call us at ' . sjioc_phone() . '.']);
    }

    $body  = json_decode(wp_remote_retrieve_body($response), true);
    $reply = $body['choices'][0]['message']['content'] ?? null;

    if (empty($reply)) {
        wp_send_json_error(['reply' => 'No response from AI. Please call us at ' . sjioc_phone() . '.']);
    }

    wp_send_json_success(['reply' => wp_kses_post(trim($reply))]);
}
add_action('wp_ajax_sjioc_chat',        'sjioc_chat_query');
add_action('wp_ajax_nopriv_sjioc_chat', 'sjioc_chat_query');
```

### 5F. PDF Knowledge Base (When Ready — Future Step)

When the church PDF is ready:

1. Convert to text: open PDF → select all → copy → paste into a `.txt` file, OR use a free converter at smallpdf.com
2. Upload to: `wp-content/uploads/sjioc-knowledge.txt` (not inside the theme folder)
3. Add one-time setup step in the admin import page:
   ```php
   // Load PDF text into wp_options (autoload=false: only read when needed)
   $kb = file_get_contents(WP_CONTENT_DIR . '/uploads/sjioc-knowledge.txt');
   update_option('sjioc_knowledge_base', $kb, false);
   ```
4. In `sjioc_build_chat_context()`, append:
   ```php
   $kb = get_option('sjioc_knowledge_base', '');
   if ($kb) {
       $ctx[] = '';
       $ctx[] = '=== CHURCH HANDBOOK ===';
       $ctx[] = mb_substr($kb, 0, 6000); // cap at 6000 chars to stay within token limit
   }
   ```
   That is all — no other code changes.

### 5G. `main.js` — Replace `sjiocSendChat()`

Replace the entire `sjiocSendChat` function (lines 237–265) with:

```javascript
window.sjiocSendChat = function () {
    var input = document.getElementById('chatInput');
    if (!input) return;
    var text = input.value.trim();
    if (!text) return;

    appendMsg(escHtml(text), 'usr');
    input.value = '';
    input.disabled = true;

    var msgs = document.getElementById('chatMessages');
    var ty = document.createElement('div');
    ty.className = 'typing-ind'; ty.id = 'typingInd';
    ty.innerHTML = '<div class="tdot"></div><div class="tdot"></div><div class="tdot"></div>';
    msgs.appendChild(ty);
    msgs.scrollTop = msgs.scrollHeight;

    var data = new FormData();
    data.append('action',  'sjioc_chat');
    data.append('nonce',   (window.sjioData || {}).nonce || '');
    data.append('message', text);

    fetch((window.sjioData || {}).ajaxUrl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        var ind = document.getElementById('typingInd');
        if (ind && ind.parentNode) ind.parentNode.removeChild(ind);
        var reply = res.success && res.data && res.data.reply
            ? res.data.reply
            : 'Sorry, I could not process that. Please call us at (610) 822-0033.';
        appendMsg(reply, 'bot'); // reply may contain HTML links — appendMsg uses innerHTML
    })
    .catch(function () {
        var ind = document.getElementById('typingInd');
        if (ind && ind.parentNode) ind.parentNode.removeChild(ind);
        appendMsg('Network error. Please call (610) 822-0033.', 'bot');
    })
    .finally(function () {
        input.disabled = false;
        input.focus();
    });
};
```

The `sjiocQuickSend()`, `appendMsg()`, `sjiocWishCeleb()`, typing animation, and all CSS remain **completely unchanged**.

---

## 6. File Change Map

| File | Action | What Changes |
|------|--------|-------------|
| `functions.php` | Edit | Add: contacts cache, celeb cache, weekly query, contacts count, WP-Cron schedule, chat AJAX handler, context builder, bust hooks |
| `footer.php` | Edit | Replace inline `get_posts()` blocks → `echo sjioc_get_contacts_html()` and celebration render; fix badge counts |
| `assets/js/main.js` | Edit | Replace `sjiocSendChat()` with AJAX version (25 lines) |
| `inc/members-db.php` | Create | `wp_sjioc_members` table creation + `sjioc_create_members_table()` |
| `inc/admin-import-members.php` | Create | WP Admin import page + CSV parser |
| `wp-config.php` | Edit | Add `SJIOC_AOAI_ENDPOINT` and `SJIOC_AOAI_KEY` constants |
| `wp-config.php` | Edit | Add `WP_CRON_LOCK_TIMEOUT` (optional, improves cron reliability) |

**Nothing changes in:** `style.css`, `header.php`, `front-page.php`, `page-*.php`, `index.php`, `page.php`

---

## 7. Deployment Checklist

### Before coding
- [ ] Provision Azure OpenAI resource in same region as App Service (East US)
- [ ] Create deployment `sjioc-chat` using model `gpt-4o-mini`
- [ ] Note endpoint URL + API key
- [ ] Export the Excel member list as UTF-8 CSV with the exact column headers listed in §2D

### Code deployment
- [ ] Add Azure OpenAI constants to `wp-config.php`
- [ ] Deploy updated theme files via SFTP or Azure Deployment Center
- [ ] Theme activation auto-creates `wp_sjioc_members` table
- [ ] **WP Admin → Tools → Import Members** → upload CSV → verify row count
- [ ] **WP Admin → Directory** → verify contacts exist → add/upload photos
- [ ] **WP Admin → Events → Add New** → add at least 2–3 upcoming events (chat will reference them)

### Verification
- [ ] Open the Celebrations widget — confirm this week's birthdays/anniversaries appear
- [ ] Click a "Wish" button — confirm chat panel opens with the wish pre-filled
- [ ] Type "when is Qurbana?" in chat — confirm AI replies with `8:30 AM`
- [ ] Type "what events are coming up?" — confirm AI lists actual events from DB
- [ ] Edit a contact in WP Admin, click Update — confirm contact panel refreshes on next load
- [ ] Check Azure Monitor for OpenAI usage after a few test messages

### Azure WebJob (Monday cron)
- [ ] Create WebJob `sjioc-weekly-refresh` with CRON `0 0 * * 1`
- [ ] Test manually by triggering the job once and checking that the transient is cleared

---

*Plan finalized: 2026-04-21 · SJIOC Delaware Valley WordPress Theme v2.0*