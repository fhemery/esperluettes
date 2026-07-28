# Backlog

The entry point of the loop. `/next-task` picks the first `TODO`;
`/continue-task` resumes the first `WIP:*`. Protocol:
[`.agents/loop/README.md`](../../.agents/loop/README.md).

This file is the loop's mutable state and lives next to the task folders it
points at; `.agents/loop/` holds only the static protocol and templates.

Statuses: `TODO` · `WIP:<STEP>` · `BLOCKED:<reason>` · `DONE`
Steps: `REFINE DESIGN PLAN BUILD VERIFY WRAP`
Modes: `interactive` (stop at each step) · `auto` (run through, report at the end)

Order matters: the top-most `TODO` is the next task. Rows are unnumbered on
purpose — move or insert one anywhere without touching the others.

| Task                                                              | Folder                          | Mode        | Status                                                                                                                                                                                                           |
| ----------------------------------------------------------------- | ------------------------------- | ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Story — one author check, not two                                 | `story-author-check/`           | auto        | WIP:VERIFY                                                                                                                                                                                                       |
| Quote - private quotes not working                                | quote-private-stories           | auto        | WIP:DESIGN                                                                                                                                                                                                       |
| MultiEdit — migrate the remaining ImageService consumers          | `media-consumer-migration/`     | interactive | TODO                                                                                                                                                                                                             |
| Extract an Editor domain from Shared                              | `editor-domain/`                | interactive | TODO:do before `chapters-multi-edit/` — it adds block logic that should land in Editor, not Shared                                                                                                               |
| Chapters — MultiEdit content                                      | `chapters-multi-edit/`          | interactive | TODO                                                                                                                                                                                                             |
| Quotes — in-chapter author view (vNext)                           | `quotes-author-view/`           | interactive | BLOCKED:needs `chapters-multi-edit/` first (decision #21) and `story-author-check/`. REFINE/DESIGN/PLAN are **done** — resume at BUILD, re-reading `02-architecture.md` §4 and risk 2 against the new DOM shape. |
| Chapter annotations                                               | `annotations/`                  | interactive | TODO:enters at **BUILD** — `01`/`02`/`03` are the pre-loop documents, 10 of 14 phases remain. Overlaps `chapters-multi-edit/` on per-block anchoring; sequence them deliberately.                                |
| Discord — warn when notifications are on without a linked account | `discord-link-hint/`            | auto        | TODO                                                                                                                                                                                                             |
| Statistics — per-user statistics on the profile                   | `statistics-profile/`           | interactive | TODO                                                                                                                                                                                                             |
| MultiEdit — advanced mode for static pages                        | `multiedit-static-pages/`       | interactive | TODO                                                                                                                                                                                                             |
| Calendar — activity subscription and participant limits           | `calendar-subscription/`        | interactive | TODO                                                                                                                                                                                                             |
| Calendar — activity state-change notifications                    | `calendar-notifications/`       | interactive | TODO                                                                                                                                                                                                             |
| Quotes — moderation of quotes and notes                           | `quotes-moderation/`            | interactive | TODO                                                                                                                                                                                                             |
| Secret Gift — participants cannot enrol                           | `secret-gift-enrolment/`        | interactive | TODO:may be absorbed by `calendar-subscription/` rather than needing its own mechanism                                                                                                                           |
| Jardino — `deselected_at` is never written                        | `jardino-snapshot-deselection/` | auto        | TODO                                                                                                                                                                                                             |

## Done

Each folder holds a compact `README.md` — read that, not the phase documents.

Reference other rows by **folder name**, never by position.

| Task | Folder | Wrapped |
|------|--------|---------|
| Profile tab registry | `profile-tab-registry/` | 2026-07-28 |
| MultiEdit v1 + Media domain | `multiedit/` | 2026-07-28 (adoption unfinished — `media-consumer-migration/`, `editor-domain/`, `chapters-multi-edit/`, `multiedit-static-pages/`) |
| Statistics | `statistics/` | 2026-07-28 (profile surface missing — `statistics-profile/`) |
| Discord notifications | `discord-notifications/` | 2026-07-28 (preferences hint missing — `discord-link-hint/`) |
| Calendar and activities | `calendar/` | 2026-07-28 (deferred — `calendar-subscription/`, `calendar-notifications/`, `secret-gift-enrolment/`, `jardino-snapshot-deselection/`) |
| Quotes v1 | `Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md` | 2026-07-27 (pre-loop; docs not yet compacted into a folder) |
