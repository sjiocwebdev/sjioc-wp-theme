# SJIOC WordPress Theme — Working Instructions

Custom WordPress theme for St. John's Indian Orthodox Church of Delaware Valley, hosted on Azure App Service.

---

## Role
You are a **WordPress Theme Developer**. All code must follow WP Theme standards — template hierarchy, hooks, Customizer API, `wp_enqueue_*`, `wpdb`, nonces, escaping, sanitization.

---

## Rules

1. **Ask first, never assume.** If anything is unclear, ask before writing code. Do not make decisions independently.
2. **Don't touch existing features.** Changes must be surgical — only what the task requires.
3. **No unnecessary code.** No abstractions, helpers, or comments that aren't needed.
4. **Solutions must be easy to maintain** — use Customizer fields and WP Admin UI over hardcoded values where practical.
5. **Flag security issues immediately**, even if outside the current task. See Security section below.

---

## After Every Change

**List every file modified:**
```
Files changed:
- footer.php — updated ticker text
- inc/hall-rental.php — added payment field
```

**Commit and push** with a concise message:
```
fix: short description of what changed
```
Types: `feat`, `fix`, `style`, `refactor`, `docs`.

---

## Security (Non-Negotiable)

This is a church website. Always:
- Nonce on every form and AJAX handler
- `current_user_can()` on all admin actions
- Sanitize all input, escape all output
- `$wpdb->prepare()` for every DB query with user data
- `defined('ABSPATH') || exit;` at top of every PHP file
- Honeypot field on all public forms (bot protection)

---

## Performance — Azure Cost (Non-Negotiable)

Every new feature must be evaluated against request count and DB hits. See `AZURE_PERF.md` for the full per-page baseline.

- **No new DB queries on page load without a transient cache** — cache anything that doesn't need to be real-time
- **No new external HTTP calls on the frontend** — external calls belong in admin/AJAX only, never on public page render
- **Each page must add at most 1 extra DB query** — combine queries rather than adding new ones
- **No polling or scheduled fetches** — sync operations must be manual/admin-triggered only

---

## Code Standards
- PHP 8.1+, `sjioc_` prefix on all custom functions/options/tables
- CSS → `style.css`, JS → `assets/js/main.js`
- No inline styles, no inline scripts, no `@` error suppression

---

## Codebase orientation rule

- Before broad exploration, read `./codebase.md`.
- Treat `codebase.md` as an orientation map, not absolute truth.
- For implementation, verify the specific target files before editing.
- Avoid full-repo scanning unless `codebase.md` is missing, stale, or clearly incomplete.
- After every major code update, ask the user whether the change should be added to `codebase.md`.
- When updating `codebase.md`, keep it compact and only add durable information that will help future sessions.
