# SJIOC WordPress Theme — Portable Context Pack

> Paste this whole file into a fresh chat (e.g. claude.ai, no repo access) to give it working
> knowledge of this codebase without needing file access. It is a snapshot — verify anything
> load-bearing against the actual repo before acting on it in a follow-up session.

## What this is
Custom WordPress **classic theme** (not a plugin, not block/FSE) for St. John's Indian Orthodox
Church of Delaware Valley. No build step, no test suite — deployed by zipping the theme folder
and uploading via WP Admin, or SFTP to `/site/wwwroot/wp-content/themes/`. Hosted on **Azure App
Service**; `wp-config.php` lives at `/home/site/wwwroot/wp-config.php` on that host (not in this
repo — it's gitignored, holds DB creds).

## Stack
| layer | tech |
| --- | --- |
| language | PHP 8.1+ |
| platform | WordPress classic theme (template hierarchy, no custom routing) |
| db | MySQL via WordPress's `$wpdb` abstraction — custom tables prefixed `{wp_prefix}sjioc_*` |
| assets | all CSS in one `style.css`; all JS in `assets/js/main.js` (events page has its own split JS/CSS) |
| external services | Microsoft Graph (mail send + OneDrive), Google Calendar API, OpenAI (chat widget), Google reCAPTCHA |

## Governing rules (from this repo's CLAUDE.md — always apply when writing code for this project)
1. Ask before assuming — don't make product decisions unilaterally.
2. Surgical changes only — don't touch unrelated code.
3. No speculative abstractions/comments.
4. Prefer Customizer / WP Admin UI fields over hardcoded values.
5. **Security is non-negotiable**: nonce on every form/AJAX handler, `current_user_can()` on
   every admin action, sanitize all input / escape all output, `$wpdb->prepare()` for any query
   with user data, `defined('ABSPATH') || exit;` at the top of every PHP file, honeypot field on
   every public form.
6. **Azure cost discipline**: no new DB query on page load without a transient cache; no external
   HTTP calls on public page render (external calls are admin/AJAX-only); each page load adds at
   most 1 extra DB query; no polling/scheduled fetches — sync is manual/admin-triggered only.
7. `sjioc_` prefix on all custom functions, options, and DB tables.

## File map
| path | role |
| --- | --- |
| `functions.php` | loader — defines version/dir/uri constants, `require_once`s every `inc/*.php` module |
| `header.php` / `footer.php` | site chrome; footer has a 3-widget bar (contacts / celebrations / chat) + ticker |
| `front-page.php` | homepage template |
| `page-*.php` | page templates: about-us, about-hub, committees, leadership, our-history, worship-services, ministries, outreach, news, resources, events, photos, contact-us, give, hall-rental |
| `template-about-section.php` | shared template for Our Parish/Diocese/Church/Vicar (content picked via page meta `sjioc_about_page_key`) |
| `page.php` / `index.php` | generic page fallback / blog fallback |
| `style.css` | theme stylesheet header + all CSS |
| `assets/js/main.js` | nav, widgets, chat, filters, gallery |
| `assets/{js,css}/events.*` | events page UI, split out separately |
| `inc/*.php` | all feature/business logic — one file per feature, see below |

## Features (`inc/`)
| feature | file | notes |
| --- | --- | --- |
| theme setup, Customizer, email sending | `setup.php` | sends mail via Microsoft Graph API, falls back to SMTP; OAuth token cached in a transient; AJAX: `test_email`, `clear_mail_token` |
| custom post types | `post-types.php` | `sjioc_gallery`, `sjioc_contact` (directory), `sjioc_celeb`, `sjioc_announcement`, `sjioc_ministry` |
| contact form | `contact-form.php` | AJAX `sjioc_contact` (+nopriv) |
| AI chat widget | `chat.php` | AJAX `sjioc_chat` (+nopriv); calls **Azure OpenAI** (not plain OpenAI — needs `SJIOC_AZURE_OAI_ENDPOINT`/`_KEY` in wp-config.php or shows a "not configured" fallback); logs token usage. 2-tier dispatch only: plate lookup (local DB, no AI) → everything else goes to the AI with worship times + a Knowledge Base excerpt injected as context — there's no separate non-AI path for common questions like "what time is Holy Qurbana" |
| admin dashboard | `admin.php` | admin menu, token-usage/cost view |
| members registry | `members.php` | `sjioc_members` table create + list UI |
| member import | `import.php` | admin bulk-import page |
| celebrations (birthdays/anniversaries) | `celebrations.php` | custom WP-Cron interval |
| events | `events.php` | DB-backed; Google Calendar sync + ICS export/import + manual entry; AJAX `gcal_sync`, `ics_sync` |
| photo gallery / OneDrive | `sharepoint.php` | Microsoft Graph drive upload+sync; AJAX `od_sync`, `od_reset`, `clear_od_token`; OneDrive token cached in transient |
| vehicle registry | `vehicles.php` | `sjioc_vehicles` table |
| hall rental | `hall-rental.php` | booking form + admin review; AJAX `rental_request` (+nopriv), `rental_update_status`; uploads attachments to OneDrive via `sharepoint.php` |
| "New to Church" welcome form | `new-to-church.php` | AJAX `sjioc_new_to_church` (+nopriv); emails Vicar/Secretary, no DB table |
| reCAPTCHA | `recaptcha.php` | site/secret key helpers + server-side verify, used by public forms |
| office bearers (About page) | `office.php` | CPT `sjioc_office` + taxonomy `sjioc_office_group`; drives the About page's Leadership + Committees sections; has a static fallback if empty |
| about pages | `about-pages.php` | CPTs `sjioc_about_section` (Our Parish/Diocese/Church/Vicar) + `sjioc_milestone` (Parish Milestones); `sjioc_get_about_nav_map()` drives hub navigation + breadcrumb links |
| Vicar History | `vicar-history.php` | CPT `sjioc_vicar_history` (name=title, photo=featured image, meta `vicar_period_start`/`_end`); renders a circular-photo vertical timeline, shared by Our History + About Us pages |
| Outreach | `outreach.php` | CPT `sjioc_outreach`; card-grid + popup, same pattern as Ministries |
| News | `news.php` | CPT `sjioc_news`; card-grid + popup + in-PHP pagination |
| Resources | `resources.php` | CPT `sjioc_resource`; card grid, each card's icon auto-pulled from the linked site's own favicon (no image upload needed) |
| Weekly Bible Verse | `bible-verse.php` | Admin uploads a CSV (Reference, Verse Text) at SJIOC → Bible Verse; rotates weekly by piggybacking on the existing Celebrations cron (no new scheduled job); shown on the home page, hidden if no CSV uploaded |

## Data model (custom tables, `{wp_prefix}` prefix)
| table | key columns | source |
| --- | --- | --- |
| `sjioc_members` | id, cardex_no, member_seq, first/middle/last_name, date_of_birth, marital_status (M/S/W/D), wedding_date, gender, phone_number, email, address/city/state/zip_code/country, is_active, created_at/updated_at | `members.php` |
| `sjioc_events` | id, title, description, location, start_date, start_time, end_date, end_time, all_day, url, source (manual/gcal/ics), gcal_id (unique) | `events.php` |
| `sjioc_photos` | id, od_item_id (unique), od_drive_id, file_name, category, album, title, media_type, download_url, url_expires, created_at | `sharepoint.php` |
| `sjioc_vehicles` | id, license_plate (unique), owner_name, owner_phone, owner_email, vehicle_desc, created_at | `vehicles.php` |
| `sjioc_rentals` | id, first/last_name, email, phone, address, member_status, org_name, recommended_by, event_type, event_date, start/end_time, setup_date, setup_start/end_time, guests, event_purpose, use_kitchen, use_av, (+ status/attachment fields for admin review) | `hall-rental.php` |
| `sjioc_chat_usage` | id, usage_date (unique/day), prompt_tokens, completion_tokens, total_tokens, call_count | `chat.php` |

All schema created via `dbDelta()` (idempotent) on `after_switch_theme`, so re-activating the
theme is safe and applies new columns without data loss.

## External calls (admin/AJAX-triggered only — never on public page render, per cost rule above)
- `chat.php` → Azure OpenAI Service (not `api.openai.com`)
- `events.php` → Google Calendar API v3 (sync)
- `sharepoint.php` → `graph.microsoft.com/v1.0` drives + users (OneDrive photo/attachment sync)
- `setup.php` → `graph.microsoft.com` sendMail + `login.microsoftonline.com` (OAuth for mail)
- `recaptcha.php` → Google reCAPTCHA verify endpoint

## Config / secrets (defined as constants in `wp-config.php` on the Azure host, not in this repo)
`SJIOC_AZURE_TENANT_ID`, `SJIOC_AZURE_CLIENT_ID`, `SJIOC_AZURE_CLIENT_SECRET`,
`SJIOC_SHAREPOINT_SITE_ID`, `SJIOC_SMTP_*` (fallback if Graph mail fails), `SJIOC_GCAL_KEY`,
`SJIOC_AOAI_*` (Azure OpenAI, if used) / OpenAI key for chat, reCAPTCHA site/secret keys.
Some of these have a `wp_options`-based Customizer fallback (see `TODO.md` in-repo for the
current migration-to-env-vars status) — don't assume every constant is required at runtime,
check `defined()` guards in the relevant `inc/*.php` file.

## Known risk zones
- Public AJAX forms (contact, chat, hall rental, new-to-church) must keep nonce +
  `current_user_can()` (where admin-only) + sanitize/escape + honeypot + reCAPTCHA.
- OAuth tokens (mail, OneDrive) live in transients — never log or echo them; the
  clear-token AJAX actions must check capabilities.
- Any new external HTTP call must stay off the public page-render path.
- Any new page-load DB read should add at most 1 query and be transient-cached if not
  real-time.

## Recent hardening (2026-08-24)
- Fixed 3 broken honeypots (Contact Us + Hall Rental had a hidden field with no `name` attribute,
  so it never actually submitted; New-to-Church had no honeypot at all — built one).
- Swept ~10 save handlers missing `wp_unslash()` before sanitizing — without it, WP's automatic
  backslash-escaping of `$_POST` leaves a literal `\` before every apostrophe in saved text.
- Vehicle plate chat lookup: owner name is now privacy-masked (`sjioc_mask_name()` — first name
  visible, last name shows only first 2 letters + asterisks) before ever reaching the public chat
  response; full name stays visible only in the capability-gated WP Admin Vehicle Registry.

---
*Snapshot basis: repo `codebase.md` last updated 2026-08-24. Newer commits may
exist — treat this as a starting point, not ground truth, for anything beyond a quick chat.*