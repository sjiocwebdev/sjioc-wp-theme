# WordPress Page Setup — SJIOC Theme

## 1. Permalink Settings

1. Go to **wp-admin → Settings → Permalinks**
2. Select **Post name** (`/%postname%/`)
3. Click **Save Changes**

---

## 2. Reading Settings (Homepage)

1. Go to **wp-admin → Settings → Reading**
2. Set **"Your homepage displays"** to **A static page**
3. Create a blank page titled **Home** first (see step 3), then return here and assign it
4. Leave **Posts page** blank (no blog)
5. Click **Save Changes**

---

## 3. Create Pages

Go to **wp-admin → Pages → Add New** for each page below.

| Title | Slug | Template |
|---|---|---|
| Home | `home` | Home Page |
| About Us | `about-us` | About Us Page |
| Worship & Services | `worship-services` | Worship & Services Page |
| Ministries | `ministries` | Ministries Page |
| Events | `events` | Events Page |
| Photos | `photos` | Photos Page |
| Contact Us | `contact-us` | Contact Us Page |

For each page:
1. Enter the **Title** from the table
2. Check the **Permalink** field below the title — edit it to match the **Slug** exactly
3. In the right sidebar under **Page Attributes → Template**, select the matching template
4. Click **Publish**

---

## 4. Verify Slugs

After publishing each page, click **View Page** and confirm the URL matches:

```
https://your-site.com/about-us/
https://your-site.com/worship-services/
https://your-site.com/ministries/
https://your-site.com/events/
https://your-site.com/photos/
https://your-site.com/contact-us/
```

If a URL returns 404, go back to **Settings → Permalinks** and click **Save Changes** again to flush rewrite rules.

> **Note:** The Events page uses a custom page template (`page-events.php`) that runs its own queries. The `sjioc_event` CPT archive is intentionally disabled — `/events/` must always be served by the WordPress page, not the CPT archive.

---

## 5. Primary Navigation Menu

1. Go to **wp-admin → Appearance → Menus**
2. Click **Create a new menu** — name it `Primary`
3. Add all 7 pages in order
4. Under **Menu Settings**, check **Primary Navigation**
5. Click **Save Menu**

---

## 6. Members Database Table

The member management system (admin roster, Enable/Disable, Import, Celebrations cron) requires a custom table.

### 6a. Create the table

Run the following SQL via **phpMyAdmin** or **MySQL Workbench** on your WordPress database:

```sql
CREATE TABLE IF NOT EXISTS wp_sjioc_members (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cardex_no     VARCHAR(20)  NOT NULL,
  member_seq    TINYINT      NOT NULL DEFAULT 1,
  first_name    VARCHAR(80)  NOT NULL,
  middle_name   VARCHAR(80)  NOT NULL DEFAULT '',
  last_name     VARCHAR(80)  NOT NULL DEFAULT '',
  gender        CHAR(1)      NOT NULL DEFAULT 'M',
  date_of_birth DATE         NULL,
  marital_status CHAR(1)     NOT NULL DEFAULT 'S',
  wedding_date  DATE         NULL,
  phone_number  VARCHAR(30)  NOT NULL DEFAULT '',
  email         VARCHAR(120) NOT NULL DEFAULT '',
  address       VARCHAR(200) NOT NULL DEFAULT '',
  city          VARCHAR(80)  NOT NULL DEFAULT '',
  state         CHAR(2)      NOT NULL DEFAULT '',
  zip_code      VARCHAR(10)  NOT NULL DEFAULT '',
  country       VARCHAR(60)  NOT NULL DEFAULT 'USA',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cardex_seq (cardex_no, member_seq),
  KEY idx_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> On Azure the table prefix may differ. Replace `wp_` with your actual prefix (check `wp-config.php` for `$table_prefix`).

### 6b. Import member data

1. Prepare your member roster as a `.csv` or `.xlsx` file with these headers:
   `Cardex No, Member Seq, First Name, Middle Name, Last Name, Gender, Date of Birth, Marital Status, Wedding Date, Phone Number, Email Address, Address, City, State, Zip Code, Country`
2. Go to **wp-admin → SJIOC → Import Members**
3. Upload the file → choose **Update existing** or **Skip duplicates** → click **Upload & Import**

---

## 7. Events Setup

### 7a. Add events manually

Go to **wp-admin → SJIOC → Events** to view the monthly calendar grid. Click **+** on any day to create an event with the date pre-filled.

### 7b. Import events from spreadsheet

1. Prepare your events file (`.csv` or `.xlsx`) with headers:
   `Title, Date, Time, End Time, All Day, Location, Category, Description`
   - **Date format:** DD/MM/YYYY or YYYY-MM-DD
   - **Category:** worship · fellowship · education · outreach · special
2. Go to **wp-admin → SJIOC → Import Events**
3. Upload the file → click **Upload & Import**

The public Events page caps display at **12 upcoming** and **8 past** events. A "Show all" button appears when there are more.

---

## 8. Celebrations Cron (Birthdays & Anniversaries)

The footer panel shows this week's birthdays and wedding anniversaries, refreshed every **Monday at 12:01 AM**.

### 8a. Trigger the first run manually

After the members table is populated, force an immediate cache build:

1. Go to **wp-admin → SJIOC → Celebrations**
2. Click **Run Now** — this populates the cache so the footer panel isn't blank on first load

### 8b. Verify the cron schedule

The `sjioc_weekly_cron` event should appear in the WP Cron list. If you have the **WP Crontrol** plugin installed, you can confirm it's scheduled for next Monday at 12:01 AM.

---

## 9. AI Chat Setup (Azure OpenAI)

### 9a. Create Azure OpenAI Resource

1. Go to **portal.azure.com** and sign in
2. Click **Create a resource** → search **"Azure OpenAI"** → click **Create**
3. Fill in:
   - **Subscription:** your active subscription
   - **Resource group:** create new or use existing (e.g. `sjioc-rg`)
   - **Region:** `East US` (best GPT-4o availability)
   - **Name:** e.g. `sjioc-openai`
   - **Pricing tier:** `Standard S0`
4. Click **Review + Create** → **Create**
5. Once deployed, click **Go to resource**
6. In the left sidebar click **Keys and Endpoint** — copy:
   - **KEY 1** (your API key)
   - **Endpoint** (e.g. `https://sjioc-openai.openai.azure.com/`)

### 9b. Deploy a GPT-4o Model

1. In your Azure OpenAI resource, click **Go to Azure OpenAI Studio**
2. Click **Deployments** → **Deploy model** → **Deploy base model**
3. Select **gpt-4o** → click **Confirm**
4. Set **Deployment name** to `gpt-4o` → click **Deploy**
5. Wait ~1 minute for deployment to complete

### 9c. Add Credentials to wp-config.php

Add these three lines **before** `/* That's all, stop editing! */`:

```php
define( 'SJIOC_AZURE_OAI_ENDPOINT', 'https://sjioc-openai.openai.azure.com/' );
define( 'SJIOC_AZURE_OAI_KEY',      'paste-key-1-here' );
define( 'SJIOC_AZURE_OAI_DEPLOY',   'gpt-4o' );
```

### 9d. Add Church Knowledge Base

1. Open your church PDF → select all text → copy
2. Go to **wp-admin → SJIOC → Chat Settings**
3. Paste the text into the textarea → click **Save Knowledge Base**

---

## 10. Deploying Theme to Azure (Production Checklist)

> The theme code travels with the zip — database content and credentials do not.

### 10a. wp-config.php path on Azure

| Environment | Path |
|---|---|
| Local (Docker) | `/var/www/html/wp-config.php` |
| Azure App Service | `/home/site/wwwroot/wp-config.php` |

Edit via **Azure Portal → App Service → SSH** or Kudu console:
`https://<your-app>.scm.azurewebsites.net`

### 10b. Add credentials to Azure wp-config.php

```php
define( 'SJIOC_AZURE_OAI_ENDPOINT', 'https://sjioc-openai.openai.azure.com/' );
define( 'SJIOC_AZURE_OAI_KEY',      'paste-key-1-here' );
define( 'SJIOC_AZURE_OAI_DEPLOY',   'gpt-4o' );

define( 'SJIOC_SMTP_HOST', 'smtp.office365.com' );
define( 'SJIOC_SMTP_USER', 'info@sjioc.org' );
define( 'SJIOC_SMTP_PASS', 'your-password-or-app-password' );
define( 'SJIOC_SMTP_PORT', 587 );

define( 'SJIOC_AZURE_TENANT_ID',     '20d9fcc6-7f55-4bdc-9c36-444d81b0e453' );
define( 'SJIOC_AZURE_CLIENT_ID',     'ba67b761-31d5-4aae-bee4-22b28dad3d5e' );
define( 'SJIOC_AZURE_CLIENT_SECRET', 'your-client-secret-value' );
define( 'SJIOC_SHAREPOINT_SITE_ID',  'ad254eaf-69cc-45b9-a429-7312749b8f00' );
define( 'SJIOC_ONEDRIVE_DRIVE_ID',   'b!r04lrcxpuUWkKXMSdJuPAGCmKZ3hjtdDikK5Tq5W4vTW6Wlu2T83T5y3ZjNsmNXB' );
define( 'SJIOC_ONEDRIVE_FOLDER_ID',  '01EO7JTQ2D6I7OJGDJKNHYXQCTOQ4ZTHES' );
```

### 10c. Post-upload checklist

- [ ] Theme zip uploaded & activated
- [ ] **Settings → Permalinks → Save Changes** (flush rewrite rules)
- [ ] All 7 pages created with correct slugs and templates (Section 3)
- [ ] Primary nav menu assigned (Section 5)
- [ ] `wp_sjioc_members` table created on Azure DB (Section 6a)
- [ ] Member data imported via SJIOC → Import Members (Section 6b)
- [ ] Events imported via SJIOC → Import Events (Section 7b)
- [ ] Celebrations cache triggered via SJIOC → Celebrations → Run Now (Section 8a)
- [ ] Azure OpenAI credentials added to `wp-config.php` (Section 9c)
- [ ] Knowledge base text saved via SJIOC → Chat Settings (Section 9d)
- [ ] SMTP credentials added to `wp-config.php` (Section 10b)
- [ ] Contact form tested — email arrives in correct inbox (Section 11d)
- [ ] OneDrive constants added to `wp-config.php` (Section 12d)
- [ ] `wp_sjioc_photos` table created — re-activate theme or trigger via WP-CLI (Section 12f)
- [ ] First OneDrive sync run via SJIOC → Photos → Sync Now (Section 12f)

---

## 11. Email / Contact Form — Microsoft 365 (Outlook) SMTP

> The contact form routes emails based on the subject selected:
> - **Contact the Vicar** → Vicar's email
> - **Contact the Trustee** → Trustee's email
> - **Contact the Secretary** → Secretary's email
> - **Everything else** → General church email
>
> Each address is configured in **wp-admin → Appearance → Customize → Church Information**.

### 11a. Set routing emails in Customizer

1. Go to **wp-admin → Appearance → Customize → Church Information**
2. Fill in: Vicar Email, Trustee Email, Secretary Email
3. Click **Publish**

### 11b. Enable SMTP AUTH on the sending mailbox (Microsoft 365 Admin)

1. Go to **admin.microsoft.com → Users → Active Users**
2. Click the church sending account (e.g. `info@sjioc.org`)
3. Click **Mail tab → Manage email apps**
4. Tick **Authenticated SMTP** ✓ → Save

> **If MFA is enabled** — use an App Password:
> 1. Go to **myaccount.microsoft.com → Security → Advanced security options → App passwords**
> 2. Create one labelled `WordPress SMTP` → copy the 16-character password
> 3. Use that as `SJIOC_SMTP_PASS`
>
> **Recommended alternative — Shared mailbox (no MFA, no licence cost):**
> 1. **admin.microsoft.com → Teams & groups → Shared mailboxes → Add**
> 2. Name: `WordPress Notifications`, Email: `noreply@sjioc.org`
> 3. Enable **Authenticated SMTP** on the account → set a password → use in `SJIOC_SMTP_PASS`

### 11c. Add SMTP credentials to wp-config.php

```php
define( 'SJIOC_SMTP_HOST', 'smtp.office365.com' );
define( 'SJIOC_SMTP_USER', 'info@sjioc.org' );
define( 'SJIOC_SMTP_PASS', 'your-password-or-app-password' );
define( 'SJIOC_SMTP_PORT', 587 );
```

### 11d. Test the contact form

1. Visit `https://your-site.com/contact-us/`
2. Fill in the form → select **Contact the Vicar** → Submit
3. Check the Vicar's inbox — confirm the email arrived

---

## 12. OneDrive Photo Sync

Photos are served directly from OneDrive via pre-authenticated download URLs stored in the database. No image files are copied to WordPress — only metadata and temporary URLs.

### 12a. Create an Azure AD App Registration

1. Go to **portal.azure.com → Azure Active Directory → App registrations → New registration**
2. Name: `SJIOC WordPress` → Supported account types: **Single tenant** → Click **Register**
3. Copy the **Application (client) ID** — this is `SJIOC_OD_CLIENT_ID`
4. Copy the **Directory (tenant) ID** — this is `SJIOC_OD_TENANT_ID`
5. Go to **Certificates & secrets → New client secret** → Description: `WP Sync` → Expires: 24 months → Add
6. Copy the **Value** immediately (shown only once) — this is `SJIOC_OD_CLIENT_SECRET`

### 12b. Grant API permission

1. In the app registration, click **API permissions → Add a permission → Microsoft Graph → Application permissions**
2. Search for `Files.Read.All` → tick it → click **Add permissions**
3. Click **Grant admin consent for [your org]** → confirm

### 12c. Find your Drive ID and Folder ID

Use **[Graph Explorer](https://developer.microsoft.com/en-us/graph/graph-explorer)** (sign in with your Microsoft 365 account):

**Get your Drive ID:**
```
GET https://graph.microsoft.com/v1.0/me/drive
```
Copy the `id` field from the response — this is `SJIOC_OD_DRIVE_ID`.

> For a SharePoint document library drive, use:
> `GET https://graph.microsoft.com/v1.0/sites/{site-id}/drives`
> and pick the drive whose `name` matches your library.

**Get your Folder ID:**
```
GET https://graph.microsoft.com/v1.0/me/drive/root:/SJIOC Photos
```
Copy the `id` field from the response — this is `SJIOC_OD_FOLDER_ID`.

> Adjust `SJIOC Photos` to match the exact folder name in your OneDrive.

### 12d. Add constants to wp-config.php

Add these six lines **before** `/* That's all, stop editing! */`:

```php
define( 'SJIOC_AZURE_TENANT_ID',     '20d9fcc6-7f55-4bdc-9c36-444d81b0e453' );
define( 'SJIOC_AZURE_CLIENT_ID',     'ba67b761-31d5-4aae-bee4-22b28dad3d5e' );
define( 'SJIOC_AZURE_CLIENT_SECRET', 'your-client-secret-value' );  // from Azure Portal — Value column, not ID
define( 'SJIOC_SHAREPOINT_SITE_ID',  'ad254eaf-69cc-45b9-a429-7312749b8f00' );
define( 'SJIOC_ONEDRIVE_DRIVE_ID',   'b!r04lrcxpuUWkKXMSdJuPAGCmKZ3hjtdDikK5Tq5W4vTW6Wlu2T83T5y3ZjNsmNXB' );
define( 'SJIOC_ONEDRIVE_FOLDER_ID',  '01EO7JTQ2D6I7OJGDJKNHYXQCTOQ4ZTHES' );
```

> ⚠️ **Client secret:** Azure Portal → App registrations → app → Certificates & secrets → copy the **Value** column (long random string ~40 chars), not the **ID** column (UUID). The value is only shown once at creation time.

### 12e. Set up the OneDrive folder structure

Create this layout inside the folder that `SJIOC_OD_FOLDER_ID` points to:

```
SJIOC Photos/
├── Worship/
│   └── Holy Qurbana 2025/
├── Events/
│   └── Parish Picnic 2025/
├── Ministries/
│   └── Sunday School/
└── Community/
    └── Fellowship 2025/
```

- **Top-level folders** map to gallery filter categories (worship · events · ministries · community)
- **Sub-folders** become the album name shown in the gallery overlay
- Supported image formats: `.jpg` `.jpeg` `.png` `.webp`

### 12f. Run the first sync

1. Re-activate the theme (Appearance → Themes → Activate) so WordPress creates the `wp_sjioc_photos` table and schedules the weekly cron
2. Go to **wp-admin → SJIOC → Photos**
3. Confirm the credentials notice shows ✅
4. Click **Sync Now from OneDrive** — the first run does a full enumeration and may take 30–60 seconds depending on photo count
5. Visit `https://your-site.com/photos/` — gallery should show your OneDrive photos

### 12g. Automatic sync schedule

The sync runs automatically every **Sunday at 12:01 AM** (site timezone). Each run:
1. Refreshes all stored download URLs (they expire in ~1 hour — the weekly cron renews them all)
2. Fetches only new/changed/deleted items via delta query — no full re-scan after the first run

To check the schedule: install **WP Crontrol** and confirm `sjioc_od_sync_cron` is listed.
