# Fanart — lazy fragments inject a full page on redirect — decisions log

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Mode | auto (user ran the six tasks sequentially, available for questions) | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Navigate to the redirect destination rather than show an error | REFINE | yes |
| 2 | The chapter-group site, which injects any body today, also ignores non-OK answers | REFINE | yes |
| 3 | Shared helper `fetchFragment` on `window`; only the four Fanart sites adopt it, other domains reported | DESIGN | yes |
| 4 | The middleware's « intended = fragment URL » defect is out of scope and reported to the user | REFINE | yes |
| 5 | Branch: stay on `fix/misc-small-fixes` (user instruction) | REFINE | yes |
