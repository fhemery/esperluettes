# Fanart submission: private message to the author — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-23 | REFINE | Where can the author read the message? | Notification only (not stored, not in the review viewer) | — |
| 2 | 2026-10-23 | REFINE | Optional or required, and length? | Optional, 500 characters | — |
| 3 | 2026-10-23 | REFINE | Field on authors' own artwork uploads? | No, submissions only | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | All co-authors receive the message (as they receive the notification) — vetoable replay, user raised no objection | REFINE | Yes |
| 2 | No moderation/report path for the message | REFINE | Yes |
| 3 | The notification stays after decline or withdrawal | REFINE | Yes |
