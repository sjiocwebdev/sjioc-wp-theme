<!-- codebase.md — LLM orientation map. Compact by design. Not absolute truth: verify target files before editing. -->

# SJIOC Delaware Valley WordPress Theme — codebase map

## meta
- last_updated: 2026-07-07
- git_sha: b0bc0e6
- estimated_map_tokens: ~1400
- baseline_orientation_tokens: ~20000 (estimate; <50 tracked source files)
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
| version | SJIOC_VER 2.0.1 |
| db | MySQL via `$wpdb` (`{prefix}sjioc_*` custom tables) |
| host | Azure App Service (WordPress on App Service) |
| assets | `style.css` (all CSS), `assets/js/main.js`, events split out |

## commands
| purpose | command |
| --- | --- |
| lint php | `php -l <file>` |
| deploy | zip theme -> WP Admin Themes upload, or SFTP to `/site/wwwroot/wp-content/themes/` |
| build/test | n/a (classic theme, no build step, no test suite) |

## project_map
| path | role |
| --- | --- |
| functions.php | loader: defines SJIOC_VER/DIR/URI, `require_once` all 15 inc modules |
| header.php / footer.php | site chrome; footer = 3-widget bar (contacts/celebrations/chat) + ticker |
| front-page.php | home template |
| page-*.php | page templates (about-us, worship-services, ministries, events, photos, contact-us, give, hall-rental) |
| page.php / index.php | generic + blog fallback |
| style.css | theme declaration + full CSS |
| assets/js/main.js | nav, widgets, chat, filters, gallery |
| assets/{js,css}/events.* | events page UI |
| inc/ | all feature logic (see feature_index) |
| *.md, SQL_*.sql | docs + one-off data seed scripts (not runtime) |

## feature_index
| feature | file | key hooks / notes |
| --- | --- | --- |
| theme setup, customizer, email send | inc/setup.php | Graph sendMail (SMTP fallback); mail OAuth token in transient; ajax `test_email`, `clear_mail_token` |
| CPTs | inc/post-types.php | `sjioc_gallery`, `sjioc_contact`(directory), `sjioc_celeb`, `sjioc_announcement`, `sjioc_ministry` |
| contact form | inc/contact-form.php | ajax `sjioc_contact` (+nopriv) |
| chat assistant | inc/chat.php | ajax `sjioc_chat` (+nopriv); OpenAI GPT-4o mini; usage table |
| admin dashboard | inc/admin.php | admin menu, token-usage/cost view, token clear actions |
| members | inc/members.php | members table create + list |
| member import | inc/import.php | admin import page |
| celebrations | inc/celebrations.php | custom cron interval; birthdays/anniversaries |
| events | inc/events.php | DB-backed; Google Calendar + ICS sync + manual; ajax `gcal_sync`,`ics_sync` |
| photos / OneDrive | inc/sharepoint.php | Graph drive upload/sync; ajax `od_sync`,`od_reset`,`clear_od_token`; od token in transient |
| vehicles registry | inc/vehicles.php | vehicles table |
| hall rental | inc/hall-rental.php | booking form + admin; ajax `rental_request`(+nopriv),`rental_update_status`; OneDrive upload |
| new to church | inc/new-to-church.php | ajax `sjioc_new_to_church` (+nopriv) + notification email |
| recaptcha | inc/recaptcha.php | site/secret key helpers + verify |
| office bearers (About page) | inc/office.php | CPT `sjioc_office` + taxonomy `sjioc_office_group`; drives About Leadership + Committees sections; meta box role/bio/order; `sjioc_render_office_*()`; static fallback in page-about-us.php |

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
- functions.php -> all inc/*.php (load order: setup, recaptcha, post-types, contact-form, chat, admin, members, celebrations, import, events, sharepoint, vehicles, hall-rental, new-to-church)
- hall-rental.php -> sharepoint.php (OneDrive upload of rental attachments)
- contact-form / new-to-church / hall-rental / chat -> setup.php (email send)
- public forms -> recaptcha.php (verification)

## external_calls (admin/ajax only — never on public page render, per CLAUDE.md)
- inc/chat.php -> api.openai.com (chat completions)
- inc/events.php -> googleapis calendar/v3 (GCal sync)
- inc/sharepoint.php -> graph.microsoft.com /v1.0 drives+users (OneDrive)
- inc/setup.php -> graph.microsoft.com sendMail + login.microsoftonline (OAuth)
- inc/recaptcha.php -> google reCAPTCHA verify

## risk_zones
| area | note |
| --- | --- |
| public ajax forms (contact, chat, rental, new_to_church) | must keep nonce + `current_user_can` where admin + sanitize/escape + honeypot + recaptcha |
| OAuth tokens (mail/od) | stored in transients; never log or echo; clear-token ajax must check caps |
| external HTTP | keep off frontend render; admin/ajax only (Azure cost + latency) |
| page-load DB | each page +1 query max; cache non-realtime reads in transients |

## recent_changes
| date | change | files |
| --- | --- | --- |
| 2026-07-07 | Office Bearers CPT makes About Leadership + Committees editable in WP Admin (transient-cached, static fallback) | inc/office.php, functions.php, page-about-us.php |

## rules
- Follow CLAUDE.md: ask before assuming, surgical changes, `sjioc_` prefix, `defined('ABSPATH')||exit`.
- CSS -> style.css, JS -> assets/js/main.js; no inline styles/scripts, no `@` suppression.
- See AZURE_PERF.md for per-page request/DB baseline before adding features.