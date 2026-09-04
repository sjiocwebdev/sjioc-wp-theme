# Azure Performance & Cost Reference

Site hosted on **Azure App Service P0v3** (1 vCore, 4 GiB RAM — fixed-rate, pay for compute uptime not per request).
**DB:** Azure Database for MySQL Flexible Server **B2s** (2 vCores, 4 GiB RAM, 684 IOPS — fixed-rate).
Cost pressure = CPU/memory load per request. Higher load → need to scale up the plan.

Audit date: 2026-08-17

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
| Chat message — plate lookup | 1 (AJAX) | 1 — `sjioc_vehicles` SELECT + own rate-limit transient | None | 🟢 Light |
| Chat message — LLM (Azure OpenAI) | 1 (AJAX) | 2 — transient write + `sjioc_chat_usage` upsert | 1 — Azure OpenAI API | 🟡 Moderate |
| Parish Life page load | 1 | 1 — full `sjioc_photos` SELECT (all photos) | None | 🟢 Light |
| Parish Life — each photo in grid (first visit) | 1 per photo | 3 per photo — rate limit transient R+W + photo row SELECT; +1 write if URL cache miss | 1 per photo — SharePoint download | 🔴 Heavy (see note) |
| Parish Life — each photo in grid (repeat, <1hr) | 0 | 0 | 0 | 🟢 None — browser cache |
| Parish Life — video in lightbox (any visit) | 1 | 3 — rate limit R+W + photo SELECT; URL from 50-min cache | 0 — browser redirected to SharePoint directly | 🟢 Light |
| About hub / Our Parish / Diocese / Church / Vicar page load | 1 | 1 — `sjioc_get_about_sections()`, cached 24h | None | 🟢 Light |
| Our History page load | 1 | 1 — `sjioc_get_milestones()`, cached 24h | None | 🟢 Light |
| Leadership / Committees page load | 1 | 1 — office bearer query, cached 24h | None | 🟢 Light |
| Outreach page load | 1 | 1 — `sjioc_outreach` get_posts (not cached, same as Ministries) | None | 🟢 Light |
| News page load | 1 | 1 — `sjioc_news` get_posts, all published fetched then paginated in PHP (not cached, same as Ministries/Outreach) | None | 🟢 Light |
| Resources page load | 1 | 1 — `sjioc_resource` get_posts (not cached, small list, same pattern as Ministries/Outreach); favicon images load client-side straight from Google's public favicon service, zero server cost | None | 🟢 Light |
| Our History / About Us page load (Vicar section) | 1 | +1 — `sjioc_get_vicar_history()` get_posts (not cached, tiny 4-5 row list); replaces the old page-meta read at the same query cost | None | 🟢 Light |
| Home page — Bible Verse line | 0 extra | 0 extra — `get_option('sjioc_bible_verses')`/`sjioc_bible_verse_index` are small autoloaded options, same cost class as any other theme_mod read | None | 🟢 None |
| Home page — Welcome Video badge / Vicar's Message popup | 0 extra | 0 — theme_mod reads only; YouTube thumbnail + iframe load client-side, zero server cost, iframe only created on click | 0 (client-side only, not server-side) | 🟢 None |

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

### 5. Parish Life photo gallery — PHP bootstrap per image
Each photo thumbnail in an album fires a separate HTTP request to `/wp-json/sjioc/v1/photo/{id}`.
Every request bootstraps WordPress, hits the DB (rate limit transient + photo row), and downloads the
image from SharePoint. For a 50-photo album, that's **50 PHP bootstraps and 150 DB operations per
unique visitor**. This is the heaviest public-facing operation on the site.

**Mitigations already in place:**
- `Cache-Control: public, max-age=3600` — browser caches each image for 1 hour. Repeat visits within
  the hour cost zero PHP/DB.
- SharePoint download URL cached in a 50-min transient — subsequent unique visitors skip the Graph API
  call and go straight to the download.
- Photo proxy streams to a temp file (`stream=>true` + `readfile()`) — image bytes never held in PHP RAM,
  keeping per-request memory near zero even under concurrent album loads.
- Rate limit: 300 photo requests per IP per 60 seconds — prevents any single visitor from hammering the proxy.

**Videos are not proxied.** The proxy sends a 302 redirect to the SharePoint pre-auth download URL.
The browser fetches the video directly from SharePoint, which supports HTTP range requests (seek/scrub).
No PHP memory, no temp file, no proxy overhead per video.

**DB write peak (photos):** 20 concurrent users × 50 photos × 1 transient write = 1,000 writes per
album-load burst ≈ 0.28 writes/second sustained over 1 hour. Against the B2s 684 IOPS ceiling this is
0.04% utilisation. The constraint is App Service CPU (50 PHP bootstraps per user), not the DB.

**Scaling trigger:** If album load time degrades, add a server-side image cache (Azure CDN in front of
the proxy REST endpoint, or migrate to Azure Blob Storage with public read access).

### 6. Chat — two-tier dispatch (only plate lookups skip the LLM)
Chat messages are dispatched in order:
1. **License plate pattern** → local DB SELECT on `sjioc_vehicles`. No LLM call, own rate limit
   (15 lookups per IP per 5 minutes — separate budget from the LLM, but still capped to stop
   scripted registry scraping).
2. **Everything else → Azure OpenAI.** There is no "PHP intent" tier and no static-sorry-message
   gate before this — any message that isn't a recognized plate goes straight to the AI. Worship
   times (from Customizer values) and a targeted excerpt of the admin's Knowledge Base (only lines
   matching words in the message, ≤10 lines, out of the full ≤2000-char KB) are injected into the
   system prompt as context, keeping prompt tokens down. If `SJIOC_AZURE_OAI_ENDPOINT`/`_KEY` aren't
   set in wp-config.php, this step short-circuits and returns a fallback message instead of calling
   the API — this applies to *any* non-plate question, including common ones like "what time is
   Holy Qurbana", not just obscure ones.

**Token tracking:** Every LLM response includes actual `prompt_tokens`, `completion_tokens`,
`total_tokens` in the API response body. These are accumulated into a daily row in `sjioc_chat_usage`
(1 DB upsert per LLM call). Visible in WP Admin → SJIOC → Chat Settings → Actual Token Usage.

**DB write budget per chat message:**
- Plate lookup: 0 writes (just its own rate-limit transient)
- LLM call: 1 transient write (rate limit) + 1 `sjioc_chat_usage` upsert = 2 writes
- Rate limit: 5 LLM calls per IP per 3 minutes — caps worst-case DB writes from a single user.

### 7. No polling or cron anywhere
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
| Album load time degrades under traffic | Add Azure CDN in front of `/wp-json/sjioc/v1/photo/*` |
| Chat LLM token cost grows | Tighten KB content; the KB excerpt sent per call is already targeted (only matching lines, ≤10) rather than the full KB text |

---

## File Map for Performance-Sensitive Code

| File | What it does | DB hits |
|---|---|---|
| `inc/events.php` | Events REST, ICS gen, admin sync | 1 per REST call |
| `inc/chat.php` | Live chat — plate lookup, KB excerpt, Azure OpenAI LLM | 0 on load; 1–2 per message depending on path |
| `inc/hall-rental.php` | Rental form, OneDrive upload | 1 insert on submit |
| `inc/contact-form.php` | Contact form, SMTP | 0 (no DB) |
| `inc/celebrations.php` | Anniversary/birthday lookup | 1 (cached via transient) |
| `front-page.php` | Home page, events teaser | 1 via `sjioc_front_page_events()` |
| `page-events.php` | Events page template | 0 at render (REST fires after) |
| `page-photos.php` | Parish Life gallery — album grid + photo grid | 1 at render (full photos SELECT); then 3 per photo proxy request |
| `inc/sharepoint.php` | Photo proxy, delta sync, OneDrive token | 3 per photo (rate limit + URL cache + row SELECT); videos redirect, no proxy |
| `inc/about-pages.php` | About Sections + Milestones CPTs, nav map | 1 per data type, transient-cached 24h |
| `inc/office.php` | Office Bearers CPT (Leadership + Committees) | 1, transient-cached 24h |
| `page-about-hub.php` / `template-about-section.php` | About Us hub + Our Parish/Diocese/Church/Vicar | 0 at render — reads cached `sjioc_get_about_sections()` |
| `page-our-history.php` | Milestone timeline | 0 at render — reads cached `sjioc_get_milestones()` |
| `page-leadership.php` / `page-committees.php` | Office bearer listings | 0 at render — reads cached office data |
| `inc/outreach.php` | Outreach Programs CPT | 1 per page load (not cached, mirrors Ministries) |
| `page-outreach.php` | Outreach page — card grid + popup | 1 via `sjioc_outreach` get_posts |
| `inc/news.php` | SJIOC News CPT (native editor/thumbnail/excerpt) | 0 — CPT registration only |
| `page-news.php` | News page — card grid + popup + in-PHP pagination | 1 via `sjioc_news` get_posts (all, sliced in PHP) |
