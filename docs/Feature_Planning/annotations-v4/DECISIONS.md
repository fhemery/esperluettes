# Chapter annotations — v4 (report + images) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-24 | REFINE | Keep image annotation (H) in v4? | No — split into its own backlog task (`annotations-images/`); v4 is the per-annotation Report only | — |
| 2 | 2026-10-24 | REFINE | Are replies reportable too? | Yes, roots and replies | v1 #3 |
| 3 | 2026-10-24 | REFINE | Where is « Signaler » offered? | Both in-chapter popover and per-commenter pop-up | — |
| 4 | 2026-10-24 | REFINE | Snapshot content | Highlighted passage + body; for a reply, also the parent annotation | — |
| 5 | 2026-10-24 | REFINE | « Ouvrir » link target | Root comment anchor (existing comment URL), no auto-open | — |
| 6 | 2026-10-24 | REFINE | Seeded reasons | « Autre » only | — |
| 7 | 2026-10-24 | DESIGN | Where is the A1 server guard enforced? | Optional guard contract in Moderation, consulted by `createReport`; refusal → 403 | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Server refuses a report when the reporter cannot see the annotation, wrote it, or it does not exist (today's report endpoint checks none of these) | REFINE, confirmed at replay | Yes |
| A2 | Duplicate reports by the same user stay allowed, as for every topic | REFINE, confirmed at replay | Yes |
| A3 | « Signaler » hidden on own annotations/replies and on emptied ones; shown to moderators as on comments | REFINE, confirmed at replay | Yes |
| A4 | Reactions get « Signaler » like any annotation, no special case | REFINE, confirmed at replay | Yes |
| A5 | Topic key `annotation`, display name « Annotation » (not vision-spec's `chapter-annotation`) | REFINE, confirmed at replay | Yes, before any report exists |
| A6 | `user` and `user-confirmed` can both report, matching the report route's role gate | REFINE, confirmed at replay | Yes |
| A7 | `can_report` DTO flag computed from the same rule as the server guard (no JS duplicate) | DESIGN | Yes |
| A8 | « Signaler » rendered by Alpine `reportButton` inside the existing x-for templates, not the PHP report-button component | DESIGN | Yes |
| A9 | Seeded reason is the seeder's existing « Other » row for the new `annotation` topic (spec's « Autre ») | DESIGN | Yes |
| A10 | Server guard does not refuse emptied annotations; only the UI hides « Signaler » on them (spec lists three refusals only) | DESIGN | Yes |
