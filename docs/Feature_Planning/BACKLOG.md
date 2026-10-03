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
- **Calendar — activity state-change notifications** · `calendar-notifications/` · interactive · TODO: leftover from `role-gated-notification-settings/` REFINE — Quote Contest already broadcasts lifecycle to all active `user-confirmed`, ignoring each activity’s `role_restrictions`. Recipients must be the users authorized to *that* activity, not a type-level role list and not every confirmed user.
- **Chapter annotations** · `annotations/` · interactive · WIP:BUILD (15/17): re-entered at DESIGN (2026-10-03) to realign `01`/`02` with Quote, Editor domain and MultiEdit; `03` regenerated from architecture rev. 2 (13 phases + 4 checkpoints). Cross-block selections are refused (DECISIONS #1). Overlaps `chapters-multi-edit/` and `quotes-author-view/` on per-block anchoring — read both records in `_done/` first and sequence them deliberately. Two facts now settled by `quotes-author-view/`: `Shared/Resources/js/anchoring/block-elements.js` is the shared "what is a block" predicate (narrower than `canonical-text.js`'s `BLOCK_TAGS`), and quotes are now refused at capture when they span two blocks (decisions #22/#23) — decide deliberately whether annotations adopt the same restriction or handle the boundary seam. `quotable-blocks-opt-in/` then added two reusable hooks: `buildCanonicalText(root, { within })` (the caller picks which areas count — annotations may include images) and the Comment toolbar's per-action `data-requires-selection-within`; and Simple chapters now render inside one `div.ce-block.ce-block--text`.
- **Chapter annotations — v2** · `annotations-v2/` · interactive · TODO: everything cut from `annotations/` v1 (reactions, in-chapter display, post-publish edits, replies, filter, per-annotation report, image annotation). Starts after v1 is wrapped; REFINE from `vision-spec.md`.
- **Gift sound on Media, retire `<x-shared::sound-upload>`** · `media-sound-upload/` · interactive · TODO: leftover from `shared-image-upload-cleanup/`. Images are on Media's private disk, sound still raw on `local`. First tradeoff to arbitrate: teach Media a raw private-file store (Range support) or leave sound out
- **Calendar — collaborative story-writing activity type** · `collaborative-stories-activities/` · interactive · TODO: a group co-writes one story on a shared account, chapters assigned to individual authors with per-chapter scheduling/permissions. `00-request.md` already has the user's raw notes (French) from a discussion with Joanne; needs a proper REFINE pass. Was dropped from the backlog without being wrapped — restored 2026-08-05.
- **Dashboard — story to discover: exclude already-read** · `dashboard-discover-exclude-read/` · interactive · TODO
- **Admin statistics — quotes section** · `admin-statistics-quotes/` · interactive · TODO
- **Admin statistics — time-range selector** · `admin-statistics-time-range/` · interactive · TODO: leftover from [`admin-statistics-graph-toggle`](_done/admin-statistics-graph-toggle.md) (spec §8) — *12 derniers mois* / *Depuis le début*; also bounds the weekly bars, which currently span all history
- **Release mechanism with release note and rollout plan** · `release-notes/` · interactive · TODO
- **`shared/dark_theme` — honour `role_based`** · `dark-theme-role-based/` · interactive · TODO: leftover from `feature-toggle-registration` (spec §9). Checked once at boot with no user, so `role_based` acts as `off`.
- **Shared — button contrast in light themes** · `shared-button-contrast/` · interactive · TODO: leftover from `multiedit-chapter-switch-block/` WRAP — primary buttons 3.1–3.6 and accent 3.02 (spring) in light mode, below AA.
- **Calendar: dedicated time field for activity dates** · `calendar-activity-time-picker/` · interactive · TODO
- **Secret Gift: admin feedback after shuffle** · `secret-gift-shuffle-feedback/` · interactive · TODO
- **Loop improvements** · `loop-improvements/` · interactive · TODO

## Done

WRAP trims a finished task's folder to just its `README.md`, moves it to
`_done/<slug>.md`, and adds one line here. `_done/` is a flat archive, browsable
like closed PRs — not loaded by default, read when working in a related area or
tracking down why something is the way it is.

- [`e2e-types-node`](_done/e2e-types-node.md) · `@types/node@^24` direct dev dep so `tsc -p e2e` passes (one `hoverSlot` type fix); new gate step `e2e-types`, scoped to `e2e/`/Playwright config/`package.json`/lockfile, kept under `--quick`
- [`improve-loop`](_done/improve-loop.md) · checkpoint rows after shared refactors, refactor-first PLAN, gate builds on Blade changes, `--` dropped from pnpm commands, agent memory folded into skills, WRAP opens with a retro feeding `loop-improvements/`
- [`pnpm-12-upgrade`](_done/pnpm-12-upgrade.md) · pin moved to pnpm 12.6.0, `auditConfig.ignoreGhsas` → `audit.ignore`, lockfile records pnpm in `packageManagerDependencies`; no dependency moved (CI on the PR not yet run)
- [`admin-double-flash`](_done/admin-double-flash.md) · admin flashes show once: `<x-admin::layout>` is the sole owner of `<x-shared::flash-block />`, removed from 23 admin page views; file-scan guard forbids it under `pages/admin/`
- [`jardino-snapshot-deselection`](_done/jardino-snapshot-deselection.md) · dropped the never-written Jardino `deselected_at` column, `isActive()` and `currentStorySnapshot`; snapshots keyed on (goal, story), re-selection resumes the existing one
- [`admin-statistics-graph-toggle`](_done/admin-statistics-graph-toggle.md) · page-level *Cumulé / Par semaine* switch on `/admin/statistics`: weekly net bars (Monday weeks, current week lighter, stacked comment breakdown), remembered per browser; new generic `x-shared::segmented-control`
- [`multiedit-chapter-switch-block`](_done/multiedit-chapter-switch-block.md) · « Choix de chapitres » block in chapter Avancé mode — buttons to chapters of the same story, rendered at save time, per-choice enabled toggle; Editor gained a block-type registry with per-consumer `blockTypes` opt-in
- [`validation-messages-followups`](_done/validation-messages-followups.md) · « Oups » flash box lists each validation message once; French defaults for Shared's `maxstripped`, `minstripped`, `required_trimmed`
- [`validation-messages`](_done/validation-messages.md) · French defaults for every Laravel built-in rule (Shared `lang-framework/fr/validation.php`, unnamespaced, attribute-less, sizes in Ko); per-block editor errors shown under the editor in chapter/news/static page forms
- [`feature-toggle-registration`](_done/feature-toggle-registration.md) · toggles exist only once declared in a service provider (undeclared check throws), admin page built on declarations + orphan rows, read-only `config:toggles [--json]`, cleanup skill driven by both
- [`quotable-blocks-opt-in`](_done/quotable-blocks-opt-in.md) · only `.ce-block--text` areas are quotable (captions and other block types excluded from canonical text and « Citer »); Simple chapters wrapped in one text block at display; reusable `within` filter and toolbar `data-requires-selection-within`
- [`role-gated-notification-settings`](_done/role-gated-notification-settings.md) · notification types can declare `visibleToRoles` (gates preference rows, writes and `createNotificationForTypeAudience` recipients); staff get opt-out/Discord-opt-in notifications for new reports and new promotion requests
- [`story-preferences`](_done/story-preferences.md) · Settings tab « Histoires » with two reader booleans: hide trigger-warning display on story/read-list cards and the story page, and hide trigger-warned stories from library, discover and search
- [`domain-docs-gate-check`](_done/domain-docs-gate-check.md) · gate docs step enforces domain doc trio + Domain Registry sync; Follow documented and registered
- [`gate-parallel-execution`](_done/gate-parallel-execution.md) · `pnpm run gate` runs its steps (docs/deptrac/php/js/build) concurrently instead of sequentially, `build` sequenced after `php` to avoid a manifest race; multi-domain `ParallelTestCommand` concurrency was built then reverted
- [`migrate-npm-to-pnpm`](_done/migrate-npm-to-pnpm.md) · pnpm 11 is the sole Node manager; lockfile, CI, hooks, gate, and active docs switched from npm
- [`fix-audit-vulnerabilities`](_done/fix-audit-vulnerabilities.md) · cleared fixable Composer/NPM audit findings; Quill 2.0.3 XSS (GHSA-v3m3-f69x-jf25) accepted exception
- [`domain-claude-md-shims`](_done/domain-claude-md-shims.md) · every domain gets a one-line `CLAUDE.md` (`@AGENTS.md`) shim; `document-domain` now writes README + AGENTS + shim
- [`quote-contest-moderator-ops`](_done/quote-contest-moderator-ops.md) · moderators can CRUD quote-contest categories on activity edit (middleware parity)
- [`favicon-season-cache`](_done/favicon-season-cache.md) · favicon links append `?theme=<season>` so season changes bust browser cache
- [`news-comment-form-retains-text`](_done/news-comment-form-retains-text.md) · Comment draft consume-before-restore — form empty after successful submit
- [`admin-menus-e2e`](_done/admin-menus-e2e.md) · core E2E locks admin sidebar `data-nav-key` inventory for moderator / admin / tech-admin
- [`news-pin-carousel-white-screen`](_done/news-pin-carousel-white-screen.md) · Shared toggle focus overlay — stops admin pane scrolling away on pin click
- [`news-pin-carousel-first`](_done/news-pin-carousel-first.md) · newly pinned news inserts at carousel position 1 (others shift +1)
- [`news-moderator-access`](_done/news-moderator-access.md) · moderators get full News admin (CRUD, publish, pin, carousel, draft preview)
- [`secret-gift-enrolment`](_done/secret-gift-enrolment.md) · readers can join, edit their preferences and leave a Secret Gift before the shuffle, behind a mandatory registration deadline, with a moderator/admin shuffle panel
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
- **Quotes v1** · reader quote book v1. Pre-loop; planning docs (`Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md`) removed — see [`app/Domains/Quote/README.md`](../../app/Domains/Quote/README.md) for the surviving record.
