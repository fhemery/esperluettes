# SecretGift gift images on Media (private)

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.

**Status:** DONE — 2026-07-31 (VERIFY skipped, user smoke-checks) ·
**Domain(s):** `Media`, `Calendar/SecretGift`, `Shared` · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

Media grew a private half: `storePrivate()` writes an image to the `private`
disk (`storage/app/private`) with no responsive variants and no `/storage/…`
URL, `stream()` hands the bytes back, and `media:gc` sweeps the private roots
with the same provider-claim logic as the public disk. SecretGift gift images
now use it — `gift_image_path` holds `secret-gift/{activityId}/{hash}.ext`, and
the unchanged `secret-gift.image` route streams it after `canViewImage()`.
`<x-media::image-field>` gained `allowLibrary` and `previewUrl` so it can edit an
image that has neither a reuse library nor a public preview, which let the gift
form drop `<x-shared::image-upload>` — the widget's last consumer, so the Blade
component is deleted.

## Key behaviour

- A private path (first segment in `MediaService::PRIVATE_SCOPE_ROOTS`, today
  just `secret-gift`) resolves to the `private` disk; `originalUrl()`,
  `variantUrl()` and `listByScope()` **throw** on one rather than inventing a
  URL, and `store()` / `storePrivate()` refuse each other's scopes.
- `MediaPublicApi::stream()` performs **no** authorization: the consumer checks
  its own rule first. Media never learns SecretGift's timing rules.
- Visibility is unchanged: giver always; recipient only once the activity is
  `ENDED`/`ARCHIVED`; everyone else 403. Upload/replace/remove only while
  `ACTIVE`.
- **Nothing in Calendar deletes a gift image.** Replace and remove only rewrite
  the column; `media:gc` reclaims the file after the 7-day grace window, which
  also fixes the old orphan leak when a shuffle wipes assignments.
- The private GC guard sits at the scope **root** (`secret-gift/`), swept
  recursively — per-activity granularity would make an activity with no
  remaining claimed gift permanently unsweepable. So the root stops being swept
  entirely if `SecretGiftMediaUsageProvider` is ever unregistered.
- Form shape is Media's: `gift_image[path]` / `gift_image[file]`, no
  `gift_image_remove`. An empty path with no file **is** the removal; a
  submitted path only means "keep what is stored" and is never adopted.
- Gift **sound** is untouched: still a raw `local` file, deleted synchronously,
  streamed with Range support.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Media/Public/Api/MediaPublicApi.php` — `storePrivate`, `stream`, `exists` |
| Service | `app/Domains/Media/Private/Services/MediaService.php` — `PRIVATE_DISK`, `PRIVATE_SCOPE_ROOTS`, `isPrivatePath`, `diskFor`, two-disk `gc()` |
| Picker endpoint | `app/Domains/Media/Private/Controllers/MediaLibraryController.php` — private scope behaves like an unknown one |
| Gift storage | `…/SecretGift/Services/SecretGiftService.php` — `saveGiftImage`, `removeGiftImage`, unchanged `canViewImage` |
| Gift serve / save | `…/SecretGift/Http/Controllers/SecretGiftController.php` — `serveImage`, image half of `saveGift` |
| Request | `…/SecretGift/Http/Requests/SaveGiftRequest.php` — `gift_image.path` / `gift_image.file` |
| Views / components | `app/Domains/Media/Private/Resources/views/components/image-field.blade.php`; `…/SecretGift/Resources/views/partials/_gift-preparation.blade.php` |
| GC claiming | `…/SecretGift/Support/SecretGiftMediaUsageProvider.php`, registered in `SecretGiftServiceProvider::boot()` |
| Legacy data move | `…/SecretGift/Support/LegacyGiftImageMover.php` + `…/Database/Migrations/2026_07_31_143000_move_secret_gift_images_to_media_private.php` |
| Tests | `app/Domains/Media/Tests/Feature/{MediaPrivateStorage,MediaImageFieldComponent,MediaService,MediaLibraryEndpoint}Test.php`; `app/Domains/Calendar/Tests/Feature/SecretGift/{SaveGift,ServeFile,SecretGiftPage,GiftImageUsageProvider,LegacyGiftImageMove}Test.php`; `app/Domains/Shared/Tests/Feature/View/Components/UploadComponentsTest.php` |
| Deleted | `app/Domains/Shared/Resources/views/components/image-upload.blade.php` |

Commits: `0eb759e3` (Media private disk + image-field props), `7c4e7aee`
(SecretGift store/serve/GC/migration), `03d71701` (image-field swap + Shared
deletion).

## Extension points used

- **Media usage registry** — `SecretGiftMediaUsageProvider` claims every
  non-null `gift_image_path`, which is what keeps the private root swept.
- **`<x-media::image-field>` props** — `allowLibrary=false` + `previewUrl`, the
  first consumer of both; defaults keep News / FAQ / StaticPage / Calendar-admin
  identical.
- No events, notifications, settings, statistics or moderation. No new deptrac
  edge — `CalendarPrivate → MediaPublic` already existed.

## Decisions worth remembering

- **Auth stays in the consumer** (#2, A2). Media streams bytes and knows no
  rules; the alternative — a Media visibility registry — was rejected and would
  be expensive to introduce now.
- **Private images have no variants** (A3), so `stream()` ships without the
  `?int $width` that architecture §3.1 listed (A9), and `hasVariants()` returns
  `false` for a private path instead of throwing (A11).
- **A submitted `gift_image.path` is never adopted** (A18) — the plan said
  "assign it if it differs", which is a privacy hole: `canViewImage()` grants the
  giver unconditional read, so a crafted path would stream any private file. With
  the library disabled the form cannot legitimately produce a different path.
  Locked by a denial test.
- **Legacy files move in a database migration**, not an artisan command (O6):
  deploys run `migrate`, and until the data flips every pre-existing gift image
  404s. `LegacyGiftImageMover` holds the logic (idempotent, reversible) so it is
  testable after `Storage::fake`.
- **The Shared `image-upload` lang file survives its component** (A6) — Story's
  `cover-tab-custom.blade.php` borrows `drop_or_click`, `max_size` and
  `size_error` and never used the widget.
- A first attempt collapsed the Shared widgets into SecretGift and was
  **reverted** (#1/#6, commits `6260fe45` / `580d1024`). Do not revive it.

## Plan vs. code

- Phases 1 and 2 ship as the **single commit** `0eb759e3` (A13): they were built
  concurrently in one worktree and phase 2's commit swept up phase 1's staged
  files. Phase 1 has no commit of its own.
- `LegacyGiftImageMover` reads and writes both disks with `Storage::disk()`
  directly, against architecture §1's "Calendar never touches disks for images"
  (A14). `MediaPublicApi` can store an *upload* but cannot adopt an existing
  file; scoped to the one-shot move, normal saves go through `storePrivate`.
- The Shared test landed at `Feature/View/Components/UploadComponentsTest.php`,
  not the plan's `Feature/SharedUploadComponentsTest.php` (A17, open item O8).
- The "lang keys survive" test asserts `Lang::has(…)`, not `__()` output: the
  suite runs under `APP_LOCALE=zz` where a string comparison passes vacuously
  (A19).

## Not done

**Deliberate non-goals** (spec §8): moving gift **sound** to Media; making gift
images public; changing giver/recipient timing; Story cover UI and the Shared
`image-upload` lang file; activity header images.

**VERIFY was skipped** at the user's request (A8) — they smoke-check by hand. The
17-row visual QA checklist in [`03-plan.md`](./03-plan.md) is therefore
**unfilled**, and no e2e spec was added (`e2e/tests/features/` is empty, nothing
to retire). The rows worth the most attention: a **migrated legacy gift** still
displaying for giver and recipient, and the admin forms still showing the reuse
picker.

**Before deploying** (open item O7, never verified): count the rows the migration
will move —
`select count(*) from calendar_secret_gift_assignments where gift_image_path is not null and gift_image_path not like 'secret-gift/%'`
— and confirm the files exist under `storage/app/calendar/secret-gift/`. The
mover reports missing sources instead of failing, so a mismatch is survivable.

**Pushed back to [`BACKLOG.md`](../BACKLOG.md)**:

- `media-sound-upload/` — give gift sound the same treatment (private Media
  storage or a Media sound capability) and retire `<x-shared::sound-upload>`.
- `shared-upload-lang-ownership/` — Shared now ships an `image-upload` lang file
  with no component; move the three borrowed keys into Story and delete it.
