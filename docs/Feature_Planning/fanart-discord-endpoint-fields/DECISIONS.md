# Extend fanart Discord endpoint payload — decisions log

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Mode | auto (user ran the six tasks sequentially, available for questions) | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | « Profile ID » = user id (no separate profile id in the public contract) | REFINE | yes |
| 2 | Fanart title already always present: nothing added | REFINE | yes |
| 3 | Description sent as plain text, omitted when empty | REFINE | yes |
| 4 | Nested keys `artist: {id, slug, avatarUrl}` and `authors: [{id, name, slug}]`; `chapterSlug` omitted without chapter | DESIGN | yes |
| 5 | Branch: stay on `fix/misc-small-fixes` (user instruction) | REFINE | yes |
