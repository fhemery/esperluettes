# Backlog

The entry point of the loop. `/next-task` picks the first `TODO`;
`/continue-task` resumes the first `WIP:*`. Protocol:
[`.agents/loop/README.md`](../../.agents/loop/README.md).

This file is the loop's mutable state and lives next to the task folders it
points at; `.agents/loop/` holds only the static protocol and templates.

Statuses: `TODO` · `WIP:<STEP>` · `BLOCKED:<reason>` — WRAP moves the entry to
`## Done` once it is finished.
Steps: `REFINE DESIGN PLAN BUILD VERIFY WRAP`
Modes: `interactive` (stop at each step) · `auto` (run through, report at the end)

Order matters: the top-most `TODO` is the next task. Entries are unnumbered on
purpose — move or insert one anywhere without touching the others.

Each entry is one line: **Task** · `folder/` · mode · status. Deliberately not
a markdown table — a table's column padding gets rewritten on every edit
(Obsidian does this on save), which turns unrelated edits from two branches
into unreadable merge conflicts. A one-line-per-task list keeps a conflict
scoped to the task that actually changed.

- **Statistics — per-user statistics on the profile** · `statistics-profile/` · interactive · TODO
- **Calendar — activity subscription and participant limits** · `calendar-subscription/` · interactive · TODO
- **Calendar — activity state-change notifications** · `calendar-notifications/` · interactive · TODO
- **Quotes — moderation of quotes and notes** · `quotes-moderation/` · interactive · TODO
- **Secret Gift — participants cannot enrol** · `secret-gift-enrolment/` · interactive · TODO: may be absorbed by `calendar-subscription/` rather than needing its own mechanism
- **Jardino — `deselected_at` is never written** · `jardino-snapshot-deselection/` · auto · TODO
- **Chapter annotations** · `annotations/` · interactive · TODO: enters at **BUILD** — `01`/`02`/`03` are the pre-loop documents, 10 of 14 phases remain. Overlaps `chapters-multi-edit/` and `quotes-author-view/` on per-block anchoring — read both records in `_done/` first and sequence them deliberately. Two facts now settled by `quotes-author-view/`: `Shared/Resources/js/anchoring/block-elements.js` is the shared "what is a block" predicate (narrower than `canonical-text.js`'s `BLOCK_TAGS`), and quotes are now refused at capture when they span two blocks (decisions #22/#23) — decide deliberately whether annotations adopt the same restriction or handle the boundary seam.
- **Gift sound on Media, retire `<x-shared::sound-upload>`** · `media-sound-upload/` · interactive · TODO: leftover from `shared-image-upload-cleanup/`. Images are on Media's private disk, sound still raw on `local`. First tradeoff to arbitrate: teach Media a raw private-file store (Range support) or leave sound out
- **Validation messages — file rules print raw keys** · `validation-messages/` · auto · TODO: from `chapters-multi-edit/` decision #11 (defect D4). No `lang/*/validation.php` is published, so `image` prints `validation.image` and `max` prints nothing. Hits `ChapterRequest` and `NewsRequest` alike; fix it once, app-wide.
- **Calendar — collaborative story-writing activity type** · `collaborative-stories-activities/` · interactive · TODO: a group co-writes one story on a shared account, chapters assigned to individual authors with per-chapter scheduling/permissions. `00-request.md` already has the user's raw notes (French) from a discussion with Joanne; needs a proper REFINE pass. Was dropped from the backlog without being wrapped — restored 2026-08-05.
- **Admin — menus E2E for moderator / admin / tech_admin** · `admin-menus-e2e/` · auto · WIP:BUILD (2/3)

## Done

WRAP trims a finished task's folder to just its `README.md`, moves it to
`_done/<slug>.md`, and adds one line here. `_done/` is a flat archive, browsable
like closed PRs — not loaded by default, read when working in a related area or
tracking down why something is the way it is.

- [`news-pin-carousel-white-screen`](_done/news-pin-carousel-white-screen.md) · Shared toggle focus overlay — stops admin pane scrolling away on pin click
- [`news-pin-carousel-first`](_done/news-pin-carousel-first.md) · newly pinned news inserts at carousel position 1 (others shift +1)
- [`news-moderator-access`](_done/news-moderator-access.md) · moderators get full News admin (CRUD, publish, pin, carousel, draft preview)
- [`comment-editor-not-displaying`](_done/comment-editor-not-displaying.md) · reply/edit composers rendered blank after the Editor domain split; fixed asset loading
- [`shared-a11y`](_done/shared-a11y.md) · tabs/confirm-modal a11y gaps (`role="tabpanel"`, focus-on-open)
- [`quote-contest`](_done/quote-contest.md) · third Calendar activity type — quote-book entries, categories, anonymous voting
- [`news-comments`](_done/news-comments.md) · comment threads on published news articles
- [`multiedit-static-pages`](_done/multiedit-static-pages.md) · Simple/Avancé block editor for static pages
- [`gate-scoped-test-paths`](_done/gate-scoped-test-paths.md) · multi-domain scoped gate runs no longer crash ParaTest
- [`quotes-author-view`](_done/quotes-author-view.md) · in-chapter author heat map + quote summary popup
- [`shared-upload-lang-ownership`](_done/shared-upload-lang-ownership.md) · moved 3 borrowed lang keys out of a dead Shared file
- [`shared-image-upload-cleanup`](_done/shared-image-upload-cleanup.md) · SecretGift gift images moved to Media's private disk
- [`chapters-multi-edit`](_done/chapters-multi-edit.md) · Simple/Avancé block editor for chapters
- [`editor-domain`](_done/editor-domain.md) · extracted the Editor domain out of Shared
- [`media-consumer-migration`](_done/media-consumer-migration.md) · Calendar/StaticPage/Profile migrated off `ImageService` onto Media
- [`quote-private-stories`](_done/quote-private-stories.md) · quoting rights on private/community stories
- [`story-author-check`](_done/story-author-check.md) · one authorship check; beta readers can quote
- [`profile-tab-registry`](_done/profile-tab-registry.md) · domains self-register their own profile tab
- [`multiedit`](_done/multiedit.md) · Media domain + block-editor v1 (News/FAQ)
- [`statistics`](_done/statistics.md) · event-driven aggregate metrics + admin page
- [`discord-notifications`](_done/discord-notifications.md) · Discord DM delivery channel for notifications
- [`calendar`](_done/calendar.md) · plugin-based time-bound activities (Jardino, SecretGift, Quote contest)
- [`discord-link-hint`](_done/discord-link-hint.md) · preferences warning when Discord notifications are on but unlinked
- **Quotes v1** · `Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md` — reader quote book v1. Pre-loop; docs never compacted into `_done/`, kept as loose files.
