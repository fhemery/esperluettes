# Role-gated notification preferences (moderator/admin) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-15 | REFINE | Staff types do not exist today (badges only). Scope? | Create the two staff notifications (new report, new promotion request) **and** role-gate their preference rows. | — |
| 2 | 2026-08-15 | REFINE | Who receives them? | Same trio as the navbar badges: moderator + admin + tech-admin, for both types. | — |
| 3 | 2026-08-15 | REFINE | Website channel? | Default ON, staff can opt out. Discord stays default OFF / opt-in. | — |
| 4 | 2026-08-15 | REFINE | Notify the actor (reporter / requester)? | Suppress self — other staff only. | — |
| 5 | 2026-08-15 | REFINE | Notification body? | Short line + reporter/requester display name + link to the matching admin queue. No report reason/topic. | — |
| 6 | 2026-08-15 | REFINE | Demotion: stored Discord/site opt-in? | Keep stored prefs so re-promotion restores them. Hide rows and stop sending immediately. | — |
| 7 | 2026-08-15 | REFINE | Other types / hide requester-facing rows? | Only these two types. Leave badges and “promotion accepted/rejected” rows as they are. User noted the shape is broadcast-to-roles plus exclude-self. | — |
| 8 | 2026-08-15 | REFINE | Reuse beyond these two types? | Yes: several later cases will want “send to certain roles, optionally excluding the current user”. This task defines that rule; it only ships the two staff types. | — |
| 9 | 2026-08-15 | REFINE | Is Calendar / Quote Contest “event starting” this rule, or “authorized to the activity”? | The second: recipients must be users who pass that activity’s `role_restrictions`, not a type-level role list. Today Quote Contest broadcasts to all active `user-confirmed` and ignores per-activity restrictions. Logged on `calendar-notifications/`. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Staff types live in the existing « Promotions & modération » group; regular users keep seeing only the two requester rows. | REFINE | Yes — a separate staff-only group is cosmetic. |
| A2 | One notification per event; no hourly digest. | REFINE | Yes |
| A3 | Deactivated accounts are not recipients (existing `activeOnly` role lookup). | REFINE | Yes |
| A4 | Already-sent inbox items stay if the report/request is later resolved or deleted. | REFINE | Yes |
| A5 | Proposed French preference labels: « Un nouveau signalement a été déposé », « Une nouvelle demande de promotion a été déposée ». Inbox copy is a short line + display name; BUILD may tighten wording. | REFINE | Yes |
| A6 | Links go to the same admin index the navbar badges already use, not a specific report/request show page. | REFINE | Yes |
| A7 | Prefs save must refuse staff-type updates from non-staff, not only hide the rows. | REFINE | No — otherwise the gate is cosmetic. |
