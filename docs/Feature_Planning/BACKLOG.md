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

| Task                                                            | Folder                          | Mode        | Status                                                                                                                                                                                                                |
| --------------------------------------------------------------- | ------------------------------- | ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Chapters — MultiEdit content                                    | `chapters-multi-edit/`          | interactive | WIP:BUILD                                                                                                                                                                                                             |
| Quotes — in-chapter author view (vNext)                         | `quotes-author-view/`           | interactive | BLOCKED:needs `chapters-multi-edit/` first (decision #21). `story-author-check/` is done. REFINE/DESIGN/PLAN are **done** — resume at BUILD, re-reading `02-architecture.md` §4 and risk 2 against the new DOM shape. |
| Chapter annotations                                             | `annotations/`                  | interactive | TODO:enters at **BUILD** — `01`/`02`/`03` are the pre-loop documents, 10 of 14 phases remain. Overlaps `chapters-multi-edit/` on per-block anchoring; sequence them deliberately.                                     |
| Statistics — per-user statistics on the profile                 | `statistics-profile/`           | interactive | TODO                                                                                                                                                                                                                  |
| MultiEdit — advanced mode for static pages                      | `multiedit-static-pages/`       | interactive | TODO                                                                                                                                                                                                                  |
| News - Add ability to comment news                              | `news-comments/`                | interactive | TODO                                                                                                                                                                                                                  |
| Calendar — activity subscription and participant limits         | `calendar-subscription/`        | interactive | TODO                                                                                                                                                                                                                  |
| Calendar — activity state-change notifications                  | `calendar-notifications/`       | interactive | TODO                                                                                                                                                                                                                  |
| Quotes — moderation of quotes and notes                         | `quotes-moderation/`            | interactive | TODO                                                                                                                                                                                                                  |
| Secret Gift — participants cannot enrol                         | `secret-gift-enrolment/`        | interactive | TODO:may be absorbed by `calendar-subscription/` rather than needing its own mechanism                                                                                                                                |
| Jardino — `deselected_at` is never written                      | `jardino-snapshot-deselection/` | auto        | TODO                                                                                                                                                                                                                  |
| Gift sound on Media, retire `<x-shared::sound-upload>`          | `media-sound-upload/`           | interactive | TODO:leftover from `shared-image-upload-cleanup/`. Images are on Media's private disk, sound still raw on `local`. First tradeoff to arbitrate: teach Media a raw private-file store (Range support) or leave sound out |
## Done

Each folder holds a compact `README.md` — read that, not the phase documents.

Reference other rows by **folder name**, never by position.

| Task | Folder | Wrapped |
|------|--------|---------|
| Shared `image-upload` lang file has no component | `shared-upload-lang-ownership/` | 2026-07-31 (VERIFY skipped — copy-only, user smoke-checks the Story cover tab) |
| `<x-shared::image-upload>` — one consumer left, decide its fate | `shared-image-upload-cleanup/` | 2026-07-31 (VERIFY skipped — user smoke-checks; leftovers — `media-sound-upload/`, `shared-upload-lang-ownership/`) |
| Extract an Editor domain from Shared | `editor-domain/` | 2026-07-30 (VERIFY skipped — `editor-domain-visual-qa/`) |
| Discord — preferences hint for unlinked account | `discord-link-hint/` | 2026-07-29 |
| MultiEdit — migrate the remaining ImageService consumers | `media-consumer-migration/` | 2026-07-29 (leftover — `shared-image-upload-cleanup/`) |
| Quotes — private stories | `quote-private-stories/` | 2026-07-28 |
| Story — one author check, not two | `story-author-check/` | 2026-07-28 |
| Profile tab registry | `profile-tab-registry/` | 2026-07-28 |
| MultiEdit v1 + Media domain | `multiedit/` | 2026-07-28 (adoption unfinished — `editor-domain/`, `chapters-multi-edit/`, `multiedit-static-pages/`) |
| Statistics | `statistics/` | 2026-07-28 (profile surface missing — `statistics-profile/`) |
| Discord notifications | `discord-notifications/` | 2026-07-28 |
| Calendar and activities | `calendar/` | 2026-07-28 (deferred — `calendar-subscription/`, `calendar-notifications/`, `secret-gift-enrolment/`, `jardino-snapshot-deselection/`) |
| Quotes v1 | `Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md` | 2026-07-27 (pre-loop; docs not yet compacted into a folder) |
