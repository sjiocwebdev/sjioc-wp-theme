# Codebase Map — Token Savings Ledger

Append-only. One row per create/refresh/compress run. Never edit past rows.

| run | date | action | codebase_map_tokens | update_tokens | total_map_cost_tokens | baseline_orientation_tokens | estimated_tokens_saved_next_session | savings_% | estimated_$saved_next_session |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-07-06 | create | 1300 | 0 | 1300 | 20000 | 18700 | 93.5% | $X — see /cost |
| 2 | 2026-07-07 | update-recent-changes | 1414 | 450 | 1864 | 20000 | 18586 | 92.9% | $X — see /cost |

<!--
Column definitions:
- codebase_map_tokens: measured size of codebase.md after this run (chars/4)
- update_tokens: tokens spent producing THIS run's edits + PREVIOUS run's report output (fold prior report cost forward). Run 1 = 0 by convention.
- total_map_cost_tokens = codebase_map_tokens + update_tokens
- baseline_orientation_tokens: estimated cold-exploration cost with no map (<50 tracked source files -> 20k). Keep consistent run-over-run.
- estimated_tokens_saved_next_session = baseline_orientation_tokens - codebase_map_tokens
- savings_% = estimated_tokens_saved_next_session / baseline_orientation_tokens * 100
- estimated_$saved_next_session: run /cost or /usage for exact figures.
-->

## Savings trend (savings_% per run)

<!-- One bar per run once 2+ rows exist. Scale: 50 chars = 100%. -->
```
run1 | ██████████████████████████████████████████████░░░░ 93.5%
run2 | ██████████████████████████████████████████████░░░░ 92.9%
```