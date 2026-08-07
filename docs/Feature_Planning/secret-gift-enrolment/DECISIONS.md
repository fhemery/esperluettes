# Secret Gift — enrolment — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-07 | REFINE | Does this task wait for/merge with `calendar-subscription/`? | Merge. Only Secret Gift needs mandatory enrolment today (Jardino/Quote Contest are open, collaborative-stories won't need it in v1) — a generic base-Calendar mechanism would serve one consumer and guess at a shape. Enrolment, cap and participant list are built as Secret-Gift-owned, on its existing `calendar_secret_gift_participants` table. `calendar-subscription/` closed as absorbed. | — |
| 2 | 2026-08-07 | REFINE | Enrolment/un-enrolment window? | Open during `preview`, closes at `registration_ends_at` or when the admin shuffles, whichever first. | — |
| 3 | 2026-08-07 | REFINE | Is `registration_ends_at` mandatory? Ordering? | Mandatory. `preview_starts_at < registration_ends_at < active_starts_at`. | — |
| 4 | 2026-08-07 | REFINE | Does the deadline auto-close registration? | Yes, independent of any admin action. | — |
| 5 | 2026-08-07 | REFINE | Can the admin shuffle before the deadline? | Yes, any time there are ≥2 participants. | — |
| 6 | 2026-08-07 | REFINE | Can the admin re-shuffle, until when? | Freely, until the activity reaches `active` state; then blocked entirely. | — |
| 7 | 2026-08-07 | REFINE | What happens at `max_participants`? | Field removed entirely — no cap, no waiting list. | — |
| 8 | 2026-08-07 | REFINE | Who sees the participant list? | Enrolled participants only. | — |
| 9 | 2026-08-07 | REFINE | When are preferences filled in? Editable? | At enrolment, rich-text editor pre-filled with existing template; editable until registration closes. | — |
| 10 | 2026-08-07 | REFINE | Deactivation/deletion of a participant? | Before shuffle: row removed. After shuffle: assignment left as-is, no special handling (matches existing Jardino gap). | — |
| 11 | 2026-08-07 | DESIGN | Where does `registration_ends_at` live? | New `calendar_secret_gift_settings` table, wired through the existing `configComponentKey()`/`configRules()`/`persistConfig()` extension point — same pattern QuoteContest already uses for its own two dates (`DateOrderRule`, no DB read). Keeps the base `Activity` table generic, unlike the `max_participants`/`requires_subscription` fields this task removes. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| | | | |
