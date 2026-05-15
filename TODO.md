# SJIOC Theme — TODO & Feature Backlog

---

## 1. Move All Secrets to Azure App Service Environment Variables

**Why:** Keys stored in `wp_options` (DB) or `wp-config.php` (filesystem) are exposed if either is compromised.
Azure App Service Application Settings are encrypted at rest and never touch the filesystem or DB.

**Code change needed:** Yes — for reCAPTCHA only (currently has no `defined()` fallback).
All others already support constants with `wp_options` fallback — just needs the env var wired in.

### Keys to migrate

| Key | Currently stored in | Constant name | Code change needed |
|---|---|---|---|
| Azure OpenAI Endpoint | `wp-config.php` (constant) | `SJIOC_AZURE_OAI_ENDPOINT` | No — already a constant |
| Azure OpenAI API Key | `wp-config.php` (constant) | `SJIOC_AZURE_OAI_KEY` | No — already a constant |
| Azure OpenAI Deployment | `wp-config.php` (constant) | `SJIOC_AZURE_OAI_DEPLOY` | No — already a constant |
| Azure Tenant ID (OneDrive) | `wp_options` + constant fallback | `SJIOC_AZURE_TENANT_ID` | No — already has `defined()` fallback |
| Azure Client ID (OneDrive) | `wp_options` + constant fallback | `SJIOC_AZURE_CLIENT_ID` | No — already has `defined()` fallback |
| Azure Client Secret (OneDrive) | `wp_options` + constant fallback | `SJIOC_AZURE_CLIENT_SECRET` | No — already has `defined()` fallback |
| SMTP Host | `wp_options` + constant fallback | `SJIOC_SMTP_HOST` | No — already has `defined()` fallback |
| SMTP Username | `wp_options` + constant fallback | `SJIOC_SMTP_USER` | No — already has `defined()` fallback |
| SMTP Password | `wp_options` + constant fallback | `SJIOC_SMTP_PASS` | No — already has `defined()` fallback |
| SMTP Port | `wp_options` + constant fallback | `SJIOC_SMTP_PORT` | No — already has `defined()` fallback |
| SMTP From address | `wp_options` + constant fallback | `SJIOC_SMTP_FROM` | No — already has `defined()` fallback |
| Google Calendar API Key | `wp_options` + constant fallback | `SJIOC_GCAL_KEY` | No — already has `defined()` fallback |
| Google Calendar ID | `wp_options` + constant fallback | `SJIOC_GCAL_ID` | No — already has `defined()` fallback |
| Outlook / ICS Feed URL | `wp_options` + constant fallback | `SJIOC_GCAL_ICS` | No — already has `defined()` fallback |
| reCAPTCHA Site Key | `wp_options` only (`sjioc_get()`) | — | **Yes — add `defined()` fallback** |
| reCAPTCHA Secret Key | `wp_options` only (`sjioc_get()`) | — | **Yes — add `defined()` fallback** |

### How to add in Azure Portal

1. Go to **Azure Portal → App Service → your app → Settings → Environment Variables**
2. Add each key as an Application Setting (Name = constant name, Value = the secret)
3. In `wp-config.php`, read them with `getenv()`:

```php
define('SJIOC_AZURE_OAI_KEY',        getenv('SJIOC_AZURE_OAI_KEY'));
define('SJIOC_AZURE_OAI_ENDPOINT',   getenv('SJIOC_AZURE_OAI_ENDPOINT'));
define('SJIOC_AZURE_OAI_DEPLOY',     getenv('SJIOC_AZURE_OAI_DEPLOY'));
define('SJIOC_AZURE_TENANT_ID',      getenv('SJIOC_AZURE_TENANT_ID'));
define('SJIOC_AZURE_CLIENT_ID',      getenv('SJIOC_AZURE_CLIENT_ID'));
define('SJIOC_AZURE_CLIENT_SECRET',  getenv('SJIOC_AZURE_CLIENT_SECRET'));
define('SJIOC_SMTP_HOST',            getenv('SJIOC_SMTP_HOST'));
define('SJIOC_SMTP_USER',            getenv('SJIOC_SMTP_USER'));
define('SJIOC_SMTP_PASS',            getenv('SJIOC_SMTP_PASS'));
define('SJIOC_SMTP_PORT',            getenv('SJIOC_SMTP_PORT') ?: 587);
define('SJIOC_SMTP_FROM',            getenv('SJIOC_SMTP_FROM'));
define('SJIOC_GCAL_KEY',             getenv('SJIOC_GCAL_KEY'));
define('SJIOC_GCAL_ID',              getenv('SJIOC_GCAL_ID'));
define('SJIOC_GCAL_ICS',             getenv('SJIOC_GCAL_ICS'));
define('SJIOC_RECAPTCHA_SITE_KEY',   getenv('SJIOC_RECAPTCHA_SITE_KEY'));
define('SJIOC_RECAPTCHA_SECRET_KEY', getenv('SJIOC_RECAPTCHA_SECRET_KEY'));
```

4. Once env vars are confirmed working in Azure, remove the values from `wp_options` via WP Admin
   (the admin UI fields can stay as overrides for local dev — they're just ignored when constants are set)

### Code change required — reCAPTCHA (`inc/recaptcha.php`)

```php
// Before (DB only):
function sjioc_recaptcha_site_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_site_key', '');
}
function sjioc_recaptcha_secret_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_secret_key', '');
}

// After (constant preferred, DB fallback):
function sjioc_recaptcha_site_key(): string {
    return defined('SJIOC_RECAPTCHA_SITE_KEY') ? SJIOC_RECAPTCHA_SITE_KEY
        : (string) sjioc_get('sjioc_recaptcha_site_key', '');
}
function sjioc_recaptcha_secret_key(): string {
    return defined('SJIOC_RECAPTCHA_SECRET_KEY') ? SJIOC_RECAPTCHA_SECRET_KEY
        : (string) sjioc_get('sjioc_recaptcha_secret_key', '');
}
```

---

## 2. ICS Download Endpoint — Rate Limiting

**File:** `inc/events.php` — `sjioc_calendar_ics_endpoint()`

**Why:** `/wp-json/sjioc/v1/calendar.ics` is publicly accessible with no request cap.
The `Cache-Control: public, max-age=1800` header already protects against legitimate
calendar app re-syncing. This rate limit is a backstop against scripted abuse.

**Approach:** IP transient — same pattern as the chat endpoint.
Allow 10 requests per IP per 30 minutes (generous for any legitimate use case).

**DB cost:** 1 read + 1 write per request (transient in `wp_options`).
Acceptable at this traffic level. Upgrade path: Redis object cache if needed.

**Code change:** Add to `sjioc_calendar_ics_endpoint()` before generating ICS:

```php
$ip_key = 'sjioc_rl_ics_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$hits   = (int) get_transient($ip_key);
if ($hits >= 10) {
    status_header(429);
    header('Retry-After: 1800');
    exit('Too many requests. Please try again later.');
}
set_transient($ip_key, $hits + 1, 1800);
```

---

## 3. Events REST Endpoint — Transient Cache

**File:** `inc/events.php` — `sjioc_get_db_events()`

**Why:** Every Events page visit fires two PHP bootstraps and two DB queries (page load + JS REST call).
Caching the REST response eliminates the DB hit on the second call for 30 minutes.

**Approach:** WP transient keyed by `months` param. Invalidate on any event add/edit/delete/sync.

**Code change:** Wrap `sjioc_get_db_events()` with transient get/set, and call
`delete_transient('sjioc_events_cache_6')` wherever events are written to the DB.

---

## 4. Bot Protection — Gaps & Improvements

**Current protection stack (per form):**

| Form | Nonce | Honeypot | reCAPTCHA v3 |
|---|---|---|---|
| Contact | ✅ | ✅ `cf_hp` | ✅ |
| Hall Rental | ✅ | ✅ `rf_hp` | ✅ |
| New-to-church | ✅ | ❌ Missing | ✅ |

### Task 4a — Add honeypot to new-to-church form

**Files:** `front-page.php` (HTML field) + `inc/new-to-church.php` (server-side check)

Add a hidden input (same pattern as contact/hall rental):
```html
<!-- front-page.php — inside the NTC form -->
<div style="display:none" aria-hidden="true">
  <input type="text" name="ntc_hp" id="ntc_hp" tabindex="-1" autocomplete="off">
</div>
```
```php
// inc/new-to-church.php — first check after nonce
if (!empty($_POST['ntc_hp'])) wp_send_json_error('invalid');
```

---

### Task 4b — reCAPTCHA: add `defined()` fallback in `inc/recaptcha.php`

Keys currently read from Customizer (`wp_options`) only. No constant support.
Needed before moving keys to Azure App Service env vars (TODO item 1).

```php
// Before:
function sjioc_recaptcha_site_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_site_key', '');
}
function sjioc_recaptcha_secret_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_secret_key', '');
}

// After:
function sjioc_recaptcha_site_key(): string {
    return defined('SJIOC_RECAPTCHA_SITE_KEY') ? SJIOC_RECAPTCHA_SITE_KEY
        : (string) sjioc_get('sjioc_recaptcha_site_key', '');
}
function sjioc_recaptcha_secret_key(): string {
    return defined('SJIOC_RECAPTCHA_SECRET_KEY') ? SJIOC_RECAPTCHA_SECRET_KEY
        : (string) sjioc_get('sjioc_recaptcha_secret_key', '');
}
```

---

### Task 4c — reCAPTCHA: admin status indicator

Keys live in **Customizer → reCAPTCHA section** (`inc/setup.php:94`).
No admin-facing indicator that reCAPTCHA is active or not.

Add a status notice at the top of the Customizer reCAPTCHA section (or as a WP Admin Dashboard widget) showing:
- 🟢 **Active** — both keys configured, reCAPTCHA is protecting forms
- 🔴 **Not configured** — forms are unprotected (reCAPTCHA fails open — bots can submit)
- ⚙️ **Via environment variable** — keys set via constant, Customizer fields ignored

**File:** `inc/setup.php` — add a `customize_controls_print_styles` or `customize_controls_enqueue_scripts` hook, or add a static notice in the Customizer section description.

---

---

## 5. Email — Replace SMTP with Microsoft Graph API

**Problem:** noreply Microsoft 365 user requires MFA and app passwords — cannot be configured cleanly.

**Solution:** Use the existing Azure AD app registration (already used for OneDrive) to send mail via
the Microsoft Graph API. No SMTP, no user account, no MFA, no app passwords.

### Prerequisites (Azure Portal — one-time)

1. Go to **Azure Portal → Azure Active Directory → App registrations → your existing SJIOC app**
2. Go to **API permissions → Add a permission → Microsoft Graph → Application permissions**
3. Add `Mail.Send` → click **Grant admin consent**
4. Create a **shared mailbox** in Microsoft 365 admin: `noreply@yourdomain.com`
   - Shared mailboxes have no license cost and no sign-in account
   - Set auto-decline on incoming mail so nothing lands in the inbox

### Task 5a — Code change: replace SMTP with Graph API in `inc/setup.php`

**File:** `inc/setup.php` — replace the `phpmailer_init` / SMTP hook with a `wp_mail` filter
that posts to the Graph API endpoint.

Graph API call:
```
POST https://graph.microsoft.com/v1.0/users/noreply@yourdomain.com/sendMail
Authorization: Bearer {access_token}
Content-Type: application/json
```

Access token is fetched via client credentials flow (same tenant/client/secret already in env vars):
```
POST https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token
grant_type=client_credentials
scope=https://graph.microsoft.com/.default
```

Token should be cached in a WP transient (expires in ~60 minutes) to avoid fetching on every email.

**New constant needed in `wp-config.php` / Azure App Settings:**
```php
define('SJIOC_MAIL_FROM', getenv('SJIOC_MAIL_FROM')); // noreply@yourdomain.com
```

All other credentials (`SJIOC_AZURE_TENANT_ID`, `SJIOC_AZURE_CLIENT_ID`, `SJIOC_AZURE_CLIENT_SECRET`)
are already in the app — no new secrets needed.

---

### Task 5b — Hardening: restrict app to noreply mailbox only (optional but recommended)

**Why:** Without this, the Azure AD app with `Mail.Send` can send as any user in the tenant.
If credentials are ever leaked, an attacker could impersonate any church member.

**How:** Run these PowerShell commands (works on Mac via `pwsh`):

```bash
# Install PowerShell on Mac (once)
brew install --cask powershell
pwsh
```

```powershell
# Inside pwsh:

# Install Exchange Online module (once)
Install-Module -Name ExchangeOnlineManagement

# Connect (requires Microsoft 365 admin account)
Connect-ExchangeOnline -UserPrincipalName admin@yourdomain.com

# Lock the app to only send as noreply@yourdomain.com
New-ApplicationAccessPolicy `
  -AppId "your-azure-client-id" `
  -PolicyScopeGroupId "noreply@yourdomain.com" `
  -AccessRight RestrictAccess `
  -Description "Restrict SJIOC app to send only as noreply"

# Verify it worked
Test-ApplicationAccessPolicy `
  -Identity "noreply@yourdomain.com" `
  -AppId "your-azure-client-id"
# Expected: AccessCheckResult = Granted

Test-ApplicationAccessPolicy `
  -Identity "vicar@yourdomain.com" `
  -AppId "your-azure-client-id"
# Expected: AccessCheckResult = Denied
```

Do this **after** confirming email works (Task 5a), not before.

---

## Priority Order

| # | Task | Effort | Impact |
|---|---|---|---|
| 1 | Move secrets to Azure App Service env vars | Low (Azure Portal only) | 🔴 High — security |
| 2 | reCAPTCHA `defined()` fallback (`inc/recaptcha.php`) | Tiny — 4 lines | 🔴 High — completes item 1 |
| 3 | Honeypot on new-to-church form | Tiny — 2 lines each file | 🔴 High — CLAUDE.md requirement |
| 4 | Email — Graph API setup (Azure Portal config) | Low — Azure Portal only | 🔴 High — unblock email |
| 5 | Email — Graph API code change (`inc/setup.php`) | Medium — replace SMTP hook | 🔴 High — unblock email |
| 6 | reCAPTCHA admin status indicator | Small — Customizer notice | 🟡 Medium — admin visibility |
| 7 | ICS rate limiting | Small — ~6 lines | 🟡 Medium — abuse protection |
| 8 | Events REST transient cache | Medium — ~15 lines + hooks | 🟢 Low now, high at scale |
| 9 | Email — hardening (ApplicationAccessPolicy) | Small — 3 PowerShell commands | 🟡 Medium — security hardening |
