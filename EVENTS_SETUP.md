# Events — Google Calendar Setup

## Overview

The Events page fetches upcoming events from a **Google Calendar** using the Calendar REST API (server-side, API key only — no OAuth). Events are cached for 1 hour.

---

## Step 1 — Create a Google Cloud API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → **APIs & Services → Library**
2. Enable the **Google Calendar API**
3. Go to **APIs & Services → Credentials → Create Credentials → API key**
4. Copy the key
5. Restrict it:
   - **Application restrictions → HTTP referrers**: add your domain (e.g. `sjiocdelaware.org/*`)
   - **API restrictions → Restrict key → Google Calendar API**

---

## Step 2 — Make the Calendar Public

1. Open **Google Calendar** → click the three-dot menu on your parish calendar → **Settings and sharing**
2. Under **Access permissions**, check **Make available to public**
3. Copy the **Calendar ID** shown under **Integrate calendar** (looks like `abc123@group.calendar.google.com`)
4. Copy the **Public address in iCal format** (the ICS URL, for the subscribe link)

---

## Step 3 — Add Credentials

### Option A — wp-config.php (recommended for production)

Add to `wp-config.php` before the "stop editing" line:

```php
/* SJIOC — Google Calendar Events */
define( 'SJIOC_GCAL_KEY', 'YOUR_API_KEY_HERE' );
define( 'SJIOC_GCAL_ID',  'YOUR_CALENDAR_ID@group.calendar.google.com' );
define( 'SJIOC_GCAL_ICS', 'https://calendar.google.com/calendar/ical/YOUR_CALENDAR_ID/public/basic.ics' );
```

The local `wp-config.php` already has empty placeholders — fill them in.

### Option B — WordPress Admin

Go to **SJIOC → Events** in the admin and fill in the fields there. Constants in `wp-config.php` always take precedence over saved options.

---

## Step 4 — Assign the Page Template

1. In WordPress Admin → **Pages**, find the `/events/` page (or create one with slug `events`)
2. In the page editor, set **Page Attributes → Template** to **Events Calendar**
3. Publish/update the page

---

## How It Works

| File | Purpose |
|---|---|
| `inc/events.php` | API fetch, REST endpoint, admin settings, front-page teaser |
| `page-events.php` | Events page template (Template Name: Events Calendar) |
| `assets/js/events.js` | Calendar grid, list view, event modal, subscribe links |
| `assets/css/events.css` | All events page styles |

**REST endpoint**: `GET /wp-json/sjioc/v1/events?months=6`
- Cached for 1 hour via WP transients (`sjioc_gcal_events_{months}`)
- Cache is cleared whenever settings are saved in the admin

**Front-page teaser**: `sjioc_front_page_events()` fetches the next 1 month and returns 3 events. Falls back to static content if the calendar is not configured.

---

## Event Categories

Google Calendar does not expose categories through the API. Use **event color** in Google Calendar to distinguish event types — this can be used for filtering if needed in a future enhancement.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| "Could not load events" on the page | Check the API key and Calendar ID in wp-config or admin settings |
| Events not updating | Cache TTL is 1 hour — save the settings page to bust it immediately |
| Subscribe button not showing | Fill in `SJIOC_GCAL_ICS` or `SJIOC_GCAL_ID` in settings |
| Template not found in page editor | Make sure `page-events.php` is in the theme root and the theme is active |
