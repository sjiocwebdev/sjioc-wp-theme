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
| Hall Rental | `hall-rental` | Hall Rental Page |

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
https://your-site.com/hall-rental/
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
- [ ] Server cron configured (Option A, B, or C in Section 13) — keeps photos alive & celebrations fresh
- [ ] `DISABLE_WP_CRON` added to `wp-config.php` after server cron is confirmed (Section 13)
- [ ] Hall Rental page created (`hall-rental`, template "Hall Rental Page") (Section 14)
- [ ] Hall Rental Customizer settings filled in (hall name, capacity, SP link, OD folder ID) (Section 14b)
- [ ] Vicar, Trustee, Secretary email addresses set in Customizer — required for rental notifications (Section 14c)
- [ ] reCAPTCHA v3 keys added in Customizer — recommended for bot protection (Section 15)

---

## 11. Email / Contact Form — Microsoft 365 (Outlook) SMTP

> **Admin UI available:** SMTP settings can be configured directly from **wp-admin → SJIOC → Email (SMTP)** without touching `wp-config.php`. Constants in `wp-config.php` always take priority (shown with a green "locked" badge). Use the built-in **Send Test Email** button on that page to verify the configuration is working.

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

---

## 13. Server Cron — Keep Photos & Celebrations Fresh (Production Required)

> **Why this matters:** WordPress WP-Cron only fires when someone visits the site. A church website can go hours or days without traffic. Without a real server cron:
> - OneDrive photo URLs expire after ~1 hour → photos disappear
> - The Celebrations panel (birthdays & anniversaries) stops updating weekly
>
> A server cron pings `wp-cron.php` every 15 minutes regardless of traffic, keeping everything alive. **This is a one-time setup done at deployment — not needed locally.**

---

### Option A — Azure App Service WebJob (Recommended for Azure hosting)

WebJobs are built into Azure App Service at no extra cost.

**Step 1 — Create the script**

Create a file called `run.sh` with this content:

```bash
#!/bin/bash
curl -s "https://your-site.azurewebsites.net/wp-cron.php?doing_wp_cron" > /dev/null
```

Replace `your-site.azurewebsites.net` with your actual Azure App Service URL.

**Step 2 — Create a settings file**

Create a file called `settings.job` with this content:

```json
{ "schedule": "0 */15 * * * *" }
```

This runs every 15 minutes (CRON expression: every 15 min, every hour, every day).

**Step 3 — Upload via Kudu**

1. Open Kudu: `https://<your-app>.scm.azurewebsites.net`
2. Go to **Debug console → CMD**
3. Navigate to `site/wwwroot/`
4. Create the folder path: `App_Data/jobs/triggered/wp-cron/`
5. Upload both `run.sh` and `settings.job` into that folder
6. In the Azure Portal go to your App Service → **WebJobs** → confirm `wp-cron` appears with schedule

---

### Option B — External Cron Service (Easiest, works with any hosting)

Use a free external service — no server access needed.

1. Go to **[cron-job.org](https://cron-job.org)** and create a free account
2. Click **Create cronjob**
3. Fill in:
   - **URL:** `https://your-site.com/wp-cron.php?doing_wp_cron`
   - **Execution schedule:** Every 15 minutes
   - **Request method:** GET
4. Click **Create** — done

> cron-job.org is free for up to 5 cron jobs and unlimited executions. It simply makes an HTTP GET request to your URL on schedule — no server access required.

---

### Option C — cPanel Hosting (Traditional shared hosting)

If deployed on cPanel-based hosting (Bluehost, SiteGround, etc.):

1. Log in to **cPanel → Cron Jobs**
2. Set the schedule to **Every 15 Minutes** (use the dropdown)
3. Enter this command:
   ```
   wget -q -O - "https://your-site.com/wp-cron.php?doing_wp_cron" > /dev/null 2>&1
   ```
4. Click **Add New Cron Job**

---

### Disable WP-Cron's built-in trigger (Optional but recommended)

Once a real server cron is in place, disable WordPress's built-in visitor-triggered cron to avoid redundant execution on every page load. Add this line to `wp-config.php` **before** `/* That's all, stop editing! */`:

```php
define( 'DISABLE_WP_CRON', true );
```

> Do **not** add this line until the server cron is confirmed working. Without either WP-Cron or a server cron, scheduled events will never run.

---

### What the cron keeps alive

| Cron Event | Schedule | What it does |
|---|---|---|
| `sjioc_od_refresh_cron` | Hourly | Refreshes expiring OneDrive photo URLs (they last ~1 hr) |
| `sjioc_od_sync_cron` | Weekly (Sun 12:01 AM) | Pulls new/changed/deleted photos from OneDrive |
| `sjioc_celebrations_cron` | Weekly (Mon 12:01 AM) | Rebuilds this week's birthdays & anniversaries cache |

### Verify it's working

After setting up, wait 15–20 minutes then check:

1. **WP Admin → SJIOC → Photos** — "Last sync" timestamp should be recent
2. **WP Admin → SJIOC → Celebrations** — cache "Generated at" should be recent
3. Install **WP Crontrol** plugin → all three events should show next scheduled times

---

## 14. Hall Rental Page (SJIOC MBM Hall)

A multi-step rental request form that stores submissions in the database, sends email notifications to staff, and uploads an HTML summary to a SharePoint folder.

### 14a. Create the page

1. Go to **wp-admin → Pages → Add New**
2. Title: `Hall Rental`, Slug: `hall-rental`, Template: **Hall Rental Page**
3. Publish

> **Navigation:** The Hall Rental link appears automatically under "Contact" in the nav — it is rendered by the PHP fallback function (`sjioc_primary_nav_fallback`) and does **not** use Appearance → Menus.

### 14b. Configure Customizer settings

Go to **wp-admin → Appearance → Customize → Hall Rental Settings**:

| Setting | Description |
|---|---|
| Hall Name | Displayed on the page (e.g. `SJIOC MBM Hall`) |
| Hall Capacity | Max guests (e.g. `200`) — shown in the form and T&C |
| SharePoint Rentals Folder URL | Full URL to the SharePoint folder — linked in staff notification emails |
| OneDrive Rentals Folder ID | Item ID of the folder where HTML summaries are uploaded (see below) |

**To find the OneDrive Rentals Folder ID** — use Graph Explorer:
```
GET https://graph.microsoft.com/v1.0/drives/{SJIOC_ONEDRIVE_DRIVE_ID}/root/children
```
Find the `SJIOC Hall Rentals` folder in the response and copy its `id` field.

> The Azure AD app registration requires `Files.ReadWrite.All` permission (not just `Files.Read.All`) to upload summary files. Grant this in **portal.azure.com → App registrations → API permissions → Add → Microsoft Graph → Application → Files.ReadWrite.All → Grant admin consent**.
>
> After granting the permission, go to **wp-admin → SJIOC → Hall Rentals** and click **Clear OneDrive Token Cache** to force a fresh token.

### 14c. Email notifications

On each new rental submission, an email is automatically sent to the **Vicar, Trustee, and Secretary**. The requester also receives a confirmation email with a reference number.

Ensure these are set in **Customize → Church Information**:
- Vicar Email
- Trustee Email
- Secretary Email

If all three point to the same address (the default `info@sjioc.org`), only one email is sent (duplicates are removed).

### 14d. Database table

The `wp_sjioc_rentals` table is created automatically on first page load after the theme is activated. If the form returns "Failed to save your request," the table likely hasn't been created yet — switch to another theme and back, or wait for the next page load (an `init` hook creates it on first run and sets a flag to skip it thereafter).

### 14e. Admin panel

Go to **wp-admin → SJIOC → Hall Rentals** to:
- View all requests with status filters (Pending / Approved / Rejected / Cancelled)
- Open individual requests to review full details
- Update status and send an email to the requester automatically
- Export all records to CSV

---

## 15. CAPTCHA — Bot Protection (Google reCAPTCHA v3)

Protects the **Contact Us** and **Hall Rental** forms from automated bot submissions. Uses reCAPTCHA v3 — completely invisible to real users (no checkbox, no image puzzles). A second layer (honeypot hidden field) catches basic bots before reCAPTCHA is even checked.

> **Cost:** Free. Google allows up to 1 million assessments/month at no charge — far more than a church site will ever use.

### 15a. Get your API keys

1. Go to [g.co/recaptcha](https://www.google.com/recaptcha/admin/create) and sign in with a Google account
2. Fill in:
   - **Label:** `SJIOC Website`
   - **reCAPTCHA type:** reCAPTCHA v3
   - **Domains:** add your production domain (e.g. `sjioc.org`) and `localhost` for local testing
3. Click **Submit**
4. Copy the **Site Key** (public — goes in the page HTML) and **Secret Key** (private — used server-side only)

### 15b. Add keys to Customizer

1. Go to **wp-admin → Appearance → Customize → CAPTCHA — Bot Protection**
2. Paste the **Site Key** into "reCAPTCHA v3 Site Key"
3. Paste the **Secret Key** into "reCAPTCHA v3 Secret Key"
4. Click **Publish**

> Forms work normally **without** the keys — CAPTCHA is simply skipped if the keys are not set. Add them when you're ready to enable bot protection.

### 15c. What users see

Nothing. The form looks and behaves identically. A small Google badge appears in the bottom-right corner of the Contact and Hall Rental pages only (required by Google's terms). It is hidden on all other pages automatically.
