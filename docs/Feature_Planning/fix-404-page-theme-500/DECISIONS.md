# Fix 500 on 404 page when a model-bound route misses — decisions log

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Mode | auto (user ran the six tasks sequentially, available for questions) | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | 419 and 500 pages are fixed by the same change (same root cause) | REFINE | yes |
| 2 | Fix by a fallback view composer in SharedServiceProvider rather than reordering middleware | DESIGN | yes |
| 3 | Branch: stay on `fix/misc-small-fixes` (user instruction) | REFINE | yes |
| 4 | Composer sets `theme` per view (`$view->with`), not `View::share`, so a guest-resolved theme never leaks into later renders (plan said share) | BUILD | yes |
