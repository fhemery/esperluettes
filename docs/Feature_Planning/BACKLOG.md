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

| Task                                                            | Folder                          | Mode        | Status                                                                                                                                                                                                                                                                                                                    |
| --------------------------------------------------------------- | ------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Chapter annotations                                             | `annotations/`                  | interactive | TODO:enters at **BUILD** — `01`/`02`/`03` are the pre-loop documents, 10 of 14 phases remain. Overlaps `chapters-multi-edit/` and `quotes-author-view/` on per-block anchoring — read both `README.md`s first and sequence them deliberately. Two facts now settled by `quotes-author-view/`: `Shared/Resources/js/anchoring/block-elements.js` is the shared "what is a block" predicate (narrower than `canonical-text.js`'s `BLOCK_TAGS`), and quotes are now refused at capture when they span two blocks (decisions #22/#23) — decide deliberately whether annotations adopt the same restriction or handle the boundary seam.                                                              |
| Statistics — per-user statistics on the profile                 | `statistics-profile/`           | interactive | TODO                                                                                                                                                                                                                                                                                                                      |
| MultiEdit — advanced mode for static pages                      | `multiedit-static-pages/`       | interactive | WIP:WRAP — VERIFY done (e2e feature specs green; checklist filled)                                                                                                                                                                                                                                                                                                           |
| News - Add ability to comment news                              | `news-comments/`                | interactive | TODO                                                                                                                                                                                                                                                                                                                      |
| Calendar — activity subscription and participant limits         | `calendar-subscription/`        | interactive | TODO                                                                                                                                                                                                                                                                                                                      |
| Calendar — activity state-change notifications                  | `calendar-notifications/`       | interactive | TODO                                                                                                                                                                                                                                                                                                                      |
| Quotes — moderation of quotes and notes                         | `quotes-moderation/`            | interactive | TODO                                                                                                                                                                                                                                                                                                                      |
| Secret Gift — participants cannot enrol                         | `secret-gift-enrolment/`        | interactive | TODO:may be absorbed by `calendar-subscription/` rather than needing its own mechanism                                                                                                                                                                                                                                    |
| Jardino — `deselected_at` is never written                      | `jardino-snapshot-deselection/` | auto        | TODO                                                                                                                                                                                                                                                                                                                      |
| Gift sound on Media, retire `<x-shared::sound-upload>`          | `media-sound-upload/`           | interactive | TODO:leftover from `shared-image-upload-cleanup/`. Images are on Media's private disk, sound still raw on `local`. First tradeoff to arbitrate: teach Media a raw private-file store (Range support) or leave sound out                                                                                                   |
| Validation messages — file rules print raw keys                 | `validation-messages/`          | auto        | TODO:from `chapters-multi-edit/` decision #11 (defect D4). No `lang/*/validation.php` is published, so `image` prints `validation.image` and `max` prints nothing. Hits `ChapterRequest` and `NewsRequest` alike; fix it once, app-wide.                                                                                  |
| Gate — the scoped run breaks on multi-domain branches            | `gate-scoped-test-paths/`       | auto        | TODO:found during `quotes-author-view/` BUILD. `scripts/gate.js` passes several dirs to `artisan test:parallel`, which forwards to `artisan test` — that accepts a single `path`, so any branch touching ≥2 domains dies on `Too many arguments, expected arguments "path"`. Reproducible on a clean tree with any two directories. A one-domain branch is unaffected, which is why it survived until now. `-- --all` is green, so nothing is hidden by the workaround; every phase of that task was gated that way. The call to make: fix `scripts/gate.js` (loop, or pass one path — smaller) or `ParallelTestCommand` (accept several paths — more useful).                          |
| `<x-shared::image-upload>` — one consumer left, decide its fate | `shared-image-upload-cleanup/`  | auto        | TODO:from `media-consumer-migration/` decision #9. SecretGift is the only user (private `local` disk, no Media semantics). Its lang file is also borrowed by Story's cover tab — do not delete that.                                                                                                                      |

## Done

Each folder holds a compact `README.md` — read that, not the phase documents.

Reference other rows by **folder name**, never by position.

| Task | Folder | Wrapped |
|------|--------|---------|
| Quotes — in-chapter author view (vNext) | `quotes-author-view/` | 2026-08-01 (24/24 QA rows pass; e2e specs retired, seeders kept — decision #24; leftover — `gate-scoped-test-paths/`) |
| Shared `image-upload` lang file has no component | `shared-upload-lang-ownership/` | 2026-07-31 (VERIFY skipped — copy-only, user smoke-checks the Story cover tab) |
| `<x-shared::image-upload>` — one consumer left, decide its fate | `shared-image-upload-cleanup/` | 2026-07-31 (VERIFY skipped — user smoke-checks; leftovers — `media-sound-upload/`, `shared-upload-lang-ownership/`) |
| Chapters — MultiEdit content | `chapters-multi-edit/` | 2026-07-31 (D4 deferred — `validation-messages/`) |
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
