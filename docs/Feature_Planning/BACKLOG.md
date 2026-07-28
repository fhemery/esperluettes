# Backlog

The entry point of the loop. `/next-task` picks the first `TODO`;
`/continue-task` resumes the first `WIP:*`. Protocol:
[`.agents/loop/README.md`](../../.agents/loop/README.md).

This file is the loop's mutable state and lives next to the task folders it
points at; `.agents/loop/` holds only the static protocol and templates.

Statuses: `TODO` · `WIP:<STEP>` · `BLOCKED:<reason>` · `DONE`
Steps: `REFINE DESIGN PLAN BUILD VERIFY WRAP`
Modes: `interactive` (stop at each step) · `auto` (run through, report at the end)

Order matters: the top-most `TODO` is the next task. Reorder rows freely.

| # | Task | Folder | Mode | Status |
|---|------|--------|------|--------|
| 1 | Story — one author check, not two | `story-author-check/` | auto | TODO |
| 2 | MultiEdit — migrate the remaining ImageService consumers | `media-consumer-migration/` | interactive | TODO |
| 3 | Extract an Editor domain from Shared | `editor-domain/` | interactive | TODO:do before `chapters-multi-edit/` — it adds block logic that should land in Editor, not Shared |
| 4 | Chapters — MultiEdit content | `chapters-multi-edit/` | interactive | TODO |
| 5 | Quotes — in-chapter author view (vNext) | `quotes-author-view/` | interactive | BLOCKED:needs `chapters-multi-edit/` first (decision #21) and `story-author-check/`. REFINE/DESIGN/PLAN are **done** — resume at BUILD, re-reading `02-architecture.md` §4 and risk 2 against the new DOM shape. |
| 6 | Chapter annotations | `annotations/` | interactive | TODO:enters at **BUILD** — `01`/`02`/`03` are the pre-loop documents, 10 of 14 phases remain. Overlaps `chapters-multi-edit/` on per-block anchoring; sequence them deliberately. |
| 7 | Discord — warn when notifications are on without a linked account | `discord-link-hint/` | auto | TODO |
| 8 | Statistics — per-user statistics on the profile | `statistics-profile/` | interactive | TODO |
| 9 | MultiEdit — advanced mode for static pages | `multiedit-static-pages/` | interactive | TODO |
| 10 | Calendar — activity subscription and participant limits | `calendar-subscription/` | interactive | TODO |
| 11 | Calendar — activity state-change notifications | `calendar-notifications/` | interactive | TODO |
| 12 | Quotes — moderation of quotes and notes | `quotes-moderation/` | interactive | TODO |
| 13 | Secret Gift — participants cannot enrol | `secret-gift-enrolment/` | interactive | TODO:may be absorbed by `calendar-subscription/` rather than needing its own mechanism |
| 14 | Jardino — `deselected_at` is never written | `jardino-snapshot-deselection/` | auto | TODO |

## Done

Each folder holds a compact `README.md` — read that, not the phase documents.

Reference other rows by **folder name**, never by number — the numbers shift
whenever a row is inserted.

| Task | Folder | Wrapped |
|------|--------|---------|
| Profile tab registry | `profile-tab-registry/` | 2026-07-28 |
| MultiEdit v1 + Media domain | `multiedit/` | 2026-07-28 (adoption unfinished — `media-consumer-migration/`, `editor-domain/`, `chapters-multi-edit/`, `multiedit-static-pages/`) |
| Statistics | `statistics/` | 2026-07-28 (profile surface missing — `statistics-profile/`) |
| Discord notifications | `discord-notifications/` | 2026-07-28 (preferences hint missing — `discord-link-hint/`) |
| Calendar and activities | `calendar/` | 2026-07-28 (deferred — `calendar-subscription/`, `calendar-notifications/`, `secret-gift-enrolment/`, `jardino-snapshot-deselection/`) |
| Quotes v1 | `Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md` | 2026-07-27 (pre-loop; docs not yet compacted into a folder) |
