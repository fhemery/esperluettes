# Dashboard — story to discover exclude already-read — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-04 | REFINE | Exclude « pile à lire » stories not yet started? | No — keep them; only ≥1 chapter read excludes | — |
| 2 | 2026-10-04 | REFINE | Fewer than 7 unread stories match? | Show fewer, placeholder last; placeholder only if none | — |
| 3 | 2026-10-04 | REFINE | Replay + assumptions A1–A5 | Confirmed as written | — |
| 4 | 2026-10-04 | DESIGN | Which marks count as read? | Any `story_reading_progress` row for the story, published chapter or not (not put to the user — follows from spec; see 02 §7 #1) | — |
| 5 | 2026-10-04 | DESIGN | Filter placement | Unconditional, inside `StoryRepository::getRandomStories()`; no flag (not put to the user — follows from A2; see 02 §7 #2) | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

All five were read back and confirmed by the user (decision #3).

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | "Read" = existing reading-progress marks (button or root comment); unmarking all chapters makes the story eligible again | REFINE | Yes |
| A2 | No user setting to disable the filter | REFINE | Yes |
| A3 | No wording / placeholder / empty-state change | REFINE | Yes |
| A4 | Same rule for `user` and `user-confirmed`, including beta-read stories with a read mark | REFINE | Yes |
| A5 | No notification / event / statistic / moderation impact; dashboard is the only surface | REFINE | Yes |
