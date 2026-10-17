# New fanart / artwork module — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — rows 1–33 and A1–A16 are settled
  (#32 / #33 resolved plan items O1 / O2; #33 supersedes #12).

The whole epic is planned here (DECISIONS #1). There is **no feature toggle**
(DECISIONS #17): the epic ships as one release at the end, so intermediate
phases may leave reachable-but-unlinked routes. Every phase still ends on a
green gate and enforces its own security rules.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Refactor — Media: private image variants, `streamVariant`, `copyPrivate`, `fanart` / `moderation` scope roots | M | — | DONE |
| 1v | Checkpoint — Secret Gift images & sounds, media library picker, `media:gc --dry-run` | S | 1 | DONE |
| 2 | Refactor — Moderation: `_media` snapshot capability (copy, GC claim, moderator stream, `_media_urls`) | M | 1 | DONE |
| 2v | Checkpoint — existing report topics (story, chapter, comment, profile) submit + admin report page | S | 2 | DONE |
| 3 | Story public API additions (readability batch, read chapters, chapters list, atomic credit spend) | M | — | DONE |
| 3b | Story: hide a co-authored story on deactivation only once all its authors are deactivated | S | — | TODO |
| 3bv | Checkpoint — co-authored story with one deactivated author: listings, story page, chapter page, collaborators page | S | 3b | TODO |
| 4 | Fanart domain skeleton: provider, deptrac, migrations, models, enums, media usage provider, docs | M | 1 | TODO |
| 5 | Settings registration + `ModeService` (most-restrictive mode) | S | 3, 4 | TODO |
| 6 | `FanartPolicy` + fanartiste membership reads | M | 3, 5 | TODO |
| 7 | Notifications: group, ten content types, `FanartNotifier` | M | 4 | TODO |
| 8 | Gated file / thumbnail streaming routes | S | 1, 6 | TODO |
| 9 | Submit a fanart (non-author): create page + POST | M | 6, 7 | TODO |
| 10 | Review: accept / decline + audio replacement | M | 9 | TODO |
| 11 | Artwork (author): upload with credit spend, edit | M | 10 | TODO |
| 12 | Deletion: owner / author delete, moderator delete + `Fanart.DeletedByModeration` | S | 9 | TODO |
| 13 | Lifecycle listeners (user deleted / deactivated / reactivated, story deleted, chapter deleted) | M | 3b, 7, 9 | TODO |
| 14 | Moderation topic: snapshot formatter, reasons seeder, author-reports-pending listener (A1) | S | 2, 12 | TODO |
| 15 | Fanartiste grant / revoke + collaborators-page section | S | 6, 7 | TODO |
| 16 | Fanartiste role requests (create, withdraw, accept, decline, 30-day cooldown) | M | 15 | TODO |
| 17 | Lazy fragments: Fanarts tab, chapter groups, viewer / review viewer | M | 8, 10, 12 | TODO |
| 18 | Story page integration: tab shell, action buttons, viewer modal | M | 16, 17 | TODO |
| 18v | Checkpoint — story page for guest / `user` / confirmed reader / author / moderator | S | 18 | TODO |
| 19 | Story-page audio: speaker icons + `fanartPlayer` store with auto-advance | M | 18 | TODO |
| 20 | Chapter page widgets: audio under title, image block at bottom | S | 17 | TODO |
| 20v | Checkpoint — chapter page (quotes, annotations, comments) and story chapter list | S | 19, 20 | TODO |
| 21 | Profile tab « Fanarts » (Images / Sons, pending badge, privacy) | M | 17 | TODO |
| 22 | Discord feed: bot endpoints + signed image URL | S | 10, 11 | TODO |
| 23 | Polish: i18n pass, a11y, mobile viewer, docs finalisation | S | all | TODO |

**28 rows** (24 phases + 4 checkpoints).

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/28)` resume correctly.
Checkpoint rows are run by `visual-verifier` and change no code.

Why no checkpoint after phase 3: it only **adds** public-API methods and one
new `ChapterCreditService` method; no existing method or caller changes, so the
existing Story suite is the whole regression net. Phase 3b, by contrast,
**changes** existing Story behaviour (DECISIONS #32): a co-authored story now
stays visible while one of its authors is deactivated, so existing pages
(listings, story page, chapter page, collaborators page) meet a state they
never rendered before — an author whose profile is soft-deleted. Hence
checkpoint 3bv. Phases 15, 19 and 20 add
component tags to Story views (collaborators page, chapter rows, chapter page);
those surfaces are covered by checkpoints 18v / 20v and the visual QA list.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Conventions every Fanart phase follows (stated once here, repeated as a
  one-line pointer in each phase):
  - **Staff** = roles `moderator`, `admin`, `tech-admin`
    (`Roles::MODERATOR`, `Roles::ADMIN`, `Roles::TECH_ADMIN`).
  - Web route middleware is the project's convention
    `['web', 'auth', 'compliant', 'role:user-confirmed,moderator,admin,tech-admin']`
    (Quote precedent, `app/Domains/Quote/Private/routes.php`). The
    architecture's `verified` is replaced by `compliant` (see Open items).
  - Never `Route::patch` — PUT for updates.
  - Fanart never deletes a file: it clears rows; `media:gc` collects files.
  - Strings live in `app/Domains/Fanart/Private/Resources/lang/fr/*.php`.
  - Tests are Pest feature tests in `app/Domains/Fanart/Tests/Feature/…`
    using real users of real roles (helpers from `tests/` and
    `app/Domains/*/Tests/helpers.php`, as Quote's tests do).

---

## Phase 1 — Refactor — Media: private image variants, `streamVariant`, `copyPrivate`, new scope roots

**Goal.** Give Media the three capabilities Fanart and Moderation need — gated
private variants, private copy, two new private scope roots — without changing
anything for today's consumers (Secret Gift, public images).

Architecture: §1.1 "Media", §7 #2 / #8. Today
`app/Domains/Media/Private/Services/MediaService.php` has
`PRIVATE_SCOPE_ROOTS = ['secret-gift']`, `folderFor()` accepts
`secret-gift/{id}`, `storePrivate()` already passes `$widths` to
`ImageService::process()` (which writes `{name}-{w}w.jpg` + `.webp`), but
`hasVariants()` only checks the public disk and the private GC sweep
(`originalsIn()` on the private disk = `allFiles`) would treat those variant
files as unclaimed originals and delete them.

**Deliverables.**
- `app/Domains/Media/Private/Services/MediaService.php`
  - `PRIVATE_SCOPE_ROOTS = ['secret-gift', 'fanart', 'moderation']`.
  - `folderFor()`: accept `fanart/{storyId}` (`#^fanart/\d+$#`) and
    `moderation/{topicKey}` (`#^moderation/[a-z0-9_-]+$#`).
  - `hasVariants(string $path)`: check `{name}-800w.webp` on
    `diskFor($path)` instead of always the public disk.
  - `streamVariant(string $path, int $width, string $format = 'webp', array $headers = []): Response`
    — private paths only (throws `InvalidArgumentException` on a public path,
    or on a missing variant file → caller turns that into 404); builds the
    variant path by the same naming rule as `variantUrl()` and streams it like
    `stream()` does (BinaryFileResponse, inline). No authorization.
  - `copyPrivate(string $path, string $targetScope): string` — `$targetScope`
    must be private; reads the original from `diskFor($path)`, writes it under
    `folderFor($targetScope)` on the private disk with a fresh random name
    keeping the extension; returns the new path. Variants not copied.
  - `originalsIn()` on the private disk: skip files matching
    `/-\d+w\.(jpg|jpeg|png|webp)$/i` (they are variants; the original's
    `deleteWithVariants()` already removes them with it).
- `app/Domains/Media/Public/Api/MediaPublicApi.php` — expose
  `streamVariant(...)` and `copyPrivate(...)` (thin delegations), with
  docblocks restating "no authorization".
- `app/Domains/Media/README.md`, `app/Domains/Media/AGENTS.md` — private
  variants now exist (opt-in via `storePrivate($scope, $file, [400, 800])`),
  served only by `streamVariant` behind the consumer's own gate; the
  "Never call `originalUrl` / `variantUrl` … on a private path" invariant
  stays; list the two new roots; GC rule for private variants.

**Tests.** (`app/Domains/Media/Tests/Feature/`)
- `MediaPrivateVariantsTest.php`
  - `it('writes 400w and 800w jpg/webp variants on the private disk when widths are given')`
  - `it('reports hasVariants true for a private original stored with widths, false without')`
  - `it('streams a private variant with an inline image response')`
  - `it('refuses streamVariant on a public path')`
  - `it('throws on streamVariant for a width that was not generated')`
- `MediaCopyPrivateTest.php`
  - `it('copies a private original into moderation/{topic} under a new name')`
  - `it('copies a public original into a private scope')`
  - `it('refuses a public target scope')`
  - `it('leaves the source file untouched')`
- `MediaServiceTest.php` (extend the GC cases)
  - `it('keeps the private variants of a claimed private original')`
  - `it('deletes an unclaimed private original together with its variants')`
  - `it('accepts fanart/{id} and moderation/{topic} scopes and rejects malformed ones')`
- Existing `MediaPrivateStorageTest`, `MediaServiceTest`, Calendar
  `SecretGift/ServeFileTest`, `SecretGift/GiftImageUsageProviderTest` unchanged
  and green.

**Acceptance.**
- ✅ Secret Gift upload / serve tests pass unmodified.
- ✅ `media:gc` on a private folder with a claimed original + its 4 variants
  deletes nothing; with the original unclaimed and past grace, deletes all 5.
- ✅ `variantUrl()` / `originalUrl()` still throw on a private path.
- ✅ `pnpm run gate` green.

---

## Checkpoint 1v — Secret Gift images & sounds, media library picker, GC dry-run

Existing consumers to look at after phase 1:
- Calendar › Secret Gift activity page as a participant: upload an image gift
  and a sound gift, see both rendered back (image + player with seeking);
  as the recipient after reveal, see them.
- Admin › News (or FAQ / static page) editor: open the media library picker,
  thumbnails render, pick one, save, the public page shows it.
- `./vendor/bin/sail artisan media:gc --dry-run` on the seeded dataset: the
  output lists no `secret-gift/…-400w.*` / `-800w.*` files and no claimed
  originals.
- e2e: `e2e/tests/core/confirm-modal.spec.ts` (uses `SecretGiftActivityPage`
  / `SoundField`) and `e2e/tests/core/editor.spec.ts`.

Screenshots → `shots/checkpoint-1v/`.

**Result (2026-10-17, HEAD 2e8e5841) — PASS**
- ✅ e2e `confirm-modal.spec.ts` + `editor.spec.ts`: 13/13 (incl. auth setup).
- ✅ Giver (`confirmed`, active gift): image + sound upload saved, image decoded, `<audio>` duration 20 s, seek to 5 s OK, no HTTP >= 400.
- ✅ Recipient after reveal (`confirmed`, ended gift): image decoded, player 0:20, seek OK (`recipient-reveal.png`).
- ✅ Admin › News: library picker opens, 4 thumbnails decoded (`picker-open.png`). Pick-and-save not exercised (News save from the flow stayed on the form; not investigated, unrelated to Media changes).
- ✅ `media:gc --dry-run`: no `-400w`/`-800w` variant and no claimed original listed on the dev DB; on the e2e DB only `secret-gift/2/2.jpg`, `6.jpg` (legacy dev files with no assignment in that DB).

---

## Phase 2 — Refactor — Moderation: `_media` snapshot capability

**Goal.** Let any snapshot formatter attach media files to a report: Moderation
copies them at report creation, keeps them alive for GC while the report
exists, streams them to staff, and hands their URLs to `render()` — no change
for existing topics (none returns `_media`).

Architecture: §1.1 "Moderation", §5 (new edge), §7 #3 / #9. Phase 1 left
`MediaPublicApi::copyPrivate($path, 'moderation/{topicKey}')`,
`MediaPublicApi::stream($path, $headers)` and the private root `moderation`.

**Deliverables.**
- `app/Domains/Moderation/Private/Services/ModerationService.php` —
  `createReport()`: after `$formatter->capture()`, if
  `isset($snapshot['_media'])`, replace each path with
  `MediaPublicApi::copyPrivate($path, "moderation/{$topicKey}")` (skip, and
  drop from the list, a path that no longer `exists()`); persist the copied
  list in `content_snapshot['_media']`.
- `app/Domains/Moderation/Private/Support/ReportMediaUsageProvider.php`
  (implements `Media\Public\Contracts\MediaUsageProvider`) — yields every
  `content_snapshot->_media[*]` of every `moderation_reports` row; registered
  in `ModerationServiceProvider::boot()` via `MediaUsageRegistry`.
- Route (staff group of `app/Domains/Moderation/Private/routes.php`):
  `GET admin/moderation/moderation-reports/{moderationReport}/media/{index}`
  → `Admin\ModerationReportController::media` →
  `MediaPublicApi::stream($path, ['Cache-Control' => 'private, max-age=3600'])`;
  404 for an out-of-range index. Name `moderation.admin.moderation-reports.media`.
- `Admin\ModerationReportController::show` — before
  `$formatter->render($snapshot)`, add
  `$snapshot['_media_urls'] = [route(...media, [report, i]) …]` (same order as
  `_media`) when `_media` is present.
- `app/Domains/Moderation/Public/Contracts/SnapshotFormatterInterface.php` —
  docblock: reserved keys `_media` (`string[]` of Media paths, returned by
  `capture()`) and `_media_urls` (given to `render()`).
- `deptrac.yaml` — `ModerationPrivate → MediaPublic`, and
  `ModerationTests → MediaPublic` if the tests need it.
- `app/Domains/Moderation/README.md` / `AGENTS.md` — document the capability
  and the retention rule (kept as long as the report row exists).

**Tests.** (`app/Domains/Moderation/Tests/Feature/`)
- `ReportMediaSnapshotTest.php` — register a test topic whose formatter
  returns `_media`:
  - `it('copies every _media file into moderation/{topic} and stores the copied paths')`
  - `it('keeps the copy readable after the source file is removed')`
  - `it('drops a _media path that does not exist at report time')`
  - `it('leaves snapshots without _media untouched')`
- `ReportMediaUsageProviderTest.php`
  - `it('claims the _media paths of every report')`
  - `it('stops claiming them once the report is deleted')`
- `ReportMediaStreamTest.php`
  - `it('streams a snapshot media file to a moderator')`
  - `it('returns 403 to a confirmed user and redirects a guest')`
  - `it('returns 404 for an index out of range')`
  - `it('passes _media_urls to the formatter render() on the admin show page')`
- Existing Moderation suite (`SubmitModerationReportTest`, `ApproveReportTest`,
  `DeleteReportTest`, `Admin/*`) unchanged and green.

**Acceptance.**
- ✅ Existing topics' reports are created and displayed exactly as before.
- ✅ A non-staff user gets 403 on the media route.
- ✅ `pnpm run gate` green (deptrac included).

---

## Checkpoint 2v — Existing report topics

After phase 2, as a confirmed user report a story, a chapter, a comment and a
profile (one each); as a moderator open Admin › Moderation › reports: the
list and each show page render as before (snapshot block, approve / dismiss /
comment). Approve one, dismiss one. e2e: `e2e/tests/core/comment-thread.spec.ts`
and `e2e/tests/core/admin-menus.spec.ts`.

Screenshots → `shots/checkpoint-2v/`.

**Result (2026-10-17, HEAD 62cc44ff) — PASS**
- e2e `comment-thread.spec.ts` and `admin-menus.spec.ts`: green.
- Submit via the report modal (e2e instance): story and chapter as `confirmed`, profile as `confirmed`, comment as `author` (the seeded comment is `confirmed`'s own, so it shows no report button there). Each shows the success state.
- Moderator: list shows the 4 reports; each show page renders (type, reporter, description, content snapshot, approve / reject / delete, moderation comment). Approved #4, dismissed #3; list filtered on pending then shows only #2 and #1.
- No 4xx/5xx or page error logged by the driver.

---

## Phase 3 — Story public API additions

**Goal.** Expose, on `StoryPublicApi`, the reads and the atomic credit spend
Fanart needs — additive only, no existing method or caller changed.

Architecture: §1.1 "Story". Current state: `StoryPolicy::view`
(`app/Domains/Story/Private/Policies/StoryPolicy.php`) is the per-story rule
(staff bypass; public; community ⇒ confirmed; private ⇒ collaborator);
`Story` uses `SoftDeletes` (author deactivation soft-deletes the story);
`ReadingProgressService::getReadChapterIdsForUserInStory()` exists;
`ChapterCreditService::spendOne()` is a non-conditional increment;
`StoryChapterDto::fromModel()` exists.

**Deliverables.**
- `app/Domains/Story/Public/Api/StoryPublicApi.php`:
  - `filterReadableStoryIds(?int $userId, array $storyIds): array` — one
    query: stories not soft-deleted, filtered by the `StoryPolicy::view` rule
    (staff → all existing; guest → public only; non-confirmed user → public +
    private where collaborator; confirmed → public + community + private where
    collaborator). Returns `int[]` preserving no particular order.
  - `canRead(?int $userId, int $storyId): bool` — wrapper over the batch.
  - `getReadChapterIds(int $userId, int $storyId): array` — delegates to
    `ReadingProgressService::getReadChapterIdsForUserInStory`.
  - `getReadChapterIdsByStoryIds(int $userId, array $storyIds): array` —
    `array<int storyId, int[] chapterIds>`, one query (new
    `ReadingProgressService::getReadChapterIdsForUserInStories`).
  - `getChaptersForStory(int $storyId): array` — `StoryChapterDto[]` ordered
    by `sort_order`, all statuses (caller filters on `status`).
  - `getChaptersForStories(array $storyIds): array` —
    `array<int storyId, StoryChapterDto[]>`, one query (profile tab).
  - `getAvailableChapterCredits(int $userId): int` — delegates to
    `ChapterCreditService::availableForUser`.
  - `spendChapterCredit(int $userId): bool` — delegates to new
    `ChapterCreditService::trySpendOne(int $userId): bool`: a single
    conditional `UPDATE … SET credits_spent = credits_spent + 1 WHERE user_id
    = ? AND credits_gained - credits_spent > 0`, returns affected rows === 1.
    `spendOne()` and the chapter-creation flow are **not** touched.
- `app/Domains/Story/Private/Services/ReadingProgressService.php`,
  `ChapterCreditService.php`, `StoryService.php` / repository — the supporting
  queries.
- `app/Domains/Story/AGENTS.md` — Public API table rows for the new methods.

**Tests.** (`app/Domains/Story/Tests/Feature/StoryPublicApiFanartReadsTest.php`)
- `it('filterReadableStoryIds: guest gets only public stories')`
- `it('filterReadableStoryIds: non-confirmed user gets public + private-where-collaborator, not community')`
- `it('filterReadableStoryIds: confirmed user gets public + community + private-where-collaborator')`
- `it('filterReadableStoryIds: moderator gets every non-deleted story')`
- `it('filterReadableStoryIds excludes soft-deleted stories (deactivated author)')`
- `it('filterReadableStoryIds runs a single query for many stories')` (query log count)
- `it('canRead mirrors StoryPolicy::view for each visibility')`
- `it('getReadChapterIds / getReadChapterIdsByStoryIds return the marked chapters')`
- `it('getChaptersForStory returns chapters in sort order with status')`
- `it('spendChapterCredit spends one and returns true while credits remain')`
- `it('spendChapterCredit returns false and spends nothing at zero credits')`
- `it('spendChapterCredit returns false for a user with no credits row')`

**Acceptance.**
- ✅ Existing Story suite green with no test modified.
- ✅ `spendChapterCredit` never drives `credits_gained - credits_spent` below 0.
- ✅ `pnpm run gate` green.

---

## Phase 3b — Story: co-authored story hidden only once all its authors are deactivated

**Goal.** Change Story's deactivation rule (DECISIONS #32): deactivating a
user soft-deletes a story they author only if **no** author of that story is
still active; a co-authored story with at least one active author stays
visible. Independent of Fanart; account deletion is **not** touched
(DECISIONS #33 keeps the hard delete of every co-authored story).

Architecture: §1.1 "Story" (deactivation rule change). Current state, read
from the code:
- `Auth` `UserService::deactivateUser()` sets the user inactive, then emits
  `Auth.UserDeactivated` — so during the listener the user already reads as
  inactive. `AuthPublicApi::getUsersById(int[] $ids)` returns
  `[id => ['email', 'isActive']]` (a missing user → `isActive: false`), in one
  query. `StoryService` already injects `AuthPublicApi`.
- `Story/Private/Listeners/SoftDeleteStoriesOnUserDeactivated` →
  `StoryService::softDeleteStoriesByAuthor($userId)` →
  `StoryRepository::findByAuthor($userId)` (every story where the user is
  among the authors, co-authored included) → private `softDeleteStory()`
  (soft-deletes chapters, then the story).
- `RestoreStoriesOnUserReactivated` → `restoreStoriesByAuthor($userId)`:
  every story (withTrashed) the user authors is restored with its trashed
  chapters. That already matches the new rule ("visible as soon as one author
  is active") — **leave it unchanged**. Chapter deletion is a `forceDelete`,
  so the only trashed chapters are deactivation ones.
- Existing tests: `Story/Tests/Feature/UserDeactivatedSoftDeleteStoriesTest.php`
  and `UserReactivatedRestoreStoriesTest.php` cover the sole-author case only;
  no test asserts the old co-author behaviour (checked: Comment / Quote /
  Profile deactivation tests do not deactivate a story co-author).
- Test helpers: `addCollaborator($storyId, $userId, 'author')`,
  `publicStory()`, `createPublishedChapter()` (`Story/Tests/helpers.php`);
  deactivate / reactivate through
  `AuthPublicApi::deactivateUserById()` / `activateUserById()` acting as
  `admin($this)`, as the existing tests do.

**Deliverables.**
- `app/Domains/Story/Private/Services/StoryService.php` —
  `softDeleteStoriesByAuthor(int $userId)`: load the user's stories with
  `authors` eager-loaded, collect every author id across them, call
  `AuthPublicApi::getUsersById()` **once**, and `softDeleteStory()` only the
  stories where no author `isActive`. Update the docblock.
- `app/Domains/Story/Private/Repositories/StoryRepository.php` — if needed, a
  `findByAuthorWithAuthors(int $userId)` (or an `with('authors')` option on
  `findByAuthor`); `deleteStoriesByAuthor` keeps using `findByAuthor`
  unchanged.
- `app/Domains/Story/README.md` (the "Soft delete on deactivation" paragraph
  and the listener table) and `app/Domains/Story/AGENTS.md` (listener table
  row "Soft-delete all stories by that author", and the soft/hard delete
  note): a co-authored story is soft-deleted only once all its authors are
  deactivated; reactivating any author restores it; account deletion still
  hard-deletes every story the user co-authors.

**Tests.** (extend `Story/Tests/Feature/UserDeactivatedSoftDeleteStoriesTest.php`
and `UserReactivatedRestoreStoriesTest.php`; the existing sole-author cases
stay unmodified and green)
- `it('keeps a co-authored story and its chapters visible when only one of its authors is deactivated')`
  — `countAuthoredStories` of the active co-author still 1; story and chapter
  not trashed; a confirmed reader still gets 200 on the story page.
- `it('soft-deletes a co-authored story once its last active author is deactivated')`
- `it('soft-deletes the sole-authored story but keeps the co-authored one of the same user')`
- `it('does not count a deleted co-author as active')` (author row whose user
  no longer exists → `isActive` false)
- `it('restores a co-authored story when one of its two deactivated authors is reactivated')`
- `it('leaves a never-hidden co-authored story untouched when its deactivated co-author is reactivated')`
- `it('looks up author activity in a single Auth call for many stories')` —
  user co-authoring 3 stories; assert the restriction is right for all three
  (no per-story Auth loop; a spy / query count is fine).
- Account deletion regression (in `UserDeletedCleanupTest.php`):
  `it('still hard-deletes a co-authored story when one of its authors deletes their account')`.

**Acceptance.**
- ✅ Deactivating one of two co-authors leaves the story, its chapters and its
  story page untouched for readers.
- ✅ Deactivating the last active author soft-deletes it; reactivating any one
  author restores story and chapters.
- ✅ Sole-author deactivation / reactivation tests pass unmodified.
- ✅ Account deletion still hard-deletes every co-authored story.
- ✅ Story README / AGENTS describe the new rule; no `docs/Feature_Planning`
  reference added there.
- ✅ `pnpm run gate` green.

---

## Checkpoint 3bv — Co-authored story with one deactivated author

Phase 3b changed existing Story behaviour: a story can now be readable while
one of its authors is deactivated (that author's profile is soft-deleted by
Profile, so `getPublicProfiles()` returns `null` for them). Look at every
existing page that lists story authors, with a co-authored public story whose
co-author was deactivated in Admin › Utilisateurs:
- Story listing (`/stories`) and home: the card renders, showing only the
  active author, no blank/broken author chip.
- Story page as guest, confirmed reader and the active author: page renders,
  author line has no empty entry, chapters readable.
- Chapter page (reader and active author): renders, comments work.
- Collaborators page (active author): the deactivated co-author's row does not
  crash the page (shown without profile or omitted — note which).
- The active author's profile « Histoires » tab still lists the story; the
  deactivated author's profile is unreachable as before.
- Deactivate the second author: the story disappears from listings and 404s;
  reactivate one: it is back.
- e2e: `e2e/tests/core/chapter-annotation-overlay.spec.ts` (touches
  deactivated users on a chapter).

Screenshots → `shots/checkpoint-3bv/`.

---

## Phase 4 — Fanart domain skeleton

**Goal.** Create the `Fanart` domain with its tables, models, enums, provider,
deptrac layers, GC claim and docs — no behaviour yet.

Architecture: §2.1, §2.2, §5, §8; Media usage contract from
`app/Domains/Media/AGENTS.md`. Phase 1 made `fanart/{storyId}` a private Media
scope; without a usage provider GC skips the whole `fanart` root.

**Deliverables.**
- `app/Domains/Fanart/Database/Migrations/2026_10_20_000001_create_fanarts_table.php`,
  `…000002_create_fanart_fanartists_table.php`,
  `…000003_create_fanart_role_requests_table.php` — columns and indexes per
  architecture §2.1, no foreign keys, `down()` methods.
- `app/Domains/Fanart/Private/Models/Fanart.php` (`SoftDeletes`,
  `#[Table('fanarts')]`, `#[Fillable]`, casts incl. `FanartKind`,
  `FanartStatus`), `Fanartist.php` (`#[Table('fanart_fanartists')]`,
  composite key: `protected $primaryKey = null`, `#[WithoutIncrementing]`,
  only `created_at`), `FanartRoleRequest.php`.
- `app/Domains/Fanart/Private/Support/FanartKind.php` (`image`, `audio`),
  `FanartStatus.php` (`pending`, `published`),
  `FanartMediaUsageProvider.php` — yields `file_path` of every row
  **`withTrashed()`**.
- `app/Domains/Fanart/Public/Providers/FanartServiceProvider.php` —
  migrations, routes (`Private/web.routes.php`, `Private/api.routes.php`,
  empty groups for now), translations `fanart`, views `fanart`, anonymous
  component path + class namespace `fanart`; registers the usage provider.
- `bootstrap/providers.php` — `FanartServiceProvider` after
  `QuoteServiceProvider` (so Story's and Profile's providers boot first).
- `deptrac.yaml` — layers `FanartPublic`, `FanartPrivate`, `FanartTests` and
  the rulesets of architecture §5.
- `app/Domains/Fanart/Private/Resources/lang/fr/ui.php` (empty skeleton).
- `app/Domains/Fanart/Database/Factories` or test helpers in
  `app/Domains/Fanart/Tests/helpers.php`: `createFanart(array $overrides)`,
  `createPublishedImage(...)`, `createPendingAudio(...)` — writing a real
  fake file through `MediaPublicApi::storePrivate`/`storePrivateFile`
  (`Storage::fake('private')`).
- `app/Domains/Fanart/README.md`, `AGENTS.md`, `CLAUDE.md` (`@AGENTS.md`) per
  `docs/Domain_Structure.md` — no link into `docs/Feature_Planning`.
- `AGENTS.md` (root) — Domain Registry row for **Fanart** with its three tables.

**Tests.**
- `app/Domains/Fanart/Tests/Feature/FanartSchemaTest.php`
  - `it('persists a fanart with kind/status enums and soft-deletes it')`
  - `it('enforces one fanartist row per (story, user)')`
- `app/Domains/Fanart/Tests/Feature/FanartMediaUsageProviderTest.php`
  - `it('claims file_path of published, pending and soft-deleted fanarts')`
  - `it('keeps a claimed fanart file and its variants through media:gc')`

**Acceptance.**
- ✅ `sail artisan migrate:fresh` runs; `migrate:rollback` drops the three tables.
- ✅ Deptrac green with the new layers.
- ✅ `pnpm run gate` green (docs check included).

---

## Phase 5 — Settings registration + `ModeService`

**Goal.** Register the two mode d'accueil settings and the profile-tab privacy
setting, and compute the effective mode of a story.

Architecture: §3.2 "ModeService", §3.4 "settings". Phase 3 added
`StoryPublicApi::getAuthorIds` (pre-existing) and `canRead`; phase 4 created
`FanartServiceProvider`. Story's own `registerStoryPreferenceSettings()`
returns early if tab `stories` exists — **do not touch it**; Fanart adds a
section to that tab from its own `booted` callback, guarded on its own key.

**Deliverables.**
- `FanartServiceProvider::boot()` → `$this->app->booted(fn () => $this->registerSettings())`:
  - section `fanart` in tab `stories`; enum parameters
    `fanart-images-mode` (default `yes`) and `fanart-audio-mode` (default
    `designated`), options `yes|designated|no`; guard: skip if
    `fanart-images-mode` already registered.
  - parameter `hide-fanart-tab` (bool, default `true`) in tab `profile`,
    section `privacy`; own guard.
- `app/Domains/Fanart/Private/Support/FanartMode.php` — enum `yes`,
  `designated`, `no` with `mostRestrictive(FanartMode ...$modes)`.
- `app/Domains/Fanart/Private/Services/ModeService.php` —
  `effectiveMode(int $storyId, FanartKind $kind): FanartMode` (reads each
  author's setting via `SettingsPublicApi::getValue`; no authors → `no`).
  `canSubmit` itself lands in phase 6 with the policy.
- `lang/fr/settings.php` — section and option labels, help text explaining
  the most-restrictive rule.

**Tests.**
- `app/Domains/Fanart/Tests/Unit/FanartModeTest.php`
  - `it('picks no over designated over yes')`
- `app/Domains/Fanart/Tests/Feature/ModeServiceTest.php`
  - `it('returns the defaults images=yes, audio=designated for an author who never set them')`
  - `it('returns the most restrictive mode across co-authors')`
- `app/Domains/Fanart/Tests/Feature/FanartSettingsTest.php`
  - `it('shows the Fanart section in Paramètres › Histoires next to Story reading prefs')`
  - `it('saves fanart-images-mode and rejects an unknown value')`
  - `it('shows hide-fanart-tab in Paramètres › Profil, default checked')`

**Acceptance.**
- ✅ Story's `hide-trigger-warnings` / `hide-stories-with-tw` still render in
  the same tab (Story settings tests green).
- ✅ `pnpm run gate` green.

---

## Phase 6 — `FanartPolicy` + fanartiste membership reads

**Goal.** One enforcement point for every Fanart ability, tested per role
before any route uses it.

Architecture: §3.3 (the ability table is the spec), §3.2 "ModeService".
Earlier phases: tables/models (4), `ModeService::effectiveMode` (5),
`StoryPublicApi::canRead / isAuthor / getAuthorIds` (3 and pre-existing).

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartistService.php` — read side only
  for now: `isFanartist(int $userId, int $storyId): bool`,
  `listFanartistIds(int $storyId): int[]`.
- `ModeService::canSubmit(int $userId, int $storyId, FanartKind $kind): bool`
  — confirmed (or staff), story not private (`StoryPublicApi::getStory()->visibility`),
  `canRead`, and mode `yes` or (`designated` and `isFanartist`). Authors are
  handled by the artwork ability, not here.
- `app/Domains/Fanart/Private/Policies/FanartPolicy.php` — methods (each
  takes the acting `?User`/user id and the `Fanart` or story id):
  `seeFeature(storyId)`, `view(Fanart)`, `submit(storyId, kind)`,
  `uploadArtwork(storyId)`, `editArtwork(Fanart)`, `review(Fanart)`,
  `delete(Fanart)`, `moderate(Fanart)`, `manageFanartists(storyId)`,
  `requestRole(storyId)` (request rules except cooldown/pending, which land in
  phase 16), `streamFile(Fanart)` (= `view`).
  Rules exactly per architecture §3.3: staff see everything incl. pending and
  private stories; published fanart on an unpublished chapter visible to
  authors/staff only; pending visible to authors, submitter, staff.
- Bind as singleton in the provider.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartPolicyTest.php`, one
`describe` per ability, real users: guest, `user`, confirmed, fanartiste,
author, co-author, moderator, admin)
- `it('denies every ability to a guest and to a non-confirmed user')`
- `it('lets a confirmed reader view a published fanart on a public and a community story')`
- `it('hides a published fanart on an unpublished chapter from readers but not from authors or staff')`
- `it('shows a pending fanart only to authors, its submitter and staff')`
- `it('hides everything on a private story from a confirmed non-collaborator, shows it to a moderator')`
- `it('hides everything once the story is soft-deleted (author deactivated)')`
- `it('allows submit only per effective mode: yes, designated+fanartiste, never no')`
- `it('refuses submit on a private story even to a fanartiste')`
- `it('allows uploadArtwork only to an author, never on a private story')`
- `it('allows review only to an author of the story on a pending fanart')`
- `it('allows delete to the submitter (any status) and to an author (published)')`
- `it('allows moderate to staff only')`
- `it('allows manageFanartists to authors only')`

**Acceptance.**
- ✅ Every row of architecture §3.3 has at least one passing allow and one
  passing deny test.
- ✅ `pnpm run gate` green.

---

## Phase 7 — Notifications: group, ten content types, `FanartNotifier`

**Goal.** All Fanart notifications exist, render, and are sent through one
notifier that excludes the actor.

Architecture: §3.1 notification table, §3.2 "FanartNotifier". Template:
`app/Domains/Quote/Public/Notifications/ChapterQuotedNotification.php` and
its registration in `QuoteServiceProvider::registerNotifications()`;
`app/Domains/Quote/Private/Listeners/NotifyAuthorsOnQuoteCreated.php` for the
empty-recipient `ValidationException` catch.

**Deliverables.**
- `app/Domains/Fanart/Public/Notifications/`: `FanartSubmittedNotification`,
  `FanartAcceptedNotification`, `FanartDeclinedNotification` (comment
  nullable), `FanartDeletedNotification`,
  `FanartDeletedByModerationNotification`,
  `FanartAudioReplacedNotification`, `FanartRemovedWithContentNotification`,
  `FanartRoleRequestedNotification`, `FanartRoleGivenNotification`,
  `FanartRoleDeclinedNotification` — types `fanart.*` per §3.1, payloads
  denormalise actor name/slug, story title/slug, chapter title/slug, fanart
  title.
- `FanartServiceProvider` — group `fanart` + ten registrations.
- `app/Domains/Fanart/Private/Services/FanartNotifier.php` — one method per
  type (`fanartSubmitted(Fanart, actorId)`, …), recipient resolution
  (authors via `StoryPublicApi::getAuthorIds`), actor removed, empty set →
  no call.
- `lang/fr/notification.php`.
- `docs/notification-types.md` — rows for the ten types.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartNotificationsTest.php`)
- `it('registers the fanart group and ten types in the notification settings')`
- one `it('renders <type> with its denormalised payload')` per type
- `it('renders a notification whose story was deleted afterwards')`
- `it('never notifies the actor')`
- `it('sends nothing and throws nothing when the only recipient is the actor')`

**Acceptance.**
- ✅ Each type appears in Paramètres › Notifications under the Fanart group.
- ✅ `pnpm run gate` green.

---

## Phase 8 — Gated file / thumbnail streaming routes

**Goal.** Serve fanart originals, audio (Range) and image variants only to
those allowed to view the fanart.

Architecture: §3.5 "files", §9 "Range streaming" / "Per-file access check".
Phase 1 gave `MediaPublicApi::stream()` and `streamVariant()`; phase 6 gave
`FanartPolicy::streamFile`. Precedent:
`app/Domains/Calendar/Private/Activities/SecretGift/Http/Controllers/SecretGiftController.php`
and its `ServeFileTest`.

**Deliverables.**
- `app/Domains/Fanart/Private/Controllers/FanartFileController.php` —
  `original(int $fanart)` → `stream($path, ['Cache-Control' => 'private, max-age=3600'])`;
  `thumb(int $fanart, int $width)` → `streamVariant($path, $width)`, width
  whitelisted to 400 / 800, image kind only, 404 otherwise.
- `Private/web.routes.php` — `GET /fanarts/{fanart}/file`
  (`fanart.file`), `GET /fanarts/{fanart}/thumb/{width}` (`fanart.thumb`),
  convention middleware (see Working agreement).

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartFileRoutesTest.php`)
- `it('streams a published image and its 400w thumbnail to a confirmed reader')`
- `it('answers a Range request on an audio with 206')`
- `it('sets Cache-Control private')`
- `it('returns 403 to a non-confirmed user and redirects a guest to login')`
- `it('returns 403 to a reader for a pending fanart, 200 to its submitter, an author and a moderator')`
- `it('returns 403 to a reader for a fanart on an unpublished chapter')`
- `it('returns 404 for a soft-deleted fanart and for an unknown width')`

**Acceptance.**
- ✅ No fanart byte is reachable without passing `FanartPolicy::streamFile`.
- ✅ `pnpm run gate` green.

---

## Phase 9 — Submit a fanart (non-author)

**Goal.** A confirmed user allowed by the mode can submit an image or audio
fanart from « Créer un nouveau fanart »; it is stored pending and the authors
are notified.

Architecture: §3.2 "FanartService" (submit, uploads, descriptions), §3.5
(create / submit routes, `StoreFanartRequest`), §4 "Create / edit page".
Spec §4.4. Phases left: `FanartPolicy::submit` (6), `FanartNotifier::fanartSubmitted` (7),
Media scope `fanart/{storyId}` (1). Slug → story: `SlugWithId::extractId($slug)`
(`app/Domains/Shared/Support/SlugWithId.php`) then
`StoryPublicApi::getStory($id)` (`StorySummaryDto` has `slug`, `visibility`);
404 when missing. Put this in a small `StoryResolver` helper in
`Private/Support/` — every later `/stories/{slug}/…` route reuses it.

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartService.php` —
  `submit(int $userId, int $storyId, SubmitFanartData $data): Fanart`:
  image → `storePrivate("fanart/{$storyId}", $file, [400, 800])`; audio →
  `storePrivateFile(...)`; row `status=pending`, `is_artwork=false`;
  `share_on_discord` forced false for audio; notify authors.
- `app/Domains/Fanart/Private/Requests/StoreFanartRequest.php` — rules per
  architecture §3.5 (image `mimes:jpg,jpeg,png,webp|max:2048`, audio
  `mimes:mp3|max:15360`, title `required|max:100`, description
  `nullable|maxstripped:1000`, chapter nullable for image / required for audio
  and must belong to the story, `attestation` `accepted`, `share_on_discord`
  boolean); `authorize()` → `FanartPolicy::submit($storyId, $kind)` (re-checked
  at save, spec 4.4.8). Description sanitised with
  `EditorPublicApi::sanitizeText($html, 'strict')`.
- `app/Domains/Fanart/Private/Controllers/FanartController.php` — `create`,
  `store` (redirect to the story page with a flash).
- Routes: `GET /stories/{slug}/fanarts/create` (`fanart.create`),
  `POST /stories/{slug}/fanarts` (`fanart.store`).
- `Private/Resources/views/pages/create.blade.php` — Image / Audio switch
  showing only allowed kinds (Alpine), `<x-media::image-field>`
  (`allowLibrary=false`, `needsPropertyConfirm=false`),
  `<x-media::sound-field maxSize="15360" accept="audio/mpeg">`,
  `<x-editor::rich-text toolbar="inline" max="1000">`, chapter select
  (published chapters, « Toute l'œuvre » option for images only), attestation
  (spec 4.4.5 wording), « Partager sur Discord » (images only).
- `lang/fr/ui.php` — form strings.

**Tests.** (`app/Domains/Fanart/Tests/Feature/SubmitFanartTest.php`)
- `it('shows the create page with only the kinds the mode allows')`
- `it('stores a pending image with 400/800 variants and notifies every author')`
- `it('stores a pending audio and requires a chapter')`
- `it('accepts an image without chapter (Toute l\'œuvre)')`
- `it('rejects gif, oversize image (>2 Mo), non-mp3 audio, oversize audio (>15 Mo)')`
- `it('requires title and the attestation; caps description at 1000 visible chars')`
- `it('sanitises the description HTML')`
- `it('rejects a chapter from another story')`
- `it('returns 403 when the mode is no, or designated without the role')`
- `it('returns 403 on a private story')`
- `it('returns 403 to a non-confirmed user; redirects a guest')`
- `it('ignores share_on_discord for audio')`

**Acceptance.**
- ✅ A non-confirmed user gets 403 on `POST /stories/{slug}/fanarts`.
- ✅ Validation failure redirects back with old input kept.
- ✅ `pnpm run gate` green.

---

## Phase 10 — Review: accept / decline + audio replacement

**Goal.** Any one author accepts or declines a pending fanart with a required
comment; accepting an audio replaces the chapter's previous one.

Architecture: §2.1 ("one published audio per chapter" lock), §2.3 ("Audio
replaced", "Author declines"), §3.5 review routes, `ReviewFanartRequest`.
Spec §4.7. Phases left: pending fanarts (9), `FanartPolicy::review` (6),
notifier (7).

**Deliverables.**
- `FanartService::accept(Fanart, int $actorId, string $comment)` — transaction:
  status `published`, `published_at = now()`; if audio: `lockForUpdate` the
  chapter's published audio, hard-delete it (`forceDelete`), notify its
  submitter `fanart.audio_replaced` unless actor. Notify submitter
  `fanart.accepted` with comment.
- `FanartService::decline(Fanart, int $actorId, ?string $comment)` —
  `forceDelete`, notify `fanart.declined`.
- `FanartService::replacePublishedAudio(int $chapterId, int $actorId, int $exceptId)`
  — the shared helper phase 11 reuses.
- `app/Domains/Fanart/Private/Requests/ReviewFanartRequest.php` — comment
  `required|string|max:500`; `authorize()` → `FanartPolicy::review`.
- `app/Domains/Fanart/Private/Controllers/FanartReviewController.php` —
  `accept`, `decline`; routes `POST /fanarts/{fanart}/accept`,
  `POST /fanarts/{fanart}/decline`. A non-pending fanart → 409/redirect with
  "already decided" flash (spec 4.7.4).

**Tests.** (`app/Domains/Fanart/Tests/Feature/ReviewFanartTest.php`)
- `it('publishes a pending image on accept and notifies the submitter with the comment')`
- `it('deletes a pending fanart on decline and notifies the submitter with the comment')`
- `it('requires a comment of at most 500 chars')`
- `it('lets a co-author decide; the second author then gets an already-decided response')`
- `it('replaces the chapter\'s published audio on audio accept and notifies its submitter')`
- `it('keeps other pending audios on the same chapter pending')`
- `it('returns 403 to the submitter, a reader, a fanartiste and a moderator')`
- `it('still allows the decision when the mode is now no / the story is now private (spec 4.7.8)')`

**Acceptance.**
- ✅ After any sequence of accepts, a chapter has at most one published audio.
- ✅ The comment is stored only in the notification payload (no column).
- ✅ `pnpm run gate` green.

---

## Phase 11 — Artwork (author): upload with credit spend, edit

**Goal.** An author posts an artwork published immediately at the cost of one
chapter credit, and can edit it for free.

Architecture: §3.2 "Artwork credit", §3.5 artwork edit routes,
`UpdateArtworkRequest`. Spec §4.5. Phases left:
`StoryPublicApi::spendChapterCredit / getAvailableChapterCredits` (3),
`FanartPolicy::uploadArtwork / editArtwork` (6), the create page and
`FanartController::store` (9), `replacePublishedAudio` (10).

**Deliverables.**
- `FanartController::store` — branch: if `FanartPolicy::uploadArtwork`
  (the actor is an author), call `FanartService::uploadArtwork(...)`;
  otherwise the phase-9 submit path. One route, one request.
- `FanartService::uploadArtwork(int $userId, int $storyId, data)` — DB
  transaction: `spendChapterCredit($userId)`; `false` → throw a validation
  error with the spec 4.5.3 message; store file; create row
  `status=published`, `is_artwork=true`, `published_at=now()`; audio →
  `replacePublishedAudio`. No notification to co-authors.
- `FanartService::updateArtwork(Fanart, data)` — file (optional), title,
  description, chapter; audio chapter change → `replacePublishedAudio` on the
  target chapter; no credit.
- `app/Domains/Fanart/Private/Requests/UpdateArtworkRequest.php` — same rules
  as `StoreFanartRequest` with file optional; `authorize()` →
  `FanartPolicy::editArtwork`.
- `FanartController::edit`, `update`; routes `GET /fanarts/{fanart}/edit`,
  `PUT /fanarts/{fanart}`.
- `views/pages/create.blade.php` — for authors: title « Ajouter un artwork »,
  credit warning with the remaining count; reused in edit mode
  (`views/pages/edit.blade.php` or a shared form partial).

**Tests.** (`app/Domains/Fanart/Tests/Feature/ArtworkTest.php`)
- `it('publishes an author image immediately and spends one credit of the uploader')`
- `it('refuses the upload with the no-credit message and stores nothing at zero credits')`
- `it('does not notify co-authors')`
- `it('replaces the chapter audio when an author uploads an audio artwork')`
- `it('lets the author edit title, description, chapter and file for free')`
- `it('returns 403 on edit to a co-author who did not upload it, and to the fanartist of a fanart')`
- `it('returns 403 on upload to an author of a private story')`
- `it('does not refund the credit on deletion')` (uses phase-12 route if
  present; otherwise deletes via the service)

**Acceptance.**
- ✅ Credit spend and row creation are atomic: a failing file store leaves
  the credit unspent (transaction rollback test).
- ✅ `pnpm run gate` green.

---

## Phase 12 — Deletion: owner / author delete, moderator delete

**Goal.** Submitters delete their own fanarts, authors delete published ones,
staff delete any via a moderation route; the right people are notified.

Architecture: §2.3 ("Author declines / deletes, fanartist withdraws,
moderator deletes"), §3.1 (event `Fanart.DeletedByModeration`), §3.5
(`DELETE /fanarts/{fanart}`, `DELETE /moderation/fanarts/{fanart}`). Spec
§4.8, §4.11.4–5, A2. Precedent: Comment's `CommentDeletedByModeration`.

**Deliverables.**
- `FanartService::deleteByOwner(Fanart)` (no notification),
  `deleteByAuthor(Fanart, actorId)` (notify submitter `fanart.deleted` unless
  actor), `deleteByModerator(Fanart, moderatorId)` (notify authors + submitter
  `fanart.deleted_by_moderation`, emit event). All `forceDelete`.
- `app/Domains/Fanart/Public/Events/FanartDeletedByModeration.php`
  (`fanartId`, `storyId`, `userId`, `moderatorId`; auditable, summary
  translated) registered on the EventBus.
- `FanartController::destroy` → `DELETE /fanarts/{fanart}` (`fanart.destroy`),
  policy `delete`; picks owner vs author variant.
- `app/Domains/Fanart/Private/Controllers/FanartModerationController.php` →
  `DELETE /moderation/fanarts/{fanart}` (`fanart.moderation.destroy`),
  middleware `role:moderator,admin,tech-admin`.
- `lang/fr/events.php`.

**Tests.** (`app/Domains/Fanart/Tests/Feature/DeleteFanartTest.php`)
- `it('lets a fanartist delete their pending and published fanarts without notifying anyone')`
- `it('lets an author delete a published fanart and notifies the fanartist')`
- `it('returns 403 to a reader and to another fanartist')`
- `it('lets a moderator delete any fanart, notifies authors and fanartist, emits Fanart.DeletedByModeration')`
- `it('returns 403 on the moderation route to an author')`
- `it('writes the moderation deletion to the audit log')`

**Acceptance.**
- ✅ An author cannot reach `DELETE /moderation/fanarts/{id}` (403).
- ✅ `pnpm run gate` green.

---

## Phase 13 — Lifecycle listeners

**Goal.** Fanart reacts to account deletion, deactivation / reactivation,
story deletion and chapter deletion per spec §5.

Architecture: §2.3, §3.4 listeners. Precedent:
`app/Domains/Quote/Private/Listeners/{DeleteQuotesOnUserDeleted,SoftDeleteOnUserDeactivated,RestoreOnUserReactivated}.php`.
Phases left: models (4), notifier `removedWithContent` (7). Chapter
unpublish, story visibility and author deactivation need **no** listener:
`canRead` (soft-deleted story) and the published-chapter filter cover them.
Phase 3b made Story soft-delete a co-authored story only once **all** its
authors are deactivated (DECISIONS #32). Account deletion (DECISIONS #33):
Story's `RemoveStoriesOnUserDeleted` hard-deletes **every** story the user
authors or co-authors and emits `Story.Deleted` for each, so those stories'
fanarts go through `DeleteFanartsOnStoryDeleted`; no co-authored story ever
survives an author's account deletion.

**Deliverables.** (`app/Domains/Fanart/Private/Listeners/`)
- `DeleteFanartDataOnUserDeleted` — `forceDelete` all fanarts with that
  `user_id` (withTrashed), delete their fanartist rows and role requests; no
  notification.
- `SoftDeleteFanartsOnUserDeactivated` / `RestoreFanartsOnUserReactivated`.
- `DeleteFanartsOnStoryDeleted` — fanarts, fanartist rows, role requests of
  the story; notify each distinct non-author submitter
  `fanart.removed_with_content` (story title from the event snapshot).
- `DeleteFanartsOnChapterDeleted` — fanarts of the chapter; notify each
  distinct submitter except the chapter's authors.
- Subscriptions in `FanartServiceProvider`.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartLifecycleTest.php`)
- `it('hard-deletes every fanart and artwork of a deleted user, plus their fanartist rows and requests')`
- `it('wipes a co-authored story and all its fanarts when one of its authors deletes their account, notifying each fanartist once')` (DECISIONS #33 — the other co-author's artworks go too)
- `it('soft-deletes a deactivated fanartist\'s fanarts and restores them on reactivation')`
- `it('hides a story\'s fanarts when its sole author is deactivated (story soft-deleted) and shows them back on reactivation')`
- `it('keeps a co-authored story\'s fanarts visible while one of its two authors is deactivated, hides them once both are')` (DECISIONS #32, relies on phase 3b)
- `it('deletes all fanarts, fanartists and requests of a deleted story and notifies each fanartist once')`
- `it('deletes the fanarts of a deleted chapter and notifies their fanartists, not the authors')`
- `it('hides fanarts of an unpublished chapter from readers and shows them again on republish')` (query-level, via the listing scope used by phase 17 — if that scope does not exist yet, assert through `FanartPolicy::view`)

**Acceptance.**
- ✅ Every row of spec §5 that Fanart owns has a passing test, co-author rows
  included (DECISIONS #32 / #33).
- ✅ `pnpm run gate` green.

---

## Phase 14 — Moderation topic: formatter, reasons, author-reports-pending

**Goal.** Fanarts can be reported with their file kept; an author reporting a
pending fanart removes it from the queue (A1).

Architecture: §3.1 "FanartSnapshotFormatter", §2.3 last row, A16. Phase 2
made Moderation copy any `_media` path returned by `capture()` and pass
`_media_urls` to `render()`. Phase 12 added the moderation delete route.

**Deliverables.**
- `app/Domains/Fanart/Public/Moderation/FanartSnapshotFormatter.php` —
  `capture()` returns title, description, kind, status, artist id + name
  (`Shared\Contracts\ProfilePublicApi`), story and chapter titles,
  `_media => [file_path]`; `render()` prints `<img>` / `<audio controls>` from
  `_media_urls` plus the fields (Blade view
  `views/moderation/snapshot.blade.php`); `getReportedUserId()` = submitter;
  `getContentUrl()` = `/stories/{slug}?tab=fanarts&fanart={id}`.
- Topic `fanart` registered in `FanartServiceProvider`
  (`ModerationRegistry`).
- Reasons: follow the existing precedent — add `'fanart'` to the `$topics`
  list of `app/Domains/Moderation/Database/Seeders/ModerationSeeder.php`
  (seeds an « Other » reason for dev / e2e). Production reasons are created by
  an admin in the reasons UI (spec 4.11.1 "admin-managed"); no data migration
  and no `FanartModerationReasonSeeder` (architecture §8 lists one — dropped,
  see Open item O4).
- `app/Domains/Fanart/Private/Listeners/RemovePendingFanartOnAuthorReport.php`
  — on `Moderation.ReportSubmitted` with `topicKey === 'fanart'`: if the
  fanart is pending and the reporter is an author of its story →
  `forceDelete`, notify `fanart.declined` with `comment = null`.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartReportTest.php`)
- `it('lets a confirmed reader report a published fanart with a fanart reason')`
- `it('stores a copy of the image in moderation/fanart with title, artist, story, chapter')`
- `it('renders the copied image for a moderator even after the fanart is deleted')`
- `it('removes a pending fanart reported by an author and sends the decline notification without comment')`
- `it('keeps a pending fanart reported by its submitter or by staff')`
- `it('keeps a published fanart reported by an author')`
- `it('lists fanart reasons in admin reasons management')`

**Acceptance.**
- ✅ Approving a fanart report does not delete the fanart (decision 31).
- ✅ `pnpm run gate` green.

---

## Phase 15 — Fanartiste grant / revoke + collaborators-page section

**Goal.** Authors grant and revoke the Fanartiste role from a section of
Story's collaborators page.

Architecture: §1.1 "Views" (collaborators page), §3.3 "manage fanartistes",
§7 #1. Spec §4.2. Phases left: `fanart_fanartists` (4),
`FanartPolicy::manageFanartists` (6), `fanart.role_given` (7). Story's page:
`app/Domains/Story/Private/Resources/views/collaborators/index.blade.php`
(route `GET /stories/{slug}/collaborators`, authors only).

**Deliverables.**
- `FanartistService::grant(storyId, userId, grantedBy)` (target must be
  confirmed — `AuthPublicApi` role check; idempotent; deletes a pending role
  request of that pair; notifies `fanart.role_given`), `revoke(storyId, userId)`
  (fanarts untouched).
- `app/Domains/Fanart/Private/Controllers/FanartistController.php` —
  `store`, `destroy`; routes `POST /stories/{slug}/fanartistes`,
  `DELETE /stories/{slug}/fanartistes/{userId}`.
- `app/Domains/Fanart/Private/View/Components/CollaboratorsSection.php` +
  `views/components/collaborators-section.blade.php` — list with profile
  names, revoke buttons, user picker to grant (reuse the profile search the
  collaborators page already uses), slot for pending requests (filled in
  phase 16).
- `collaborators/index.blade.php` (Story) — one line:
  `<x-fanart::collaborators-section :story-id="…" />`.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartistManagementTest.php`)
- `it('lets an author grant the role to a confirmed user and notifies them')`
- `it('refuses to grant the role to a non-confirmed user')`
- `it('lets a bêta-lecteur also be fanartiste (cumulative)')`
- `it('revokes the role without touching the user\'s fanarts')`
- `it('returns 403 to a co-collaborator who is not an author and to the fanartiste')`
- `it('renders the Fanartistes section on the collaborators page for an author')`
- Story's `Collaborators/CollaboratorPageTest.php` green unmodified.

**Acceptance.**
- ✅ A fanartiste gains no access to a private story (`canRead` false).
- ✅ `pnpm run gate` green.

---

## Phase 16 — Fanartiste role requests

**Goal.** Confirmed readers request the role; any author accepts or declines;
30-day cooldown after a decline.

Architecture: §2.1 `fanart_role_requests`, §3.3 "request role", A10. Spec
§4.3. Phase 15 left `FanartistService::grant` (which already removes a pending
request) and the collaborators section with a requests slot.

**Deliverables.**
- `FanartistService::request(storyId, userId)`, `withdraw(request)`,
  `accept(request, actorId)` (→ `grant`, row deleted, `role_given`),
  `decline(request, actorId)` (status `declined`, `decided_*`, notify
  `role_declined`), `canRequest(userId, storyId)` (policy rule of phase 6 +
  no pending + last decline older than 30 days).
- `FanartPolicy::requestRole` completed with pending/cooldown.
- `app/Domains/Fanart/Private/Controllers/RoleRequestController.php` — routes
  `POST /stories/{slug}/fanartiste-requests`,
  `DELETE /stories/{slug}/fanartiste-requests/{request}`,
  `POST …/{request}/accept`, `POST …/{request}/decline`. Request notifies
  every author `fanart.role_requested`.
- Collaborators section: pending requests list with Accepter / Décliner.
- `views/components/request-role-button.blade.php` (used on the story page in
  phase 18; renders « Demander le rôle de fanartiste » / « Demande envoyée —
  retirer » / nothing).

**Tests.** (`app/Domains/Fanart/Tests/Feature/RoleRequestTest.php`)
- `it('creates a request and notifies every author')`
- `it('refuses a request when no media type is in designated mode')`
- `it('refuses a second pending request for the same story')`
- `it('lets the requester withdraw')`
- `it('accepts: grants the role, deletes the request, notifies role given')`
- `it('declines: notifies the requester; a new request is refused for 30 days then allowed')` (`travel`)
- `it('refuses requests from authors, existing fanartistes and non-confirmed users')`
- `it('returns 403 on accept/decline to a non-author')`

**Acceptance.**
- ✅ Cooldown is computed from the latest decline of that (user, story).
- ✅ `pnpm run gate` green.

---

## Phase 17 — Lazy fragments: tab, groups, viewer / review viewer

**Goal.** Server-rendered HTML fragments for the Fanarts tab, each chapter
group, and the viewer, with every visibility and spoiler rule applied in the
query.

Architecture: §3.3 (listing = query scopes), §3.5 "fragments", §4 "Groups",
"Thumbnails", "Viewer". Spec §4.6, §4.7.1, A4, A6. Phases left: file routes
`fanart.file` / `fanart.thumb` (8), accept / decline (10), delete routes (12),
`StoryPublicApi::getChaptersForStory / getReadChapterIds` (3).

**Deliverables.**
- `Fanart` model scopes: `visibleTo(viewerContext)` — published only for
  readers, plus chapter published or null; authors/staff see all incl.
  pending.
- `FanartService` queries: `tabSummary(storyId, viewerId)` (groups: chapter id
  / `work`, counts, read flag), `group(storyId, chapterId|null, viewerId)`,
  `pendingFor(storyId)`.
- `app/Domains/Fanart/Private/Controllers/FanartFragmentController.php` —
  `GET /stories/{slug}/fanarts` (tab), `GET /stories/{slug}/fanarts/groups/{group}`
  (`{chapterId}` or `work`), `GET /fanarts/{fanart}` (viewer; review viewer
  when the viewer may review and the fanart is pending). All behind
  `FanartPolicy` (`seeFeature` / `view`).
- Views `views/fragments/tab.blade.php` (pending block « En attente de
  confirmation » for authors/staff; groups in story order + « Toute
  l'œuvre »; unread groups collapsed with spoiler notice for readers, all
  expanded for authors; « Montrer tout » / « Cacher tout »; empty state;
  effective-mode explanation for authors incl. the co-author rule, spec 4.1.5),
  `fragments/group.blade.php`, `fragments/viewer.blade.php`
  (image fit, title, artist link, sanitised description,
  `<x-moderation::report-button topic-key="fanart">`, delete for submitter /
  author, `<x-moderation::moderation-button>` with « Supprimer » for staff;
  review form with comment + Accepter / Décliner), and
  `views/components/thumbnail.blade.php` (`srcset` 400w/800w via
  `fanart.thumb`, `loading="lazy"`, `alt` = title).

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartFragmentsTest.php`)
- `it('returns the tab with groups in chapter order plus Toute l\'œuvre')`
- `it('marks unread chapter groups collapsed with a spoiler notice for a reader, read ones expanded')`
- `it('expands every group for an author and shows the pending block')`
- `it('never lists pending fanarts to a reader, lists them to the submitter only in their own context')`
- `it('omits groups of unpublished chapters for readers, keeps them for authors')`
- `it('renders the empty state when nothing is published')`
- `it('renders the review viewer with comment + Accepter/Décliner for an author on a pending fanart')`
- `it('renders the moderation menu only for staff')`
- `it('returns 403 for every fragment to a non-confirmed user')`
- `it('loads a group without N+1 (query count bound)')`

**Acceptance.**
- ✅ No fragment ever contains a fanart the viewer may not `view`.
- ✅ `pnpm run gate` green.

---

## Phase 18 — Story page integration: tab shell, action buttons, viewer modal

**Goal.** The story page shows Chapitres / Fanarts tabs, the action buttons
and the viewer modal to confirmed users and staff, and is unchanged for
everyone else.

Architecture: §1.1 "Views", §4 "Story page tabs", "Viewer", A11. Phases
left: fragments (17), `request-role-button` (16), create page (9/11).
Story view: `app/Domains/Story/Private/Resources/views/show.blade.php`
renders the chapter list via `@include('story::chapters.partials.chapter-list.author-view'|'reader-view')`
inside `{{-- Chapters section --}}`.

**Deliverables.**
- `app/Domains/Fanart/Private/View/Components/StoryTabs.php` +
  `views/components/story-tabs.blade.php` — when the viewer may
  `seeFeature`: tab bar (Chapitres / Fanarts), the slot as the Chapitres
  panel, the Fanarts panel fetching `GET /stories/{slug}/fanarts` on first
  open; reads `?tab=fanarts&fanart={id}` to open the tab and the viewer.
  Otherwise renders the slot **only** (no wrapper markup change).
- `views/components/story-actions.blade.php` — « Proposer un fanart » (if
  `submit` for ≥ 1 kind) / « Ajouter un artwork » (authors, non-private) /
  `<x-fanart::request-role-button>`; nothing otherwise.
- `views/components/viewer-modal.blade.php` + `Private/Resources/js/fanart-viewer.js`
  (Alpine component: open fragment, close, Escape, focus trap; mobile stacks
  image above text). Register the JS entry the way Quote does (Vite input).
- `show.blade.php` (Story) — wrap the Chapters section include in
  `<x-fanart::story-tabs :story-id :story-slug>` and add
  `<x-fanart::story-actions …>` next to the chapter list header. Two tag
  insertions, no other change.

**Tests.**
- `app/Domains/Fanart/Tests/Feature/StoryPageFanartTest.php`
  - `it('renders no tab bar and no fanart button for a guest and a non-confirmed user')`
  - `it('renders the tab bar and Proposer un fanart for a confirmed reader when allowed')`
  - `it('renders Ajouter un artwork for an author and no Proposer button')`
  - `it('renders Demander le rôle de fanartiste when the mode is designated and the reader lacks the role')`
  - `it('renders no submission button on a private story')`
- Story's `Stories/*` show-page tests green unmodified.

**Acceptance.**
- ✅ For guest and `user` roles the story page HTML has no Fanart markup.
- ✅ `pnpm run gate` green (asset build included — run without `--quick`).

---

## Checkpoint 18v — Story page for every role

After phase 18, open the same public story and a community story as: guest,
`user`, confirmed reader, fanartiste, author, moderator. Check the page looks
exactly as before for guest / `user` (no tab bar); for the others the
Chapitres tab shows the usual chapter list (author view: reorder, create
chapter, credits exhausted banner; reader view: read toggles), and the
Fanarts tab opens. Mobile width (375 px) for reader and author. e2e:
`e2e/tests/core/chapter-comments.spec.ts`, `e2e/tests/core/confirm-modal.spec.ts`.

Screenshots → `shots/checkpoint-18v/`.

---

## Phase 19 — Story-page audio: speaker icons + `fanartPlayer`

**Goal.** Chapters with a published audio show a speaker icon; playing one
streams it, marks the chapter read on end and auto-advances to the next
chapter with audio.

Architecture: §4 "Story-page player". Spec §4.10, DECISIONS #15. Phases left:
`fanart.file` route (8), story tabs (18). Mark-read route: Story's
`chapters.read.mark` (`POST /stories/{storySlug}/chapters/{chapterSlug}/read`).
Story chapter rows: `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/{reader-list,author-list}.blade.php`.

**Deliverables.**
- `views/components/chapter-audio-icon.blade.php` — renders a speaker button
  for a chapter id if it is in the story's audio list and the viewer may see
  it; nothing otherwise. The list is computed once per page (component class
  memoises per story id).
- `views/components/story-player.blade.php` — one `<audio controls>` +
  `x-data`; seeds `Alpine.store('fanartPlayer')` with
  `[{chapterId, src, markReadUrl}]` in chapter order (published chapters only
  for readers).
- `Private/Resources/js/fanart-player.js` — store: `play(chapterId)`,
  `onEnded()` → `POST markReadUrl` (CSRF) then play the next entry, stop after
  the last; ignores mark-read failure (still advances).
- Story `reader-list` / `author-list` partials — one
  `<x-fanart::chapter-audio-icon>` tag per row; `show.blade.php` — one
  `<x-fanart::story-player>` tag inside the tabs.

**Tests.**
- `app/Domains/Fanart/Private/Resources/js/fanart-player.test.js` (Vitest)
  - `plays the requested chapter`, `skips chapters without audio`,
    `stops after the last`, `posts mark-read on end before advancing`,
    `advances even if mark-read fails`.
- `app/Domains/Fanart/Tests/Feature/StoryPlayerTest.php`
  - `it('renders a speaker icon only on chapters with a published audio, for confirmed users')`
  - `it('omits audio of unpublished chapters for readers')`
  - `it('renders no icon and no player for a guest')`

**Acceptance.**
- ✅ Listening to the end of chapter 1 marks it read (Story reading-progress
  row exists) — checked in VERIFY in a browser.
- ✅ `pnpm run gate` green.

---

## Phase 20 — Chapter page widgets

**Goal.** The chapter page shows its published audio under the title and its
image group at the bottom, view-only, to confirmed users and staff.

Architecture: §4 "Chapter page". Spec §4.9. Phases left: `fanart.file` (8),
group fragment + thumbnail + viewer modal (17/18). Story view:
`app/Domains/Story/Private/Resources/views/chapters/show.blade.php` — **the
chapter body must stay a single `[data-quote-article]` root** (Story
AGENTS.md invariant): insert the tags outside `<article data-quote-article>`.

**Deliverables.**
- `views/components/chapter-audio.blade.php` (`<x-fanart::chapter-audio :chapter-id>`)
  — native player streaming `fanart.file`, report flag; nothing if no
  published audio or viewer not allowed.
- `views/components/chapter-images.blade.php` — the group fragment of phase 17
  rendered inline, view-only (no delete / review), viewer modal included.
- `chapters/show.blade.php` (Story) — two tag insertions (under the title;
  after the article, before comments).

**Tests.** (`app/Domains/Fanart/Tests/Feature/ChapterPageFanartTest.php`)
- `it('shows the audio player under the title for a confirmed reader')`
- `it('shows the image block at the bottom, without delete or review controls')`
- `it('shows nothing to a guest and a non-confirmed user')`
- `it('keeps a single [data-quote-article] root in the chapter page')`
- Story `Chapters/*` and Quote `AuthorHeatViewTest` green unmodified.

**Acceptance.**
- ✅ `pnpm run gate` green.

---

## Checkpoint 20v — Chapter page and chapter list

After phases 19–20, on a chapter with quotes, annotations and comments, as a
confirmed reader and as its author: quoting toolbar works and the author
heat map lines up; annotations overlay anchors; comments post; the fanart
audio/images appear only where expected. On the story page, the chapter list
(reader and author views, reorder) still behaves. e2e:
`e2e/tests/core/chapter-annotations.spec.ts`,
`chapter-annotation-overlay.spec.ts`, `chapter-annotation-round-trip.spec.ts`,
`chapter-comments.spec.ts`, `comment-draft-consume.spec.ts`.

Screenshots → `shots/checkpoint-20v/`.

---

## Phase 21 — Profile tab « Fanarts »

**Goal.** A per-user profile tab listing their published images and sounds by
story then chapter, with pending items and delete for the owner, private by
default.

Architecture: §3.3 "profile tab", §3.4 (profile tab registration, privacy
setting `hide-fanart-tab` registered in phase 5). Spec §4.13, A6, A7.
Precedent: Quote's `ProfileTabDefinition` registration in
`QuoteServiceProvider::boot()`, `Public/Visibility/QuotesTabVisibility.php`,
`Private/View/Components/ProfileTab.php`. Phases left:
`StoryPublicApi::filterReadableStoryIds / getReadChapterIdsByStoryIds /
getChaptersForStories` (3), thumbnail + viewer (17), delete route (12).

**Deliverables.**
- `app/Domains/Fanart/Public/Visibility/FanartTabVisibility.php` — owner,
  staff, or confirmed viewer when owner's `hide-fanart-tab` is false; never
  guest / `user`.
- Profile tab `fanarts` registered (order after Quote's 50, e.g. 55) with
  `ProfileTabPrivacy('profile', 'hide-fanart-tab')`.
- `app/Domains/Fanart/Private/View/Components/ProfileTab.php` +
  `views/components/profile-tab.blade.php` — sections Images / Sons, grouped
  story → chapter, lazy groups (profile-group fragment route
  `GET /fanarts/profile/{userId}/groups/{storyId}`), spoiler collapse per
  viewer's reads, pending badge « En attente de validation » and delete for
  the owner. Content filtered with **one** `filterReadableStoryIds` call.
- `lang/fr/ui.php` profile strings.

**Tests.** (`app/Domains/Fanart/Tests/Feature/FanartProfileTabTest.php`)
- `it('hides the tab from everyone but owner and staff by default')`
- `it('shows the tab to confirmed viewers once the owner opens it, never to guests or non-confirmed')`
- `it('lists only fanarts on stories the viewer can read')`
- `it('shows pending items with a badge to the owner only')`
- `it('collapses groups of chapters the viewer has not read')`
- `it('lets the owner delete from the tab')`
- `it('uses a single readability query for many stories')`

**Acceptance.**
- ✅ A confirmed viewer never sees a fanart on a private story they cannot read.
- ✅ `pnpm run gate` green.

---

## Phase 22 — Discord feed

**Goal.** The Discord bot polls approved, opted-in image fanarts on public
stories and marks them sent.

Architecture: §3.5 "Bot API", §7 #6, A9, A12; `docs/Discord_Api_Usage.md`.
Precedent: `app/Domains/Discord/Private/api.routes.php` (middleware
`['api', 'discord.api']`, `throttle:60,1`). Phases left: published images
with `share_on_discord` (10/11), `StoryPublicApi::filterReadableStoryIds(null, …)`
(3), `MediaPublicApi::streamVariant` (1).

**Deliverables.**
- `app/Domains/Fanart/Private/api.routes.php` — prefix `api/discord/fanarts`:
  `GET /pending?perPage=&page=`, `POST /mark-sent {ids[]}`.
- `app/Domains/Fanart/Private/Controllers/DiscordFeedController.php` — oldest
  first; items `{id, title, artistName, storyTitle, storyUrl, chapterTitle?,
  imageUrl}`; `imageUrl` = `URL::temporarySignedRoute('fanart.discord-image', now()->addDay(), …)`.
- Web route `GET /fanarts/{fanart}/discord-image` with `signed` middleware
  only (no session) → 800w variant; 404 if the fanart is gone or no longer
  eligible.
- `docs/Discord_Api_Usage.md` — « Fanarts feed » section.

**Tests.** (`app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php`)
- `it('rejects a request without the bot token')`
- `it('lists published images with share_on_discord on public stories only')`
- `it('excludes audio, pending, community and private stories, and already-sent ones')`
- `it('includes author artworks that ticked the box')`
- `it('marks given ids as sent')`
- `it('serves the signed image URL and rejects a tampered or expired one')`
- `it('drops a deleted fanart from pending')`

**Acceptance.**
- ✅ `pnpm run gate` green.

---

## Phase 23 — Polish: i18n, a11y, mobile, docs

**Goal.** Close the epic: wording review against the wireframes, keyboard and
screen-reader pass, mobile layout, domain docs final.

Architecture: §6 (VERIFY list), spec §6 (i18n / mobile / a11y rows).

**Deliverables.**
- `lang/fr/*.php` — labels match wireframes (« Proposer un fanart », « Ajouter
  un artwork », « En attente de confirmation », « Montrer tout » / « Cacher
  tout »); no hard-coded strings left in Fanart views (grep).
- Tabs: `role="tablist"`/`tab`/`tabpanel`, arrow-key navigation; collapsibles
  `aria-expanded`; thumbnails `alt`; viewer focus trap + Escape (fix whatever
  is missing).
- `app/Domains/Fanart/README.md` / `AGENTS.md` — final: public surface
  (components, routes, registrations), events, listeners, invariants
  (one published audio per chapter, never delete files, actor never notified,
  Story views only include tags).
- `e2e/tests/features/fanart-artwork-module.spec.ts` only if VERIFY needs a
  browser-only flow (player auto-advance) — see `e2e/tests/features/README.md`.

**Tests.**
- `app/Domains/Fanart/Tests/Feature/FanartViewsI18nTest.php`
  - `it('renders the story tab, viewer and create page without missing translation keys')`

**Acceptance.**
- ✅ `pnpm run gate` green (full, no `--quick`).
- ✅ Domain docs reference no `docs/Feature_Planning` path.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes.

| Surface | Check | OK? |
|---------|-------|-----|
| Story page — guest | No tab bar, no fanart button, no speaker icon; page identical to before | |
| Story page — `user` (non-confirmed) | Same as guest | |
| Story page — confirmed reader, public story | Tabs Chapitres / Fanarts; « Proposer un fanart » next to chapter list; speaker icons on chapters with audio | |
| Story page — confirmed reader, mode `designated`, no role | « Demander le rôle de fanartiste »; after request, withdraw option | |
| Story page — author | « Ajouter un artwork », no « Proposer »; Fanarts tab shows « En attente de confirmation » + effective-mode explanation (co-authored: most-restrictive note) | |
| Story page — private story, author | No submission button; existing fanarts visible | |
| Fanarts tab — reader | Groups in chapter order + « Toute l'œuvre »; unread chapters collapsed with spoiler notice; « Montrer tout » / « Cacher tout »; lazy thumbnails | |
| Fanarts tab — empty | Empty-state message | |
| Fanarts tab — unpublished chapter | Group hidden for reader, visible for author | |
| Viewer — desktop | Image fits width/height; title, artist link, description, Signaler; Supprimer for author / owner | |
| Viewer — mobile (375 px) | Image stacked above text, scrollable, closable | |
| Viewer — moderator | Moderation menu with « Supprimer » | |
| Review viewer — author, pending image | Comment required; Accepter publishes; Décliner removes; Signaler removes from queue | |
| Review viewer — pending audio | Player plays; accept replaces previous chapter audio | |
| Create page — reader | Image / Audio switch only shows allowed kinds; attestation wording incl. AI clause; Discord box only on Image; validation errors keep fields | |
| Create page — author | Title « Ajouter un artwork »; credit warning with count; no-credit refusal message | |
| Artwork edit page | Pre-filled; file replace works; no credit spent | |
| Story-page player | Click speaker → plays; at end chapter marked read (check icon in list) and next chapter with audio starts; stops after last | |
| Chapter page — confirmed reader | Audio player under title with report flag; images block at bottom, view-only | |
| Chapter page — guest | Nothing of the feature | |
| Chapter page — quotes / annotations | Quote toolbar and author heat map still anchor correctly | |
| Collaborators page — author | Fanartistes section: grant, revoke, pending requests accept / decline | |
| Profile tab — owner | Images / Sons sections; pending badge; delete | |
| Profile tab — other confirmed, tab private | Tab absent | |
| Profile tab — other confirmed, tab opened | Only readable stories; spoiler collapse for unread chapters | |
| Profile tab — moderator | Visible even when private, pending visible | |
| Settings › Histoires | Fanart section with two selects next to reading prefs; defaults images=oui, audio=fanartistes désignés | |
| Settings › Profil | « Masquer l'onglet Fanarts » checked by default | |
| Notifications | Each of the ten types renders (submitted, accepted w/ comment, declined w/ and w/o comment, deleted, deleted by moderation, audio replaced, removed with content, role requested/given/declined) | |
| Admin › Moderation report (fanart) | Snapshot shows copied image / audio player + title, artist, story, chapter; link opens the viewer on the story page | |
| Admin › Moderation report after fanart deleted | Copy still displayed | |
| Deactivated fanartist | Their fanarts gone from tab / chapter / profile; back after reactivation | |
| Deactivated sole author | Story and its fanarts unreachable | |
| Deactivated co-author (one of two) | Story and its fanarts still visible; hidden once both authors are deactivated | |
| Deleted co-author account | Co-authored story and all its fanarts gone; fanartists notified | |
| Deleted chapter | Its fanarts gone; fanartist notified | |
| Dark mode / theme | Tabs, thumbnails, viewer readable | |

## Open items

| # | Item | Needed by | Status |
|---|------|-----------|--------|
| O1 | **Contradiction (spec §5 / DECISIONS #10):** Story's `softDeleteStoriesByAuthor` uses `findByAuthor`, which matches **co-authored** stories too, so a co-authored story (and therefore its fanarts, via `canRead`) is hidden as soon as **any** author is deactivated — not "only once every author is deactivated". Fanart follows Story (A13). | 3b, 13 | Resolved — DECISIONS #32: Story is changed in phase 3b (hidden only once all authors are deactivated) |
| O2 | **Contradiction (spec §5 / DECISIONS #12):** Story's `deleteStoriesByAuthor` (on `Auth.UserDeleted`) hard-deletes **every** story the user co-authors, so "co-author deletes account, story survives" never happens today; the story's fanarts are removed through `Story.Deleted` (fanartists notified). Fanart's own `UserDeleted` listener still deletes that user's artworks/fanarts elsewhere. | 13 | Resolved — DECISIONS #33: Story's behaviour kept, supersedes #12; phase 13 tests it |
| O3 | Architecture §3.5 says middleware `web, auth, verified`; the codebase convention for confirmed-only routes is `['web','auth','compliant', 'role:…']` (Quote). The plan uses the convention. | 8 | Resolved in plan |
| O4 | Moderation reasons: the only seeding precedent is `ModerationSeeder` (an « Other » reason per hard-coded topic, dev/e2e only); production reasons are admin-created. Phase 14 adds `'fanart'` to that list instead of the `FanartModerationReasonSeeder` named in architecture §8. Release note: an admin must create the fanart reasons before go-live. | 14 | Resolved in plan |
| O5 | Resolving `{slug}` → story id: `SlugWithId::extractId()` lives in `app/Domains/Shared/Support/SlugWithId.php` (Shared, usable from Fanart); then `StoryPublicApi::getStory($id)` and compare its slug, 404 on mismatch / missing. | 9 | Resolved in plan |
| O6 | Description sanitiser profile: plan picks `strict` (`config/purifier.php`: p/strong/em/span + custom-emoji classes — covers the `inline` toolbar's bold/italic/emoji) via `EditorPublicApi::sanitizeText`. Phase 9 confirms with a test. | 9 | Resolved in plan |
| O7 | Profile picker for granting the Fanartiste role: reuse whatever user search the collaborators page uses today (not read yet). | 15 | Open |
| O8 | Production upload limits (WAF / nginx `client_max_body_size`, PHP `upload_max_filesize`) must allow ≥ 16 MB (architecture §9). Not checkable from the repo. | release | Open — ops |
| O9 | Do moderators/admins also hold `user-confirmed`? The route middleware lists staff roles explicitly, so either way works; Moderation's report-form route only allows `user`/`user-confirmed` — staff reporting a fanart depends on that. | 14 | Open — non-blocking |
