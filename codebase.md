<!-- codebase.md — LLM orientation map. Compact by design. Not absolute truth: verify target files before editing. -->

# SJIOC Delaware Valley WordPress Theme — codebase map

## meta
- last_updated: 2026-08-21
- git_sha: uncommitted (many changes below are local-only; check `git status` before assuming deployed state)
- estimated_map_tokens: ~1900
- baseline_orientation_tokens: ~20000 (estimate; <60 tracked source files)
- freshness: fresh

## legend
- CPT = WordPress custom post type
- Graph = Microsoft Graph API
- ajax = admin-ajax handler (action name after `wp_ajax[_nopriv]_`)
- -> = requires / calls / depends on

## stack
| layer | tech |
| --- | --- |
| language | PHP 8.1+ |
| platform | WordPress classic theme (template hierarchy) |
| version | SJIOC_VER 2.0.16 (bump on every style.css/main.js change — browsers cache by this query string) |
| db | MySQL via `$wpdb` (`{prefix}sjioc_*` custom tables) |
| host | Azure App Service (WordPress on App Service) |
| assets | `style.css` (all CSS), `assets/js/main.js`, events split out |

## commands
| purpose | command |
| --- | --- |
| lint php | `php -l <file>` (no local PHP available in some dev envs — verify manually) |
| deploy | zip theme -> WP Admin Themes upload, or SFTP to `/site/wwwroot/wp-content/themes/`. **Deploy gotchas seen repeatedly this session:** wrong upload folder (files silently never land), PHP opcache surviving a portal "Restart" (SJIOC_VER stuck on old value — try Stop+Start, not Restart), browser CSS/JS cache (always bump SJIOC_VER + hard refresh) |
| build/test | n/a (classic theme, no build step, no test suite) |

## project_map
| path | role |
| --- | --- |
| functions.php | loader: defines SJIOC_VER/DIR/URI, `require_once` all 18 inc modules |
| header.php / footer.php | site chrome; footer = 4-tab widget bar (contacts/celebrations/chat/follow) + ticker + Service Times/Quick Links/Contact Us columns |
| front-page.php | home template |
| page-*.php | page templates: about-us, about-hub, committees, leadership, our-history, worship-services, ministries, outreach, news, events, photos, contact-us, give, hall-rental |
| template-about-section.php | shared template for Our Parish/Diocese/Church/Vicar (picks content via `sjioc_about_page_key` page meta) |
| page.php / index.php | generic + blog fallback (bare-bones, not styled — no `single.php` exists) |
| style.css | theme declaration + full CSS |
| assets/js/main.js | nav, widgets, chat, filters, gallery, committees tabs/accordion |
| assets/{js,css}/events.* | events page UI |
| inc/ | all feature logic (see feature_index) |
| *.md, SQL_*.sql | docs + one-off data seed scripts (not runtime) |

## feature_index
| feature | file | key hooks / notes |
| --- | --- | --- |
| theme setup, customizer, email send | inc/setup.php | Graph sendMail (SMTP fallback via `pre_wp_mail`); Graph now supports `Cc:` header too (`sjioc_graph_send_mail`); mail OAuth token in transient; social icons (`sjioc_social_icons()` header/footer, `sjioc_follow_rows()` widget-bar panel) — full-brand-color SVGs, shared data via `sjioc_social_links_data()` |
| CPTs | inc/post-types.php | `sjioc_gallery`, `sjioc_contact`(directory), `sjioc_celeb`, `sjioc_announcement`, `sjioc_ministry` |
| about pages | inc/about-pages.php | CPTs `sjioc_about_section` (Our Parish/Diocese/Church/Vicar text) + `sjioc_milestone` (Parish Milestones timeline, admin: Parish Milestones); **Vicar Leadership Timeline** meta box on the "Our History" page itself (`sjioc_vicar_timeline` postmeta, JSON, one-line-per-vicar textarea UI — `Period \| Name`); `sjioc_get_about_nav_map()` (cached page-by-template lookup, also exposes `sjioc_get_our_history_page_id()`) drives About Us hub card links AND lets page-about-us.php pull the same Milestones/Vicar data as page-our-history.php (no duplicate entry) |
| outreach | inc/outreach.php | CPT `sjioc_outreach`; mirrors Ministries' card-grid + popup-modal pattern, no transient cache (small dataset, same as Ministries) |
| news | inc/news.php | CPT `sjioc_news` (native editor/thumbnail/excerpt — no custom meta box); `page-news.php` = card grid + popup modal (reuses Ministries/Outreach `.mdgrid`/`.min-modal` pattern) + in-PHP pagination (fetch-all then `array_slice`, stays within +1-query budget) |
| contact form | inc/contact-form.php | ajax `sjioc_contact` (+nopriv); rate-limited (3/10min per IP, same transient pattern as chat); email body fields are `esc_html()`'d (was raw-interpolated — fixed); **subjects + routing are admin-configurable**: `sjioc_contact_subjects()` reads option `sjioc_contact_subjects` (JSON), admin UI at SJIOC → Contact Form is one-line-per-subject textarea: `Label \| to@email \| cc1@email, cc2@email` (CC optional); falls back to original 9-subject hardcoded list until admin saves |
| chat assistant | inc/chat.php | ajax `sjioc_chat` (+nopriv); OpenAI GPT-4o mini; usage table; 3-tier dispatch (plate lookup -> PHP intent -> LLM) |
| admin dashboard | inc/admin.php | admin menu (`sjioc` top-level + submenus incl. Email Settings, Contact Form); token-usage/cost view, token clear actions |
| members | inc/members.php | members table create + list |
| member import | inc/import.php | admin import page |
| celebrations | inc/celebrations.php | custom cron interval; birthdays/anniversaries |
| events | inc/events.php | DB-backed; Google Calendar + ICS sync + manual; ajax `gcal_sync`,`ics_sync`; nav renamed "Updates" (About > News + Calendar submenus) |
| photos / OneDrive | inc/sharepoint.php | Graph drive upload/sync; ajax `od_sync`,`od_reset`,`clear_od_token`; od token in transient |
| vehicles registry | inc/vehicles.php | vehicles table |
| hall rental | inc/hall-rental.php | booking form + admin; ajax `rental_request`(+nopriv),`rental_update_status`; OneDrive upload; staff notify = **To Secretary, Cc Vicar+Trustee** (single email, was 3 separate To's); visitor gets a confirmation email + later status-change emails — Contact Us and New to SJIOC do NOT email the visitor, staff-only |
| new to church | inc/new-to-church.php | ajax `sjioc_new_to_church` (+nopriv); staff notify = **To Secretary, Cc Vicar+Trustee** (was 3 separate To's incl. Trustee never included before) |
| recaptcha | inc/recaptcha.php | site/secret key helpers + verify; fails open (allows submission) if unconfigured or Google unreachable |
| office bearers (About page) | inc/office.php | CPT `sjioc_office` + taxonomy `sjioc_office_group`; drives About Leadership + Committees sections; meta box role/bio/order; `sjioc_render_office_*()`; static fallback in page-about-us.php. Group placement (section/tab/style/order) is **term meta**, editable per-group on the Groups screen (SJIOC → Groups submenu, added because the taxonomy has no menu link by default under a shared top-level menu) — not hardcoded; only the 2 Committees tab labels (`sjioc_office_tabs()`) are still fixed in code. 5 display styles now: grid/roles/card/pill/**text** (text = name+role+bio, no photo — works for both Leadership-section and Committee-section groups; leadership renderer tracks each person's style individually since people from differently-styled groups get merged into one grid) |

## data_model
| table (`{prefix}` prefix) | source file |
| --- | --- |
| sjioc_chat_usage (daily tokens) | inc/chat.php |
| sjioc_events | inc/events.php |
| sjioc_members | inc/members.php |
| sjioc_photos | inc/sharepoint.php |
| sjioc_vehicles | inc/vehicles.php |
| sjioc_rentals | inc/hall-rental.php (setup creates, admin reads) |

## dependency_edges
- functions.php -> all inc/*.php (load order: setup, recaptcha, post-types, contact-form, chat, admin, members, celebrations, import, events, sharepoint, vehicles, hall-rental, new-to-church, office, about-pages, outreach, news)
- hall-rental.php -> sharepoint.php (OneDrive upload of rental attachments)
- contact-form / new-to-church / hall-rental / chat -> setup.php (email send)
- public forms -> recaptcha.php (verification)
- page-about-us.php -> about-pages.php (`sjioc_get_milestones()`, `sjioc_get_vicar_timeline()` via `sjioc_get_our_history_page_id()`) so Milestones/Vicar content matches page-our-history.php without duplicate entry

## external_calls (admin/ajax only — never on public page render, per CLAUDE.md)
- inc/chat.php -> api.openai.com (chat completions)
- inc/events.php -> googleapis calendar/v3 (GCal sync)
- inc/sharepoint.php -> graph.microsoft.com /v1.0 drives+users (OneDrive)
- inc/setup.php -> graph.microsoft.com sendMail (now w/ Cc support) + login.microsoftonline (OAuth)
- inc/recaptcha.php -> google reCAPTCHA verify

## risk_zones
| area | note |
| --- | --- |
| public ajax forms (contact, chat, rental, new_to_church) | must keep nonce + `current_user_can` where admin + sanitize/escape + honeypot + recaptcha; contact form additionally rate-limited (3/10min/IP) |
| OAuth tokens (mail/od) | stored in transients; never log or echo; clear-token ajax must check caps |
| external HTTP | keep off frontend render; admin/ajax only (Azure cost + latency) |
| page-load DB | each page +1 query max; cache non-realtime reads in transients |
| email body HTML | always `esc_html()` user-submitted fields before interpolating into HTML email bodies (was a gap in contact-form.php, fixed) |
| `add_action` with multi-param closures | WordPress only passes 1 arg to a hook callback by default — a closure declaring 2+ params needs explicit `add_action($hook, $cb, $priority, $accepted_args)` or it fatals with `ArgumentCountError`. Bit us once on `sjioc_office_group_edit_form_fields` |
| `.mdcard img { height:100% }` (Ministries/Outreach/News shared card) | Works on desktop (2-col grid, image row height is set by the text column). On mobile (1-col stack) the image's row has nothing else to anchor its height, so `height:100%` resolves to `auto` per spec — a portrait photo can render very tall, pushing card text far below the fold. Fixed with a flat `height:200px` inside the `max-width:640px` media query; don't revert to a percentage height there |
| Unicode dingbats (☎ ✝ 📍 etc.) as icons | Renders inconsistently across OS/browser emoji fonts — color can't be overridden via CSS `color`, and some render in a jarring default color (seen: black phone glyph, blue/purple cross). Site convention now: hand-rolled inline SVG in `var(--go)` for any icon that needs to match theme color, not raw `&#xxxx;`/emoji characters |

## recent_changes
| date | change | files |
| --- | --- | --- |
| 2026-07-07 | Office Bearers CPT makes About Leadership + Committees editable in WP Admin (transient-cached, static fallback) | inc/office.php, functions.php, page-about-us.php |
| 2026-08-20 | Office Bearer groups became fully admin-configurable — section/tab/style/order moved from a hardcoded PHP array to term meta on `sjioc_office_group`, editable per-group; one-time migration backfills the original 13 groups so existing content renders unchanged | inc/office.php |
| 2026-08-20/21 | About Us hub system (About Us Hub, Our Parish/Diocese/Church/Vicar, Leadership, Committees, Our History as dedicated pages), Outreach CPT+page, News CPT+page, Vicar Leadership Timeline (new page-level meta box), header/footer social icons redesigned to full-brand-color SVGs + "Follow" tab added to the bottom widget bar, Contact Form subjects made admin-configurable with per-subject CC, Graph API mail gained Cc support, contact form rate-limited + email-body XSS fix, Hall Rental/New-to-Church staff notify switched from 3 separate To's to one To(Secretary)+Cc(Vicar,Trustee) email | inc/about-pages.php, inc/outreach.php, inc/news.php, page-*.php (many), inc/setup.php, inc/contact-form.php, inc/admin.php, inc/hall-rental.php, inc/new-to-church.php, style.css |
| 2026-08-21 | Mobile fixes: widget-bar tabs no longer overflow off-screen (icon-only breakpoint raised 380px->640px, one consolidated mobile rule instead of two); widget bar tabs now show icon+label only for the active/last-tapped tab (Chat expanded by default) via new `sjiocSetExpandedTab()`; fixed `.mdcard` images rendering oversized on mobile (see risk_zones); replaced remaining raw cross-glyph icons (New to SJIOC band+popup, About Us Core Values card, Hall Rental terms) with gold SVGs; removed the giant low-opacity `::before` cross watermark on the New to SJIOC band (was visible as a stray colored overlay on mobile). Office Bearers gained a 5th display style, "Text Card" (name+role+bio, no photo) — see feature_index | style.css, footer.php, assets/js/main.js, front-page.php, page-about-us.php, page-hall-rental.php, inc/office.php, functions.php |

## rules
- Follow CLAUDE.md: ask before assuming, surgical changes, `sjioc_` prefix, `defined('ABSPATH')||exit`.
- CSS -> style.css, JS -> assets/js/main.js; no inline styles/scripts, no `@` suppression.
- See AZURE_PERF.md for per-page request/DB baseline before adding features.
- Prefer the "one entry per line in a plain textarea" pattern for small admin-editable lists (Vicar Timeline, Contact Form Subjects) over dynamic add/remove-row JS UIs — proven simpler for this site's admin to actually use.
