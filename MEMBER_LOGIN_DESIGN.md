# Member Login — Design Document

**Status:** Phase 1 built & locally tested · 2026-09-06
Sections 3–14 are the broader roadmap. **Section 0 is what actually shipped** — where
it differs from the roadmap below, Section 0 wins.

Passwordless front-end login for parishioners, gated to the existing church directory.
No username/password anywhere in the flow.

---

## 0. Phase 1 — as built (2026-09-06)

### Scope delivered
Magic-link email login **and** email OTP, plus a member dashboard that greets the
signed-in member by name. Google sign-in, member directory page, documents, and
giving are still future phases.

### Confirmed decisions
| Decision | Choice |
| --- | --- |
| Session model | **No WordPress user accounts, no roles.** A signed selector/verifier cookie backed by a `sessions` table. Lighter, and members get zero `wp-admin` surface. |
| Identity | Login is **by email**, not by household. Any email that matches an active `sjioc_members` row (case-insensitive) gets in; the greeting uses the **primary** matching row (lowest `member_seq`). Household grouping was explicitly *not* wanted. |
| Storage | **3 dedicated tables** (not transients) — for the audit trail, session revocation, and clean isolation. Rate-limit counters stay in transients (throwaway). |
| Session length | **24 hours, hard.** No sliding renewal. "Remember me" only pre-fills the email field next visit (90-day non-auth cookie). |
| Greeting title | Mr. (male) / Mrs. (married or widowed female) / Ms. (single, divorced, or unknown female). |
| URLs | `/member-login/` and `/member-dashboard/` — plain WP Pages; the code resolves them by template so the admin can rename the slugs. |
| Isolation | Only **one line** added to shared code (`functions.php` require). Everything else is new self-contained files with their own asset version. `style.css` / `main.js` / `header.php` / `footer.php` untouched. |

### Files
| File | Role |
| --- | --- |
| `inc/member-auth.php` | Everything: schema install (on `admin_init` via a version check — no theme reactivation), audit log, directory lookup, sessions, send (link/OTP), verify, logout, rate limiting, dev delivery, asset + robots hooks. |
| `page-member-login.php` | Template `Member Login` — email step, "link sent" step, OTP entry step; all steps driven by query params. |
| `page-member-dashboard.php` | Template `Member Dashboard` — gated; "Welcome, Mr. …" + sign-out. |
| `assets/css/member.css` | Scoped styling, theme tokens. Loaded only on the two templates. |
| `assets/js/member.js` | Progressive enhancement only — injects the reCAPTCHA token, preserves the clicked method button, blocks double-submit. Form works without JS. |
| `functions.php` | +1 line: `require_once .../inc/member-auth.php`. No `SJIOC_VER` bump (member assets carry `SJIOC_MEMBER_ASSET_VER`). |

### Tables (`{prefix}` prefixed, `dbDelta`, all timestamps UTC)
- `sjioc_member_challenges` — pending magic-link / OTP: `member_id`, `email`, `kind`, `selector`, `verifier_hash`, `attempts`, `expires_at`, `consumed_at`, `ip`, `created_at`
- `sjioc_member_sessions` — active logins: `member_id`, `selector` (unique), `verifier_hash`, `issued_at`, `expires_at`, `last_seen`, `ip`, `user_agent`, `revoked_at`
- `sjioc_member_auth_log` — audit: `member_id`, `email`, `event`, `detail`, `ip`, `user_agent`, `created_at`

### Security — as implemented
| Area | Measure |
| --- | --- |
| Magic-link token | 256-bit CSPRNG (`random_bytes(32)`), split `selector.validator`; only `HMAC-SHA256(validator, salt)` stored; single-use (`consumed_at` set atomically); 15-min expiry; a newer send invalidates older unconsumed links of the same kind. |
| OTP | 6 digits (`random_int`, zero-padded); only `HMAC-SHA256(code, salt)` stored; **5** wrong attempts then the challenge is burned; 15-min expiry; selector carried in an `HttpOnly` 20-min cookie. |
| Session cookie | `selector.verifier`; only `HMAC-SHA256(verifier, salt)` stored. Name `__Host-sjioc_member` on HTTPS (falls back to `sjioc_member` on local HTTP). `HttpOnly` + `Secure` + `SameSite=Lax`. HTTPS detected via `is_ssl()` **or** `X-Forwarded-Proto` (Azure's TLS front end). 24-h server-checked expiry. `last_seen` refresh throttled to 10 min. |
| Signing key | `SJIOC_MEMBER_AUTH_SALT` (wp-config) if set, else `wp_salt('auth')`. |
| Constant-time | Every token/code/verifier comparison is `hash_equals()`. |
| Rate limiting | 3 sends / 15 min **per IP** and **per email** (transient counters). A blocked or unknown-email request returns the *same* "check your email" screen. |
| Enumeration | Uniform response + uniform work whether or not the email is a member; honeypot-triggered requests also return the neutral "sent" screen. |
| Bot defense | reCAPTCHA v3 (`member_auth` action, fails open per existing helper) + off-screen honeypot with a real `name`. |
| CSRF | WP nonce on every POST (`sjioc_member_send` / `_otp` / `_logout`) + `SameSite` cookie. |
| Exposure | `nocache_headers()` + `X-Robots-Tag: noindex, nofollow` + `wp_robots` noindex on both templates. |
| Audit | Every send / login / failure / lockout / logout written to `sjioc_member_auth_log` with IP + UA. Raw tokens, codes, and cookie values are **never** logged or echoed (except the dev-only local delivery, below). |
| Least privilege | Members are not WP users — no dashboard, no REST auth, no `wp-admin`. |

### Local / dev delivery
Container has no mail service, so when `WP_DEBUG` is on **or** the host is `localhost`/
`*.local`/`*.test`, the magic link and OTP code are written to the PHP error log
(`docker logs wordpress_app`) and shown on the login page **only to a logged-in WP
administrator**. On Azure (real host, no debug) this path is inert.

### Local test results (2026-09-06, WP 7.0.4, OrbStack)
Passed: link send → verify → dashboard greeting; OTP send → wrong code rejected →
correct code → dashboard; consumed link reuse rejected; logged-in→dashboard redirect;
unauth→login redirect; logout revokes session; honeypot → neutral; bad nonce → error;
non-member email → neutral; per-IP rate limit trips; `X-Robots-Tag` + no-store headers
present; no PHP notices/warnings. Tables auto-provisioned via the version check.

### Deployment (SFTP, no theme re-upload)
1. Upload the 6 files to `/site/wwwroot/wp-content/themes/sjioc-wp-theme/` (5 new + `functions.php`).
2. Load any `wp-admin` page once as an admin → the 3 tables auto-create.
3. Create two Pages: **Member Login** (template *Member Login*, slug `member-login`) and **Member Dashboard** (template *Member Dashboard*, slug `member-dashboard`).
4. Optional: add `define('SJIOC_MEMBER_AUTH_SALT', '<fresh 50+ char random>');` to `wp-config.php`.
5. Confirm the site sets `$_SERVER['HTTPS']`/forwards `X-Forwarded-Proto` (standard on Azure App Service) so the `Secure` / `__Host-` cookie engages — the code also checks `X-Forwarded-Proto` directly as a backstop.
6. If files don't take effect: **Stop + Start** the App Service (opcache), not Restart.
7. Members without an email on file can't log in until the office adds one (existing Members admin screen).

### Not yet done (next phases)
Google sign-in (custom OIDC) · member directory page · member documents CPT + protected
delivery · giving import + statements · passkeys. Login entry point is URL-only for now
(no nav link) by request.

---

## 1. Requirement (as confirmed)

| Question | Answer |
| --- | --- |
| What does login unlock? | (1) Member directory, (2) Giving history / statements, (3) Member-only content & documents |
| Who may log in? | **Directory members only** — email must already exist in `{prefix}sjioc_members` (`is_active = 1`). No self-registration. Unknown emails are rejected. |
| Sign-in methods wanted | Email magic link · Email OTP code · "Sign in with Google" — all three now. Passkey / biometric later (paired with a future mobile app). |
| Plugin policy | **No third-party auth plugins.** A plugin is acceptable *only if the plugin's author is the identity provider itself* (e.g. an official Google-authored plugin — which does not exist for WordPress). Everything here is therefore custom code we own and maintain. |

### Non-goals (this phase)
- Public self-service account creation
- Social providers other than Google
- Passkeys / WebAuthn (Phase 6, with the mobile app)
- A live payment/giving integration (see §7.3)

---

## 2. Relevant findings from the codebase

| Fact | Where | Consequence for this design |
| --- | --- | --- |
| No front-end login exists today | — | Greenfield; only `wp-admin` login is present. |
| Login hardening already in place | `inc/setup.php:59-78` | Per-IP brute-force throttle + username-enumeration blocks. We reuse the same **per-IP transient** pattern for the new flows. |
| `wp_mail()` is transparently routed through Microsoft Graph | `inc/setup.php:538-550` (`pre_wp_mail` filter) | Magic-link / OTP emails need **no new infrastructure** — a plain `wp_mail()` call goes out via Graph (SMTP fallback already wired). Email is effectively free. |
| Church directory table | `inc/members.php` — `{prefix}sjioc_members` | Columns: `cardex_no`, `member_seq`, `first_name`, `last_name`, `email`, `phone_number`, `city`, `is_active`, … Rows are **per person**, grouped into families by `cardex_no`. `email` is frequently **blank or shared across a family**. |
| Full Members admin UI already exists | `inc/admin.php:75-395` (list + search + add/edit, incl. `email` field) | Admins can already set/correct a member's email — no new admin screen needed just for that. |
| reCAPTCHA v3 helper | `inc/recaptcha.php` | Reuse on the login request form. |
| Azure App Service (IIS + PHP), classic theme, no Composer, no build step | `codebase.md` | No `.htaccess`; protected file delivery must use `web.config` **or** a PHP delivery endpoint. No Composer → any JWT verification is hand-rolled with PHP's `openssl_*`. |
| WordPress core has **no** native passwordless login | — | Justifies custom build. (WP *does* ship Application Passwords since 5.6 — useful for the future mobile app, not for browser login.) |

---

## 3. Architecture decision — real WordPress users

**Each parishioner who logs in gets a real `wp_users` row** assigned a locked-down custom role `sjioc_member`, linked back to the directory via user meta.

### Why real WP users (not a hand-rolled session)
- Battle-tested session handling: `wp_set_auth_cookie()`, cookie expiry, "remember me", `wp_logout()`, "log out everywhere" (session-token bump), HTTPS-only cookies.
- `is_user_logged_in()` / `current_user_can()` gate every member page and every member-scoped query with WordPress's own primitives.
- Nonces, REST auth, media ownership all work without special-casing.
- The future mobile app can authenticate against the same user store (Application Passwords / a token endpoint) instead of a parallel identity system.

### The `sjioc_member` role
- Capabilities: `read` only. **No** `edit_posts`, `upload_files`, `manage_options`, etc.
- `wp-admin` blocked: `admin_init` redirect to the member landing page for anyone who can't `edit_posts` (except `admin-ajax.php` / `admin-post.php`).
- `show_admin_bar(false)` for the role.
- Registered/updated on `after_switch_theme` (same hook as the table creators).

### User provisioning ("find or create")
On a successful auth (any method), we resolve the verified email to a directory record, then:
1. Look for an existing `wp_users` row by `sjioc_member_login_key` meta (see §4).
2. If none, `wp_insert_user()` with:
   - `user_login` = synthetic, never shown, never typed — e.g. `mem_<cardex>_<seq>` (or `mem_<cardex>` for household login).
   - `user_email` = the directory email.
   - `role` = `sjioc_member`.
   - `display_name` / `first_name` / `last_name` from the directory row.
   - `user_pass` = `wp_generate_password(64, true, true)` — random, discarded, never used.
3. Store link meta: `sjioc_member_login_key`, `sjioc_member_id` (or `sjioc_cardex_no`), `sjioc_login_method_last`.
4. Re-sync `display_name` / email from the directory on every login so admin edits propagate.

---

## 4. Identity model — the shared / blank email problem

The directory is family-based (`cardex_no` + `member_seq`); WordPress requires **`user_email` to be unique**. Families that share one email, or members with no email on file, force a choice:

### Option A — Household login (per `cardex_no`) — **recommended**
- Email → resolve to the family (`cardex_no`) → one `wp_user` **per household**.
- Directory, documents and **giving statements are shown for the whole family**, which is how parish giving statements are normally issued anyway.
- Handles shared emails natively; no data cleanup required to launch.
- If a specific email maps to more than one household (rare — e.g. an adult child on a separate cardex using a parent's email), we show a small "Which family is this?" picker (first names + city) after auth.
- Trade-off: no per-person attribution. Acceptable for all three launch features; revisit if per-person history is ever needed.

### Option B — Per-person login
- Requires every logging-in person to have a **unique** email in the directory.
- Cleaner identity, but pushes a real data-cleanup burden onto the church and blocks members whose family shares one inbox.

### Members with **no email on file**
Uniform message: *"We don't have an email address for your family on record. Please contact the church office."* — plus the office adds it in the existing Members admin screen. (No enumeration: this same generic wording is used whether or not the typed email exists — see §6.)

> **Decision required:** A vs B. Recommendation: **A (household)**.

---

## 5. Authentication methods

All three share one request surface: `/<base>/login` (base = `member` / `portal` / `account` — see Open Decisions).

### 5.1 Email magic link (custom)

**Request** (`POST` via `admin-post.php` or a REST route):
1. Form: email field + honeypot (`name` attribute — see `codebase.md` risk_zones) + nonce + reCAPTCHA v3.
2. Server: `sanitize_email()`, normalize (lowercase, trim).
3. Rate-limit check: ≤ 3 requests / 15 min **per email** and **per IP** (transient pattern from `inc/contact-form.php`).
4. Resolve email → active directory household(s).
5. **Always** respond with the same neutral page: *"If that email is on file, we've sent you a sign-in link and code. Check your inbox."* — regardless of match.
6. On match: `random_bytes(32)` → raw token. Store `hash('sha256', $raw)` + purpose + `cardex_no` + `email` + `expires_at` (now + 15 min) + requester IP + `attempts = 0` in `{prefix}sjioc_auth_tokens`.
7. `wp_mail()` the link: `https://<site>/<base>/verify?token=<raw>` (raw token only in the email, never stored/logged).

**Verify** (`GET /<base>/verify`):
1. `hash('sha256', $_GET['token'])` → look up row.
2. Reject if not found / expired / already used.
3. Mark `used_at = now` (single-use, atomic `UPDATE … WHERE used_at IS NULL`).
4. Resolve household → find-or-create `wp_user` (§3) → `wp_set_auth_cookie($uid, $remember)`.
5. Redirect to `redirect_to` (validated with `wp_validate_redirect()`, default = member landing).
6. Write an audit-log row (§8).

Cross-device is fine (token travels in the URL). Safety comes from short TTL + single use + a "new sign-in" notification email.

### 5.2 Email OTP code (custom)

Same request/rate-limit/enumeration rules. Instead of a link:
- Generate a **6-digit numeric** code, store `hash('sha256', $code)` alongside a `reference` id.
- Email the code; drop the `reference` in a short-lived, `HttpOnly` cookie (and a hidden field) so the verify form knows which pending code to check.
- Verify form: user types the 6 digits → server checks hash for that `reference`.
- ≤ 5 wrong attempts (`attempts` column) then the code is burned; same 15-min TTL.

Offered **on the same screen** as the magic link — the email contains both: *"Tap the button, or enter this code: 481 902."* OTP is the reliable path when a mail client rewrites/pre-fetches links or the member is reading email on a different device.

### 5.3 Sign in with Google (custom OpenID Connect — no plugin)

Google is the identity provider; we implement the standard Authorization Code + PKCE flow directly against Google's endpoints. Nothing third-party sits between us and Google.

**One-time setup (admin / infra):**
- Google Cloud project → *OAuth consent screen* (External, "in production", scopes `openid email profile`).
- *OAuth 2.0 Client ID* (type: Web application).
- Authorized redirect URI: `https://<site>/<base>/auth/google/callback`.
- Client ID + secret stored as **wp-config constants** `SJIOC_GOOGLE_CLIENT_ID` / `SJIOC_GOOGLE_CLIENT_SECRET` (same pattern as `SJIOC_AZURE_OAI_*`), with an admin-settings fallback. Secret never rendered in any page.

**Flow:**
1. `/<base>/login` → "Continue with Google" → server generates `state` (CSRF), `nonce`, PKCE `code_verifier` (all stashed in a short `HttpOnly` cookie / transient) → redirect to `https://accounts.google.com/o/oauth2/v2/auth?…`.
2. Google → `…/auth/google/callback?code=&state=`.
3. Verify `state`. Exchange `code` at `https://oauth2.googleapis.com/token` (server-to-server, only during this explicit action — not on page render, consistent with the Azure-cost rule).
4. Verify the returned `id_token` (RS256 JWT):
   - Signature against Google's JWKS (`https://www.googleapis.com/oauth2/v3/certs`, cached in a transient ~24 h) using `openssl_verify` — hand-rolled, ~40 lines, no Composer.
   - Claims: `iss` ∈ {`https://accounts.google.com`, `accounts.google.com`}, `aud` = our client ID, `exp` not passed, `nonce` matches.
   - Require `email_verified === true`.
5. `email` → resolve to directory household. **No match → reject** ("This Google account's email isn't on our parish rolls…"). Match → find-or-create `wp_user` → session.
6. Audit-log row.

### 5.4 Passkeys / WebAuthn — Phase 6 (deferred)
Added once §5.1–5.3 are stable and the mobile app work begins. Still needs a bootstrap method (one of the above) for first-device registration and new devices, so it is a convenience layer, never the sole method.

---

## 6. Anti-abuse & anti-enumeration

- **Uniform responses:** the login request always returns the same "check your email" screen; Google always returns the same generic rejection for any non-member email. No timing branch that leaks existence (resolve + hash even on the no-match path, or defer with a fixed floor).
- **Rate limits** (per-IP **and** per-email transients, mirroring `inc/contact-form.php` / `inc/chat.php`):
  - Login request: 3 / 15 min.
  - Verify attempts (OTP): 5 per code.
  - Google callback: 10 / 15 min per IP.
- **Tokens/codes:** CSPRNG, stored **hashed** (`sha256`), single-use, 15-min TTL, bound to the requesting `cardex_no`.
- **reCAPTCHA v3** on the request form (fails open if unconfigured — existing helper behavior).
- **Honeypot** field with a real `name` attribute on the request form.
- **"New sign-in" notification** email to the household address on every successful login (method + rough location/UA), giving members a tamper signal.
- **Session cookies** HTTPS-only (site already forces TLS).

---

## 7. The three gated features

### 7.1 Member Directory — data exists
- New template `page-member-directory.php` (members-only gate).
- Renders households from `{prefix}sjioc_members` grouped by `cardex_no`: names, city, and — for members who haven't opted out — phone / email with click-to-call / mailto.
- **Directory opt-out:** add `directory_optout TINYINT(1) NOT NULL DEFAULT 0` to `sjioc_members` (+ a checkbox on the existing member edit screen, and a self-service toggle on the member's own profile page). Opted-out members still appear by name but with contact details hidden.
- Search/filter client-side or with a single cached query (transient, respecting the +1-query budget).

### 7.2 Member-only documents — new CPT
- New CPT `sjioc_member_doc` (`inc/member-docs.php`): title, description (editor), category taxonomy, file attachment **or** external URL, optional publish/expire dates.
- Front-end `page-member-resources.php` — mirrors the existing public Resources page pattern (`inc/resources.php` / `page-resources.php`).
- **Protected file delivery:** uploaded files must not be reachable by guessable URL.
  - Store under `wp-content/uploads/sjioc-private/`.
  - Block direct web access with a `web.config` deny rule (Azure/IIS — `.htaccess` won't work here).
  - Serve through an authenticated PHP endpoint: check `is_user_logged_in()` + role → `readfile()` with correct `Content-Type` / `Content-Disposition` / no-cache headers.
- Admin manages under the existing **SJIOC** menu.

### 7.3 Giving history / statements — ⚠ no data source yet
There is currently **no donations data anywhere** in the site. `page-give.php` is Zelle / external links only; there is no giving table and no payment integration. This feature cannot be built until a data source is chosen:

| Option | How it works | Effort / cost | Notes |
| --- | --- | --- | --- |
| **A. CSV / Excel import (recommended for v1)** | Treasurer periodically exports contributions from the parish accounting system (QuickBooks / Servant Keeper / Breeze / …) and uploads via an admin import screen into a new `{prefix}sjioc_giving` table. Statements generated on demand (HTML → print/PDF) from those rows. | Low. Same import pattern as Members / Vehicles. No external calls, no recurring cost. | Data is only as fresh as the last upload. Fine for year-end / quarterly statements. |
| **B. Giving-provider API** (Tithe.ly, Pushpay, Vanco, Stripe) | Scheduled **admin-triggered** sync pulls transactions into the same table; front-end still reads only the local table. | Medium–high. External API, credentials, provider fees, ongoing maintenance. Live polling is disallowed by `CLAUDE.md` — must be a manual/admin sync. | Only worth it if the church already uses one of these for online giving. |
| **C. Defer** | Ship auth + directory + documents now; revisit giving later. | — | Removes the hardest unknown from the critical path. |

Giving records are **highly confidential** — household-scoped access only, dedicated audit logging on every view/download, never cached in a shared transient.

> **Decision required:** A, B, or C. Recommendation: **A** (or **C** to de-risk the initial launch).

---

## 8. Data model changes

| Object | Purpose |
| --- | --- |
| `wp_users` rows + `wp_usermeta` | One per logging-in household/person. Meta: `sjioc_member_login_key`, `sjioc_member_id` / `sjioc_cardex_no`, `sjioc_login_method_last`. |
| Role `sjioc_member` | Locked-down front-end-only role (§3). |
| `{prefix}sjioc_auth_tokens` | `id`, `token_hash`, `purpose` ENUM(`magic`,`otp`), `reference`, `cardex_no`, `email`, `attempts`, `expires_at`, `used_at`, `ip`, `created_at`. Garbage-collected on write (`DELETE … WHERE expires_at < NOW() - INTERVAL 1 DAY`). |
| `{prefix}sjioc_auth_log` | `id`, `event` (`request`,`login`,`fail`,`logout`,`doc_view`,`giving_view`), `method`, `email`, `cardex_no`, `ip`, `ua`, `created_at`. Audit trail; admin-viewable, retention-capped. |
| `sjioc_members.directory_optout` | New `TINYINT(1)` column (§7.1). |
| `{prefix}sjioc_giving` | Only if §7.3 Option A/B is chosen. |
| Options | `sjioc_member_login_enabled`, `sjioc_member_session_days`, `sjioc_google_client_id` / `_secret` (fallback to constants), `sjioc_member_url_base`. |

All new tables via `dbDelta()` on `after_switch_theme`, `sjioc_` prefixed, following `inc/members.php`.

---

## 9. Azure cost / performance

- Login, verify, OTP, and the Google handshake are all discrete `POST`/redirect actions — **never on general page render**. Compliant with `CLAUDE.md` perf rules.
- Member-area pages add queries only for the small logged-in audience; still held to **+1 query per page**, directory reads cached in a transient.
- Google JWKS fetched at most once / 24 h, only during a Google login.
- Emails ride the existing Graph `wp_mail()` route — **zero new external dependency**.
- **No cron, no polling.** Token GC piggybacks on token-table writes; any giving sync is admin-triggered only.

---

## 10. UX & entry points

- **Dedicated page** `page-member-login.php` at `/<base>/login`, styled to the theme (Playfair / Cormorant / Lato, gold accents, same card treatment as Hall Rental / Contact forms). Full page, not a modal — simpler, linkable, better on mobile.
- **Header link:** "Member Login" in the primary nav (or the utility area) → becomes "My Account" once logged in.
- **After login:** `/<base>/` landing = a simple dashboard with cards → *Parish Directory*, *Documents*, *Giving* (if built), *My Profile* (contact info + directory opt-out toggle), *Log out* / *Log out everywhere*.
- **Logout:** standard `wp_logout()`; "log out everywhere" bumps the user's session tokens.
- **Pre-launch:** email the parish a heads-up so the first magic-link email isn't mistaken for phishing (from the known parish address, describing exactly what the email will look like).

---

## 11. Phased rollout

| Phase | Deliverable |
| --- | --- |
| **0 — Foundations** | `sjioc_member` role, wp-admin block, identity model (§4 decision), `sjioc_auth_tokens` + `sjioc_auth_log` tables, user provisioning, admin on/off toggle. |
| **1 — Email magic link + OTP (MVP)** | `/<base>/login`, `/<base>/verify`, rate limits, reCAPTCHA, honeypot, audit log, member landing page, logout, "new sign-in" email. |
| **2 — Google Sign-In** | OIDC flow, JWKS verifier, admin settings for client ID/secret. |
| **3 — Member Directory** | `page-member-directory.php` + `directory_optout` column + admin/self toggles. |
| **4 — Member Documents** | `sjioc_member_doc` CPT + `page-member-resources.php` + protected delivery endpoint + `web.config` rule. |
| **5 — Giving** | Per §7.3 decision — CSV import + `sjioc_giving` table + statement generator. |
| **6 — Passkeys / mobile** | WebAuthn registration + login; token endpoint / Application Passwords for the app. |

---

## 12. Files (estimate)

**New**
- `inc/member-auth.php` — role, provisioning, magic link + OTP, sessions, rate limits, audit log
- `inc/member-auth-google.php` — OIDC flow + JWKS verifier *(Phase 2)*
- `inc/member-portal.php` — URL routing, page gating, member dashboard
- `inc/member-directory.php` *(Phase 3)*
- `inc/member-docs.php` — CPT + protected file delivery *(Phase 4)*
- `inc/giving.php` — table, import, statement generator *(Phase 5)*
- `page-member-login.php`, `page-member.php`, `page-member-directory.php`, `page-member-resources.php`, `page-member-giving.php`
- `web.config` (or an edit to an existing one) — deny direct access to `uploads/sjioc-private/`

**Changed**
- `functions.php` — `require_once` the new modules, `SJIOC_VER` bump
- `style.css` — login form + member area styling
- `assets/js/main.js` — login/OTP form interactions
- `header.php` — "Member Login" / "My Account" nav link
- `inc/admin.php` — member edit screen: directory opt-out checkbox; new "Member Access" submenu (toggle, audit-log viewer, Google settings)

---

## 13. Security checklist (per CLAUDE.md — every item mandatory before ship)

- [ ] `defined('ABSPATH') || exit;` on every new file
- [ ] Nonce on every form and every AJAX/`admin-post` handler
- [ ] `current_user_can()` on all admin actions **and** every member-scoped data read
- [ ] `$wpdb->prepare()` for every query touching user input
- [ ] `wp_unslash()` before `sanitize_*()` on all raw `$_POST` (repeat offender — see `codebase.md`)
- [ ] Escape all output (`esc_html` / `esc_attr` / `esc_url`)
- [ ] Honeypot (with `name` attribute) on the public login form
- [ ] reCAPTCHA v3 on the login request
- [ ] Rate limiting per IP **and** per email
- [ ] Tokens/codes: CSPRNG, hashed at rest, single-use, 15-min TTL
- [ ] No account enumeration — uniform responses & timing
- [ ] Google: verify `state`, `nonce`, `aud`, `iss`, `exp`, signature, `email_verified`
- [ ] Protected documents served only via an authenticated PHP endpoint
- [ ] Audit log for auth events + every giving-data access
- [ ] `sjioc_member` role cannot reach `wp-admin`; admin bar hidden
- [ ] Auth cookies HTTPS-only
- [ ] Google client secret never rendered; stored as a constant/option, not in theme files

---

## 14. Open decisions (blocking build)

1. **Identity model:** household (per-`cardex_no`) vs per-person login. → *rec: household.*
2. **Giving data source:** CSV import (A) / provider API (B) / defer (C). → *rec: A, or C to de-risk launch.*
3. **Member documents:** new `sjioc_member_doc` CPT (rec) vs a "members only" toggle on existing Pages/CPTs.
4. **Directory opt-out** for members who don't want contact details shown. → *rec: yes.*
5. **URL base:** `/member/…` vs `/portal/…` vs `/account/…`.
6. **Session length:** default 14 days + "log out everywhere"? Or longer for convenience?
7. **Google client secret storage:** wp-config constant (rec, matches `SJIOC_AZURE_OAI_*`) vs admin field.
8. **Post-login landing:** confirm the dashboard card set (Directory / Documents / Giving / My Profile / Log out).
9. **Pre-launch parishioner email** to pre-empt phishing concerns — who sends, what it says.
10. **Scope of Phase 1 launch:** ship auth + directory first, or hold until documents are also ready?
