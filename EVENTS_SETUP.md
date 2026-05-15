# Events — Setup & User Guide

## Is it available locally?

**Yes — most features work out of the box with no external configuration.**

| Feature | Works locally without setup? |
|---|---|
| Add events manually (WP Admin form) | ✅ Yes |
| Import from spreadsheet (CSV) | ✅ Yes |
| Events page (calendar + list view) | ✅ Yes — after Step 1 below |
| Download ICS button on events page | ✅ Yes |
| Outlook ICS sync (Sync from Outlook button) | ✅ Yes — paste ICS URL in admin |
| Google Calendar API sync | ❌ Needs API key |

The database table (`sjioc_events`) is created automatically the first time **any admin** logs into WordPress after the theme is active. No manual SQL needed.

---

## Step 1 — Create the Events Page

1. In **WP Admin → Pages**, create a new page with slug `events` (or find the existing one)
2. In the page editor sidebar, find **Page Attributes → Template**
3. Select **Events Calendar**
4. Publish or update the page
5. Visit `yoursite.com/events/` — the calendar loads immediately

---

## Step 2 — Add Events (three ways)

### Option A — Manual entry (one event at a time)

Go to **WP Admin → SJIOC → Events → Add Event**

Fill in:
- **Title** (required)
- **Start Date** (required) + **Start Time** (leave blank for all-day)
- **End Date** + **End Time** (optional)
- **All Day** checkbox — hides the time fields automatically
- **Location** — e.g. "Church Hall" or full address
- **Description** — shown in the event modal on the website
- **Link / URL** — optional external link

Click **Add Event**. It appears in the list below and on the website immediately.

To edit or delete, find the event in the **Upcoming Events** table. Only manually-added events have Edit / Delete buttons.

---

### Option B — Import from Spreadsheet (bulk import)

Go to **WP Admin → SJIOC → Events → Import from Spreadsheet**

**Step 1 — Get the template**

Click **Download template** to get a pre-formatted CSV file with sample rows and the correct column order.

**Step 2 — Fill it in (Excel or Google Sheets)**

Open the CSV in Excel (or Google Sheets). The columns are:

| Column | Required | Format | Example |
|---|---|---|---|
| Title | ✅ | Text | Parish Picnic |
| Start Date | ✅ | YYYY-MM-DD | 2026-06-07 |
| Start Time | | HH:MM (24-hr) | 10:00 |
| End Date | | YYYY-MM-DD | 2026-06-07 |
| End Time | | HH:MM (24-hr) | 14:00 |
| All Day | | Yes / No | No |
| Location | | Text | Church Grounds |
| Description | | Text | Annual outdoor gathering |
| URL | | Full URL | https://... |

**Tips:**
- Leave **Start Time** blank → event is automatically treated as all-day
- Set **All Day** to `Yes` to force all-day regardless of time columns
- Dates **must** be `YYYY-MM-DD` format. In Excel: format the date column as **Text** first, or use a custom format `YYYY-MM-DD` to prevent Excel from auto-converting dates
- Rows with missing Title or Start Date are skipped and reported

**Step 3 — Export as CSV**

- **Excel**: File → Save As → CSV UTF-8 (Comma delimited)
- **Google Sheets**: File → Download → Comma-separated values (.csv)

**Step 4 — Upload**

Back in **WP Admin → SJIOC → Events → Import from Spreadsheet**, choose the CSV file and click **Import Events**. A summary shows how many were imported and any skipped rows.

---

### Option C — Google Calendar API sync (optional)

See the **Google Calendar Setup** section below. Requires a Google Cloud API key.

---

## Outlook 365 Calendar — Configuration

Outlook calendar events can be shared with the website in two ways:

### Getting the Outlook ICS URL (for the subscribe link)

This gives website visitors a button to subscribe to the parish calendar in their own Outlook, Apple Calendar, or Google Calendar.

1. Open [Outlook on the web](https://outlook.office.com) and sign in
2. Click the **Settings** gear (top-right) → **View all Outlook settings**
3. Go to **Calendar → Shared calendars**
4. Under **Publish a calendar**:
   - Select the calendar you want to share (e.g. your main Calendar)
   - Set permissions to **Can view all details**
   - Click **Publish**
5. Copy the **ICS** link that appears (it looks like `https://outlook.live.com/owa/calendar/...`)
6. In **WP Admin → SJIOC → Events**, paste it into the **ICS Feed URL** field and click **Save Settings**

The **Subscribe (Outlook / GCal)** button on the Events page will now appear and work.

### Importing Outlook events into the website (current method — CSV)

Until the automated Outlook sync is built, here's how to get Outlook events onto the website via spreadsheet:

**Method 1 — Export from Outlook and import CSV**

1. In **Outlook on the web**: go to Calendar → Settings gear → **View all Outlook settings → Calendar → Export calendar** → choose date range → Export (downloads `.ics` file)
2. Open the `.ics` file in a text editor, or use a tool like [ICS to CSV converter](https://www.indigoblue.eu/ics2csv/) to convert it
3. Reformat to match the import template columns (Title, Start Date, etc.)
4. Upload via **SJIOC → Events → Import from Spreadsheet**

**Method 2 — Add events directly in WP Admin**

For recurring events (Sunday services, Bible study, etc.) it's often faster to just add them directly in **SJIOC → Events → Add Event** since the website only needs upcoming events.

### Sync from Outlook (ICS sync)

1. Paste Outlook ICS URL in **WP Admin → SJIOC → Events → ICS Feed URL** and click **Save Settings**
2. Click **Sync from Outlook** — events are fetched from the Outlook calendar and imported into the website DB
3. Re-sync any time new events are added to Outlook
4. Synced events appear with an **Outlook** source tag in the events list and cannot be edited via WP Admin
5. Manually-added events in WP Admin are unaffected

---

## For Visitors — Subscribing to the Parish Calendar

The Events page always shows a **Download ICS** button. Visitors can use this to add parish events to their personal calendar app.

### Import into Outlook (Microsoft 365 / Outlook.com)

1. Click **Download ICS** on the Events page — a `.ics` file downloads
2. Open **Outlook** (desktop or web) → Calendar
3. Click **Add calendar** (web) or **File → Open & Export → Import/Export** (desktop)
4. Select **Import an iCalendar (.ics) file** → choose the downloaded file
5. Click **Import** — events are added to your calendar

> Note: this is a one-time import (snapshot). Events added later to the parish website will not sync automatically unless using the Subscribe link (see below).

### Subscribe in Outlook (live, auto-updating)

If the **Subscribe (Outlook / GCal)** button appears on the Events page:

1. Click **Subscribe (Outlook / GCal)** — opens Outlook web
2. Click **Add calendar** in the prompt
3. The calendar stays in sync automatically — new parish events appear as they're added

### Subscribe in Apple Calendar (Mac / iPhone)

1. Click **Download ICS** → when prompted, choose **Subscribe** (not Import)
   - Or: File → New Calendar Subscription → paste the ICS URL manually
2. Set refresh to **Every day** or **Every week**
3. Click **Subscribe**

The ICS URL to paste is:  
`https://yoursite.com/wp-json/sjioc/v1/calendar.ics`

### Subscribe in Google Calendar

1. Open [Google Calendar](https://calendar.google.com)
2. Click the **+** next to **Other calendars** → **From URL**
3. Paste the ICS URL: `https://yoursite.com/wp-json/sjioc/v1/calendar.ics`
4. Click **Add calendar**

---

## Google Calendar Setup (optional)

Only needed if you want to sync from a Google Calendar via the API.

### Step 1 — Create an API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → **APIs & Services → Library**
2. Enable the **Google Calendar API**
3. Go to **Credentials → Create Credentials → API key**
4. Copy the key and restrict it:
   - **Application restrictions → HTTP referrers**: `yoursite.com/*`
   - **API restrictions → Google Calendar API**

### Step 2 — Make the Calendar Public

1. Open **Google Calendar** → three-dot menu on the calendar → **Settings and sharing**
2. Under **Access permissions**, check **Make available to public**
3. Copy the **Calendar ID** (e.g. `abc123@group.calendar.google.com`)
4. Copy the **Public address in iCal format** (the ICS URL)

### Step 3 — Add to wp-config.php or WP Admin

**wp-config.php** (takes precedence):
```php
define( 'SJIOC_GCAL_KEY', 'YOUR_API_KEY' );
define( 'SJIOC_GCAL_ID',  'abc123@group.calendar.google.com' );
define( 'SJIOC_GCAL_ICS', 'https://calendar.google.com/calendar/ical/.../basic.ics' );
```

**Or via WP Admin → SJIOC → Events** → paste values → Save Settings → Sync from Google Calendar.

---

## Technical Reference

### File map

| File | Purpose |
|---|---|
| `inc/events.php` | DB table, REST endpoints, ICS generation, CSV import, GCal sync, admin page |
| `page-events.php` | Events page template (`Template Name: Events Calendar`) |
| `assets/js/events.js` | Calendar grid, list view, event modal, view preference (localStorage) |
| `assets/css/events.css` | All events page styles, scoped to theme CSS variables |

### REST endpoints

| Endpoint | Description |
|---|---|
| `GET /wp-json/sjioc/v1/events?months=6` | Returns upcoming events as JSON (from DB) |
| `GET /wp-json/sjioc/v1/calendar.ics` | Returns all events as a downloadable ICS file |

### Database table — `wp_sjioc_events`

| Column | Type | Notes |
|---|---|---|
| id | int | Auto-increment primary key |
| title | varchar(255) | Event name |
| description | longtext | Shown in modal on events page |
| location | varchar(255) | Venue or address |
| start_date | date | Required |
| start_time | time | NULL = all-day |
| end_date | date | Optional |
| end_time | time | NULL for all-day |
| all_day | tinyint(1) | 1 = all-day event |
| url | varchar(500) | External link (shown in modal) |
| source | varchar(20) | `manual` or `gcal` |
| gcal_id | varchar(255) | Google Calendar event ID (for dedup on sync) |

Table is created automatically on first admin login after deployment. No manual SQL needed.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Events page shows "Loading events…" forever | Check the browser console for errors; visit `/wp-json/sjioc/v1/events` directly to test the REST endpoint |
| "Could not load events" on the page | REST API may be blocked — check permalink settings (WP Admin → Settings → Permalinks → Save) |
| Template not showing in page editor | Ensure `page-events.php` is in the theme root and the theme is active |
| Imported events show wrong dates | Check date format in CSV — must be `YYYY-MM-DD`. In Excel, format the date column as Text |
| Download ICS button is missing | Should always appear — check `wp-json/sjioc/v1/calendar.ics` loads in browser |
| Subscribe link not showing | Paste an Outlook or Google Calendar ICS URL into **SJIOC → Events → ICS Feed URL** |
| GCal sync returns "No events returned" | Check the Calendar ID is correct and the calendar is set to public |
