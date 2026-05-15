# Azure Performance & Cost Reference

Site hosted on **Azure App Service** (fixed-rate plan — pay for compute uptime, not per request).
Cost pressure = CPU/memory load per request. Higher load → need to scale up the plan.

Audit date: 2026-05-15

---

## Per-Page Request & DB Hit Baseline

| Page | PHP Executions | DB Queries | External Requests | Load |
|---|---|---|---|---|
| Home (`/`) | 1 | 1 — `sjioc_get_db_events(1)`, 3 rows | None | 🟢 Light |
| Events (`/events/`) page load | 1 | 0 | None | 🟢 Light |
| Events REST call (JS fires on load) | 1 | 1 — `sjioc_get_db_events(6)` | None | 🟡 Moderate |
| Events ICS download | 1 | 1 — all events (12 months) | None | 🟢 Light |
| Hall Rental page load | 1 | 0 — static form | None | 🟢 Light |
| Hall Rental form submit | 1 | 1 — booking insert | 1 — OneDrive upload (Azure) | 🟡 Moderate |
| Contact form submit | 1 | 0 | 1 — SMTP send | 🟡 Moderate |
| New-to-church modal | 0 — static HTML in footer | 0 | 0 | 🟢 None |
| Any WP Admin page load | 1 | 1–3 — options + events list | None | 🟢 Light |
| Admin: Sync from Outlook | 1 (AJAX) | N — 1 upsert per event | 1 — fetch Outlook ICS URL | 🟡 Moderate (admin-only) |
| Admin: Sync from Google Calendar | 1 (AJAX) | N — 1 upsert per event | 1 — Google Calendar API | 🟡 Moderate (admin-only) |
| Admin: CSV import | 1 (multipart POST) | N — 1 insert per row | None | 🟡 Moderate (admin-only) |

---

## Key Observations

### 1. Events page = 2 PHP bootstraps per visitor
The Events page makes two separate HTTP requests: one for the HTML page, and one for the REST endpoint
(`/wp-json/sjioc/v1/events`) fired by `events.js` on load. Both bootstrap WordPress and hit the DB.

At current traffic (tens of visitors/day for a parish site) this is fine. If traffic grows, the fix is a
**WP transient cache on `sjioc_get_db_events()`** — cache for 30 minutes, invalidate on any event
add/edit/delete/sync. This would reduce the REST endpoint from a DB query to a memory read.

### 2. ICS endpoint has no rate limiting
`/wp-json/sjioc/v1/calendar.ics` is publicly accessible with no rate limit. Someone could hammer it in a
loop. It's cheap per call but worth adding a `Cache-Control: public, max-age=1800` header — already done.
Consider adding the same IP transient rate limiting used on the chat endpoint if abuse is observed.

### 3. OneDrive upload adds latency to hall rental, not cost
The external call to Microsoft's API on hall rental submission doesn't affect App Service billing but
adds 1–3 seconds to the user's form response time. Already handled asynchronously where possible.

### 4. Admin sync operations are the heaviest but safe
Outlook ICS sync and GCal sync are the most resource-intensive operations — they fetch an external URL,
parse the response, and execute N upsert queries. They are manual/admin-triggered only and never run
automatically, so they have zero impact on public-facing page load.

### 5. No polling or cron anywhere
No `wp_schedule_event` or background fetch is registered. All sync is on-demand. This is intentional —
automatic background sync would add idle load on Azure even when no one is on the site.

---

## Rules for New Features

Before adding any new feature, answer these:

1. **Does it add a DB query to a public page?** → Must cache with a transient (30 min minimum).
2. **Does it make an external HTTP call on page render?** → Move it to admin/AJAX only.
3. **Does it register a cron or polling loop?** → Do not. All sync must be manual.
4. **Does it add a new REST endpoint?** → Ensure it caches or rate-limits.
5. **Does it add a new public form?** → One DB insert on submit is acceptable. No chained queries.

---

## Scaling Trigger Points

| Condition | Action |
|---|---|
| Events page load time > 2s | Add transient cache to `sjioc_get_db_events()` |
| ICS endpoint abuse observed | Add IP rate limiting (same pattern as chat) |
| DB connections spiking | Upgrade App Service plan or add object cache (Redis) |
| OneDrive upload timeouts | Make upload async (queue + retry pattern) |

---

## File Map for Performance-Sensitive Code

| File | What it does | DB hits |
|---|---|---|
| `inc/events.php` | Events REST, ICS gen, admin sync | 1 per REST call |
| `inc/chat.php` | Live chat with rate limiting | 0 on load, 1 per message |
| `inc/hall-rental.php` | Rental form, OneDrive upload | 1 insert on submit |
| `inc/contact-form.php` | Contact form, SMTP | 0 (no DB) |
| `inc/celebrations.php` | Anniversary/birthday lookup | 1 (cached via transient) |
| `front-page.php` | Home page, events teaser | 1 via `sjioc_front_page_events()` |
| `page-events.php` | Events page template | 0 at render (REST fires after) |
