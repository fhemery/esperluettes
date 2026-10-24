# Admin statistics — quotes section — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.

## 1. Overview

The admin statistics page gains a « Citations » section showing how many quotes
readers currently hold in their quote books, and the evolution of that total
over time. It is for staff reading `/admin/statistics`; the value is seeing
whether the quote book is adopted.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Citation | A passage a reader saved from a chapter into their quote book |
| Total de citations | Number of citations that currently exist, net of deletions |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` / `user-confirmed` / author / moderator | nothing (page not accessible) | — |
| Admin / tech-admin | Same access as the rest of `/admin/statistics` | read only |

The access rule is whatever the existing statistics page already enforces
(`admin` and `tech-admin` only); this feature does not change it. Moderators do
not get access (decision #6).

## 4. Functional requirements

### 4.1 Reading the quotes section

1. A staff member opens `/admin/statistics`.
2. The summary row shows one more tile: the current total of citations.
3. A new « Citations » tab holds a widget with the same total and its evolution
   curve.
4. The page-level « Cumulé / Par semaine » switch applies to this curve like
   the others: cumulative total, or net weekly change as bars.
5. Edge: with no quote at all, the total is 0 and the curve is empty/flat, as
   for other empty metrics.

### 4.2 What counts

The total is net: it rises when a reader saves a quote and falls when a quote
stops existing or being visible:

- a reader deletes one of their quotes → −1, for good (the quote is
  hard-deleted, see `quote-hard-delete/`; reactivation never brings it back);
- a user is deleted → their quotes are subtracted;
- a user is deactivated → their quotes are subtracted; reactivated → added back.

Quotes on a chapter or story that was deleted still count, because the rows
remain (they are shown as « unavailable » to their owner).

## 5. Lifecycle

See 4.2. The evolution curve must reflect past deletions and deactivations as
well as it can for history that predates this feature (see §9).

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | `admin` and `tech-admin` only, as the existing page (not moderators) |
| Visibility / privacy | Aggregate count only; no quote text, note or author is shown |
| Settings | N/A — no preference |
| Notifications | N/A — nothing notifies anyone |
| Domain events | Quote deletions, user-driven or by deletion/deactivation/reactivation, must become observable (DESIGN) |
| Statistics | This feature *is* the statistics addition |
| Moderation | N/A |
| Lifecycle / cascade | §4.2 / §5 |
| Media | N/A |
| Search | N/A |
| i18n | French labels, like the rest of the page |
| Mobile | Tab and tile must fit the page's existing responsive behaviour |
| Accessibility | Same as existing widgets |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | How are deletions counted? | Net total: the curve falls on deletions |
| 2 | Placement | New « Citations » tab, plus a summary tile |
| 3 | Deactivated users' quotes | Excluded while deactivated, restored on reactivation |
| 6 | Do moderators see the page? | No — `admin` and `tech-admin` only, as today |

## 8. Out of scope

- Per-user quote statistics (belongs to `statistics-profile/`).
- The time-range selector (`admin-statistics-time-range/`).
- Breakdowns (per story, per chapter, with/without note).
- Any change to quote behaviour for readers.

## 9. Open questions

- **Non-blocking, for DESIGN** — history before the new deletion events exist can
  only be rebuilt from creations (events stored since 2026-07-23), so past
  deletions are invisible in the curve; whether to correct the baseline by
  recomputing from the table is a technical call.
- **Assumption** — quotes on deleted chapters/stories count (§4.2); not asked.
