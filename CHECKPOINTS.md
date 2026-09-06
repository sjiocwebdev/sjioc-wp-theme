# Checkpoints

Known-good commits to revert to if a later change breaks something.
Each has a matching annotated git tag (`git tag -n`, pushed to `origin`).

## How to revert

```bash
# See what changed since a checkpoint
git log --oneline checkpoint-2026-09-05..HEAD
git diff checkpoint-2026-09-05..HEAD

# Option A — undo everything since the checkpoint, keep history (safe, recommended)
git revert --no-commit checkpoint-2026-09-05..HEAD
git commit -m "revert: roll back to checkpoint-2026-09-05"
git push origin main

# Option B — hard reset main to the checkpoint (rewrites history — only if nothing
# built on top matters and you accept a force-push)
git reset --hard checkpoint-2026-09-05
git push --force-with-lease origin main

# Just inspect the old code without changing main
git switch --detach checkpoint-2026-09-05
```

To deploy a checkpoint: `git archive --format=zip -o sjioc-theme-checkpoint-2026-09-05.zip checkpoint-2026-09-05` and upload that zip in WP Admin → Themes.

---

## Checkpoint log

| Date | Tag | Commit | State |
| --- | --- | --- | --- |
| 2026-09-05 | `checkpoint-2026-09-05` | `1b21b96` | Baseline **before Member Login work begins.** |

### `checkpoint-2026-09-05` — `1b21b96`

Baseline snapshot taken before starting the passwordless Member Login feature
(see `MEMBER_LOGIN_DESIGN.md`). This is the last state with **no login/auth code**.

Included since the previous deploy:
- Home page: Latest Event Video row (`sjioc_latest_video_url` Customizer field)
- Resources page + `sjioc_resource` CPT (auto-favicon cards)
- Weekly Bible Verse (CSV upload, rides the Celebrations cron)
- Vicar History timeline as a CPT with photo uploads (`sjioc_vicar_history`)
- News page + `sjioc_news` CPT
- Expanded chat handling (`inc/chat.php`)
- About Us hub, Leadership/Committees/Our History pages, Outreach CPT
- Admin-configurable contact-form subjects + routing; Graph mail Cc support
- Security sweep: honeypot `name` fixes, `wp_unslash()` swept across ~10 handlers

Verification status: working tree clean, all commits pushed to `origin/main`.
Not functionally re-tested locally this session (no local PHP/Docker available) —
treat as good based on it being the current live-intended `main`. Confirm visually
on the site before relying on it as a rollback target.
