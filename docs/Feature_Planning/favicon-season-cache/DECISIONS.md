# Favicon stays the same when season changed — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-08 | REFINE | How to bust favicon cache on season change? | Use query param `?theme=<season>` (from request) | — |
| 2 | 2026-08-08 | DESIGN | Where to put the season query? | Append `?theme={{ $theme->value }}` in Blade outside `Theme::asset()` | — |
| 3 | 2026-08-08 | DESIGN | Keep static `?v=20260425`? | No — replace with `?theme=` only | — |
| 4 | 2026-08-08 | DESIGN | Scope of asset URLs? | Favicon `<link>`s only | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Replace the hardcoded `?v=20260425` entirely with `?theme=<season>` (do not keep both) | REFINE | Yes |
| 2 | Only favicon `<link>`s in the head — not logos / CSS theme backgrounds | REFINE | Yes |
| 3 | No change to root `public/favicon.ico` copies (browsers that ignore HTML links may still show autumn) | REFINE | Yes — may need follow-up if that path still sticks |
| 4 | Page reload is enough; no JS to force favicon refresh mid-session | REFINE | Yes |
| 5 | All existing favicon sizes (ico, 16/32/48/180) get the same query param | REFINE | Yes |
| 6 | DESIGN picks: Blade append outside `asset()`, Shared-only, feature test on HTML hrefs | DESIGN | Yes |
