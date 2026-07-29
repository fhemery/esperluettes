# Discord link hint — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Use a generic `warningCallback` on `NotificationChannelDefinition` rather than hard-coding a Discord check in the Notification view — keeps domains decoupled | DESIGN | Yes |
| 2 | Warning is informational only — toggles still work even when unlinked | REFINE | Yes |
| 3 | Single phase — too small to split | PLAN | N/A |
