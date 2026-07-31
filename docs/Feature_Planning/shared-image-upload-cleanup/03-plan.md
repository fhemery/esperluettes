# SecretGift gift images on Media (private) — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions & assumptions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Media: private disk store, stream, GC | M | — | DONE |
| 2 | Media: `image-field` gains `allowLibrary` + `previewUrl` | S | — | DONE |
| 3 | SecretGift: gift images stored & served through Media, legacy files migrated | M | 1 | DONE |
| 4 | SecretGift: swap to `<x-media::image-field>`, delete Shared `image-upload` | M | 2, 3 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 1 and 2 are both Media-only and independent of each other; either order
works. Phase 3 needs 1. Phase 4 needs both 2 and 3.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- **Do not revive the reverted approach.** A first implementation collapsed the
  Shared widget into SecretGift and was reverted (DECISIONS #1/#6). Shared keeps
  `image-upload` until phase 4 deletes it; nothing is copied into Calendar.

---

## Phase 1 — Media: private disk store, stream, GC

**Goal.** Give Media a private-disk half — store bytes off the public disk,
stream them back on request, and sweep the private folders with the same GC —
so a consuming domain can own an image that has no `/storage/…` URL.

Architecture sections that govern this phase: **§3.1** (public API surface),
**§3.2** (services), **§7 tradeoffs 1/3/4**. Assumptions **A1** (disk is
Laravel's `private`, root `storage/app/private`), **A3** (private images store
the original only, no `-400w`/`-800w` variants), **A5** (scope string
`secret-gift/{activityId}`; the path prefix implies the private disk).

No consumer uses any of this at the end of this phase — that is phase 3. This
phase is pure platform, and it ships green on its own.

### What exists today (so you do not have to go read it)

- `app/Domains/Media/Private/Services/MediaService.php` has
  `public const DISK = 'public'` and every method hardcodes it:
  `folderFor`, `store`, `variantUrl`, `listByScope`, `hasVariants`, `gc`,
  and the private helpers `managedFolders()`, `originalsIn(string $folder)`,
  `mtime(string $path)`.
- `folderFor()` accepts the flat scopes `news`, `faq`, `static-pages`,
  `activities` and the pattern `chapters/{userId}`; anything else throws
  `InvalidArgumentException`.
- `gc(int $days = 7, bool $dryRun = false)` walks `managedFolders()`, and for
  each folder applies a **zero-claim safety guard**: if no registered
  `MediaUsageProvider` claims any path under `{folder}/`, the whole folder is
  skipped rather than emptied (a missing provider must not wipe a scope).
- `ImageService::process($disk, $folder, $file, $widths)` and
  `deleteWithVariants($disk, $path)` already take a `$disk` argument — no change
  needed there. `process()` with `$widths = []` writes the original only.
- `MediaPublicApi::originalUrl()` returns `asset('storage/'.$path)` directly;
  `variantUrl()` delegates to `MediaService` which also returns `asset(...)`.
- `config/filesystems.php` already defines the `private` disk
  (`driver: local`, `root: storage_path('app/private')`, `serve: false`).
  **No config change is needed.**
- Verified: `Storage::disk('private')->response($path, null, $headers)` works
  despite `serve => false` — `serve` only affects `url()`. `response()` returns
  a `StreamedResponse`, sets `Content-Type` from the mime type and
  `Content-Length`, and defaults `Content-Disposition: inline`.

### Two design points this phase must get right

**1. The zero-claim guard's granularity for private folders.** Gift images live
in `secret-gift/{activityId}` folders (A5). If GC treated each activity folder
as a managed folder, then an activity whose gifts were all removed — or whose
assignments were wiped by a re-shuffle — would have zero claimed paths, so the
guard would skip the folder forever and the orphans would never be collected.
That directly defeats `01-functional.md` §5 ("Shuffle that deletes assignment
rows frees paths for GC"). So: **sweep `secret-gift/` recursively as one managed
root, and apply the zero-claim guard at that root**, not per activity subfolder.
The guard's purpose is catching a missing provider, and the provider is
per-domain, so the root is its natural granularity. Recorded as assumption
**A10** in `DECISIONS.md`.

**2. Private paths must never produce a public URL.** `originalUrl()` and
`variantUrl()` build `asset('storage/…')`, which for a private path would be a
URL to a file that is not there — a silent broken image, or worse a wrong file.
Both must **throw** for a private path (architecture §3.1). Same reasoning for
`listByScope()`: the reuse picker would hand out private paths and unusable
URLs, so it rejects private scopes exactly like an unknown scope.

### Deliverables

`app/Domains/Media/Private/Services/MediaService.php`

- `public const PRIVATE_DISK = 'private';`
- `private const PRIVATE_SCOPE_ROOTS = ['secret-gift'];`
- `folderFor(string $scope): string` — also accept `#^secret-gift/\d+$#`,
  returning the scope unchanged as its folder (like `chapters/{userId}`).
- `isPrivateScope(string $scope): bool` / `isPrivatePath(string $path): bool` —
  first path segment is in `PRIVATE_SCOPE_ROOTS`.
- `diskFor(string $scopeOrPath): string` — `PRIVATE_DISK` or `DISK`.
- `storePrivate(string $scope, UploadedFile $file, array $widths = []): string`
  — asserts the scope is private (throw `InvalidArgumentException` otherwise),
  then `$this->imageService->process(self::PRIVATE_DISK, $this->folderFor($scope), $file, $widths)`.
  Default `[]` = original only (A3).
- `stream(string $path, array $headers = []): StreamedResponse` —
  `Storage::disk($this->diskFor($path))->response($path, null, $headers)`.
  Performs **no** authorization (architecture §3.3); the caller has already
  authorised. Works for public paths too, but private is the reason it exists.
- `exists(string $path): bool` — on the disk `diskFor($path)` resolves.
- `variantUrl()` and `listByScope()` — throw `InvalidArgumentException` when the
  path/scope is private.
- Generalise the disk-bound private helpers:
  `managedFolders(string $disk): array`, `originalsIn(string $disk, string $folder): array`,
  `mtime(string $disk, string $path): int`. For the private disk,
  `managedFolders()` returns the roots in `PRIVATE_SCOPE_ROOTS` that exist.
- `gc()` — loop over both disks. For the private disk, `originalsIn` must be
  **recursive** under the root (`allFiles()`, still excluding `-\d+w.(jpg|jpeg|png|webp)`
  and non-images) and the claim check is `str_starts_with($claimedPath, $root.'/')`
  evaluated once per root. Return shape stays
  `array{deleted:list<string>, skipped:list<string>}`. Public-disk behaviour is
  unchanged — the existing non-recursive flat-scope sweep and its tests must
  keep passing untouched.

`app/Domains/Media/Public/Api/MediaPublicApi.php`

- Expose `storePrivate`, `stream`, `exists` (delegating to `MediaService`).
- `originalUrl(string $path): string` — throw `InvalidArgumentException` when
  `$path` is private, before building `asset('storage/…')`. (It builds the URL
  inline today; it needs to ask `MediaService` whether the path is private, so
  give `MediaService` a public `isPrivatePath()` and call it.)
- Docblocks: say plainly that private images have no URL and no variants.

`app/Domains/Media/Private/Controllers/MediaLibraryController.php`

- A private scope must behave like an unknown scope (same status the endpoint
  already returns for unknown/retired scopes — read the existing handling and
  match it, do not invent a new one).

Docs (the gate's `docs` step checks links, and these are the domain's contract):

- `app/Domains/Media/README.md` — a short "Private images" section: which disk,
  no URLs, no variants, streamed by the consumer after its own auth check.
- `app/Domains/Media/AGENTS.md` — new invariants: (a) never call
  `originalUrl`/`variantUrl` on a private path, they throw by design; (b) the
  private GC guard is applied at the scope **root**, so a private root still
  needs a registered provider or nothing under it is ever collected.
  Must not reference `docs/Feature_Planning`.

### Tests

New: `app/Domains/Media/Tests/Feature/MediaPrivateStorageTest.php`
(`Storage::fake('private')` + `Storage::fake('public')`)

- `it stores a private image on the private disk and returns a scoped path`
  — `storePrivate('secret-gift/7', $file)` returns `secret-gift/7/{hash}.jpg`,
  the file exists on `private`, and `public` is empty.
- `it does not generate variants for a private image` — no `-400w`/`-800w`
  file exists next to the original.
- `it rejects a public scope passed to storePrivate`
- `it rejects a private scope passed to store` — `store('secret-gift/7', …)`
  throws; private bytes must not land on the public disk by accident.
- `it streams a private image back with its mime type` — `stream($path)`
  returns a `StreamedResponse`, status 200, `Content-Type: image/jpeg`, and the
  streamed body equals the stored bytes.
- `it merges caller supplied headers into the stream response`
- `it reports existence on the right disk` — `exists()` true for the stored
  private path, false for a path that only exists on `public`.
- `it refuses to build a public URL for a private path` — `originalUrl` and
  `variantUrl` both throw `InvalidArgumentException`.
- `it refuses to list a private scope in the reuse picker` — `listByScope('secret-gift/7')`
  throws.

Extend `app/Domains/Media/Tests/Feature/MediaServiceTest.php`

- `it garbage collects an unclaimed private gift image past the grace window`
  — a provider claims one path under `secret-gift/`, a second file in a
  *different* activity folder is unclaimed and old → deleted.
- `it keeps a claimed private image` and `it keeps a private image inside the grace window`
- `it skips the private root when no provider claims anything under it`
  — the zero-claim guard, at root granularity.
- `it collects an orphan in an activity folder whose other gifts are all gone`
  — the A10 case: activity `7` has no claimed path at all, activity `9` does;
  the file under `secret-gift/7/` is still deleted because the guard is applied
  at the `secret-gift` root. This is the test that proves §5's shuffle promise.
- The existing public-disk GC tests must pass unmodified.

Extend `app/Domains/Media/Tests/Feature/MediaLibraryEndpointTest.php`

- `it does not expose a private scope through the library endpoint`
  — authenticated `GET /media/library?scope=secret-gift/7` returns the same
  status as an unknown scope and no `items`.

### Acceptance

- ✅ `MediaPublicApi::storePrivate('secret-gift/7', $file)` writes only to the
  `private` disk and returns `secret-gift/7/{hash}.{ext}` with no variant files.
- ✅ `MediaPublicApi::originalUrl()` and `variantUrl()` throw on a private path;
  no code path can produce an `asset('storage/secret-gift/…')` URL.
- ✅ An authenticated `GET /media/library?scope=secret-gift/7` returns no items.
- ✅ `media:gc` deletes an unclaimed private gift image older than the grace
  window, keeps a claimed one, and skips the whole `secret-gift` root when no
  provider claims anything under it.
- ✅ Public-disk store / picker / GC behaviour is byte-for-byte unchanged: every
  pre-existing Media test passes without being edited.
- ✅ Media `README.md` and `AGENTS.md` document the private half and do not
  reference `docs/Feature_Planning`.
- ✅ `npm run gate` green.

---

## Phase 2 — Media: `image-field` gains `allowLibrary` + `previewUrl`

**Goal.** Let a consumer use `<x-media::image-field>` for an image that has no
reuse library and no public preview URL, without changing anything for the
consumers that have both.

Architecture sections: **§4** (frontend), decision **#3** in `DECISIONS.md`
(`allowLibrary` approved). Functional requirement **§4.2**.

This phase is Media-only and touches no consumer. It is independent of the
private-disk work — it can land before or after it.

### What exists today

`app/Domains/Media/Private/Resources/views/components/image-field.blade.php`
(~278 lines) is the whole component: markup plus an inline Alpine component
`mediaImageField` registered in an `@once @push('scripts')` block at the bottom.

Relevant current behaviour:

- `@props([...])` currently has `name, path, alt, caption, scope, showUsage,
  usageCount, showAlt, showCaption, altRequired, allowKeepOriginal, keepOriginal,
  maxSize, accept, label, helpText]`.
- Line ~36 computes the initial preview server-side:
  `$currentUrl = $path ? ($keepOriginal ? $api->originalUrl($path) : $api->variantUrl($path, 400, 'webp')) : null;`
- Two action buttons: "Téléverser" (`$refs.fileInput.click()`) and
  "Choisir une image existante" (`openPicker()`), plus a picker modal
  (`x-show="pickerOpen"`) that fetches `GET {libraryUrl}?scope=…&page=…`.
- Emits `{name}[path]` (hidden, `x-model="path"`) and `{name}[file]` (file
  input). A new upload clears `path`; picking from the library sets it.

Existing lang: `app/Domains/Media/Private/Resources/lang/fr/image-field.php`
(namespace `media::image-field.*`) — **no new keys are needed** for this phase.

### Deliverables

`app/Domains/Media/Private/Resources/views/components/image-field.blade.php`

- Two new props: `'allowLibrary' => true`, `'previewUrl' => null`.
- Initial preview: `$currentUrl = $previewUrl ?: ($path ? (…existing expression…) : null);`
  — the consumer-supplied URL wins. This is not cosmetic: after phase 1, calling
  `variantUrl()` on a private path **throws**, so the `previewUrl` branch must
  short-circuit before the API call is made, not after.
- When `allowLibrary` is false: do not render the "Choisir une image existante"
  button and do not render the picker modal at all. The Alpine `openPicker` /
  `loadMore` / `fetchPage` / `chooseExisting` functions may stay in the shared
  `@once` script (it is registered once for the page and is not per-instance) —
  what must be unreachable is the UI that calls them.
- Update the component's leading comment block to document both props.

`app/Domains/Media/README.md` — add `allowLibrary` and `previewUrl` to the
component's documented props, noting that `previewUrl` is what makes the field
usable for a private image.

### Tests

New: `app/Domains/Media/Tests/Feature/MediaImageFieldComponentTest.php` —
render the component with `Blade::render()` (or a throwaway route) and assert on
the HTML. There is no test for this component today, so this file is new.

- `it renders the library picker by default` — the rendered HTML contains the
  `media::image-field.choose_existing` label and the picker modal markup.
- `it hides the library picker when allowLibrary is false` — neither the button
  label nor the modal markup is present; the upload button and the
  `{name}[file]` input still are.
- `it uses the consumer supplied preview url when given` — with
  `:preview-url="'/calendar/secret-gift/1/image/2'"` and a path, the rendered
  Alpine `currentUrl` is that URL and no `/storage/` URL appears in the output.
- `it falls back to the media variant url when no preview url is given` —
  today's behaviour, unchanged.
- `it still emits name[path] and name[file] when the library is hidden`.
- `it does not call media url helpers for a private path when a preview url is set`
  — pass `path: 'secret-gift/1/abc.jpg'` plus a `previewUrl`; the component
  renders without throwing. (This is the regression that phase 4 depends on.)

### Acceptance

- ✅ `<x-media::image-field>` with no new props renders exactly as before —
  the existing News / FAQ / StaticPage / Calendar-admin forms are untouched and
  their tests pass unmodified.
- ✅ With `:allow-library="false"`, neither the "choose existing" button nor the
  picker modal is in the DOM.
- ✅ With `:preview-url="…"`, the initial preview is that URL and no Media URL
  helper is called for the current path.
- ✅ A private path plus a `previewUrl` renders without throwing.
- ✅ `npm run gate` green.

---

## Phase 3 — SecretGift: gift images stored & served through Media, legacy files migrated

**Goal.** Move gift-image bytes onto Media's private disk — new uploads, the
serve route, GC claiming, and the files that already exist in production — while
leaving the gift form and its field names exactly as they are.

**Depends on phase 1**, which added to `MediaPublicApi`:

```php
storePrivate(string $scope, UploadedFile $file, array $widths = []): string  // original only
stream(string $path, array $headers = []): StreamedResponse                  // no auth
exists(string $path): bool
```

with the private scope `secret-gift/{activityId}` mapping to the folder of the
same name on the `private` disk, and Media's GC sweeping the `secret-gift/`
root recursively (an unclaimed original older than the 7-day grace window is
deleted; the root is skipped entirely if **no** provider claims anything in it —
hence the usage provider below is not optional).

Architecture sections: **§1** (domain placement), **§2** (data model), **§2.3**
(lifecycle), **§3.5** (routes), **§9** (risks). Assumptions **A2** (Calendar
keeps `canViewImage` and the route; Media never learns SecretGift's rules),
**A4** (deferred GC, no synchronous delete), **A5** (scope string), **A7**
(one-shot data move for existing files).

**The UI does not change in this phase.** `_gift-preparation.blade.php` keeps
using `<x-shared::image-upload>` and the flat `gift_image` file field with the
`gift_image_remove` boolean. Swapping the widget and the form shape is phase 4.
Keeping them apart is deliberate: the server-side storage change is what carries
the data migration, and it must be revertable without touching the form.

### What exists today

`app/Domains/Calendar/Private/Activities/SecretGift/Services/SecretGiftService.php`

- `saveGiftImage(SecretGiftAssignment $assignment, UploadedFile $file): string`
  deletes the previous file with `Storage::disk('local')->delete(...)`, then
  writes `calendar/secret-gift/{activity_id}/{giver_user_id}.{ext}` to the
  `local` disk, sets `gift_image_path` and saves.
- `removeGiftImage(SecretGiftAssignment $assignment): void` deletes the file
  from `local` and nulls the column.
- `canViewImage(SecretGiftAssignment $assignment, int $userId, Activity $activity): bool`
  — giver always; recipient only when `$activity->state` is
  `ActivityState::ENDED` or `ARCHIVED`; everyone else false. **Unchanged by this
  phase** (architecture §3.3).

`.../Http/Controllers/SecretGiftController.php`

- `saveGift()` rejects a non-`ACTIVE` activity, 403s when the user has no
  assignment as giver, then handles text / image-remove / image-upload /
  sound-remove / sound-upload in that order.
- `serveImage(Activity $activity, SecretGiftAssignment $assignment)` checks
  `canViewImage`, 404s on a null path, 404s when the file is missing on `local`,
  then reads the whole file with `Storage::disk('local')->get()`, resolves the
  mime type with `File::mimeType()`, and returns
  `response($content, 200, ['Content-Type' => …, 'Cache-Control' => 'private, max-age=3600'])`,
  adding `Content-Disposition: attachment; filename="gift-image-{giver}-{id}.{ext}"`
  when the request matched `secret-gift.download-image`.
- Both `secret-gift.image` and `secret-gift.download-image` point at
  `serveImage`; both routes are `['web', 'auth', 'verified']`.

Sound (`gift_sound_path`, `streamSound`, `downloadSound`, `saveGiftSound`,
`removeGiftSound`) stays on the `local` disk and is **out of scope**
(DECISIONS #5). Do not touch it. It shares no code with the image path beyond
living in the same service and controller.

`SecretGiftServiceProvider::boot()` currently loads views, translations,
migrations, routes and the Blade component namespace. It registers no Media
usage provider. The pattern to copy is in
`app/Domains/Calendar/Public/Providers/CalendarServiceProvider.php`:

```php
app(MediaUsageRegistry::class)->register(new ActivityMediaUsageProvider());
```

with `app/Domains/Calendar/Private/Support/ActivityMediaUsageProvider.php` as
the reference implementation (a final class implementing
`App\Domains\Media\Public\Contracts\MediaUsageProvider::usedPaths(): iterable`,
returning a plucked column).

### Deliverables

**Storage + serve**

- `Services/SecretGiftService.php`
  - Inject `MediaPublicApi` (constructor property promotion; deptrac already
    allows `CalendarPrivate → MediaPublic`, architecture §5).
  - `saveGiftImage()` → `$this->media->storePrivate('secret-gift/'.$assignment->activity_id, $file)`,
    assign to `gift_image_path`, save. **Delete nothing** — the old path is
    reclaimed by Media GC (A4). Drop the `Storage::disk('local')->delete(...)`.
  - `removeGiftImage()` → null the column and save. **Delete nothing.**
  - `canViewImage()` unchanged.
- `Http/Controllers/SecretGiftController.php`
  - `serveImage()` — keep the `canViewImage` 403 and the null-path 404, replace
    the existence check with `$this->media->exists($path)` and the body with
    `$this->media->stream($path, $headers)`. Keep
    `'Cache-Control' => 'private, max-age=3600'` and the download
    `Content-Disposition` (note: `Storage::response()` defaults to
    `inline` with the hashed basename, so the explicit
    `Content-Disposition` for the download route is still required, and the
    inline route should keep whatever it produces today).
  - `saveGift()` — unchanged in this phase.

**GC claiming**

- New `.../SecretGift/Support/SecretGiftMediaUsageProvider.php` — final class
  implementing `MediaUsageProvider`, `usedPaths()` returns
  `SecretGiftAssignment::query()->whereNotNull('gift_image_path')->pluck('gift_image_path')->all()`.
  Only image paths: sound is not a Media path.
- `SecretGiftServiceProvider::boot()` — register it on `MediaUsageRegistry`,
  with a one-line comment in the same spirit as `CalendarServiceProvider`'s.

**Legacy data move (A7)**

Vehicle: a **database migration**, not a standalone artisan command. Production
deploys run `artisan migrate` (see `docs/Deploying.md`); a separate command
could be forgotten, and between deploying this phase and running it every
pre-existing gift image would 404, because `stream()` resolves the disk from the
path prefix and a legacy `calendar/secret-gift/…` path is not a private path.
Tying the move to `migrate` makes code and data flip together. Architecture §8
explicitly allows either vehicle.

- New `.../SecretGift/Database/Migrations/2026_07_31_HHiiss_move_secret_gift_images_to_media_private.php`
  — thin: resolves and invokes the mover below in `up()`, and its inverse in
  `down()`.
- New `.../SecretGift/Support/LegacyGiftImageMover.php` — the logic, so it is
  testable without re-running migrations (a test cannot easily re-run a
  migration after `Storage::fake`). Two methods:
  - `toMedia(): array` — for each assignment whose `gift_image_path` is set and
    does **not** already start with `secret-gift/`: copy the bytes from `local`
    to `private` at `secret-gift/{activity_id}/{basename}` (keep the existing
    `{giverId}.{ext}` basename — it is already unique within the activity folder
    and re-hashing buys nothing), update the column, delete the source on
    `local`. Skip and report rows whose source file is missing. Idempotent:
    running it twice is a no-op.
  - `toLegacy(): array` — the inverse, for the migration's `down()`.
  - Both return a small per-row report (moved / skipped-missing /
    already-migrated counts) so the deploy log is readable.
- Update `docs/Deploying.md` only if it needs a note; otherwise leave it alone.

**Docs**

- `app/Domains/Calendar/Private/Activities/SecretGift/README.md` — the
  "Gift assets are private files, not Media-domain images" paragraph (~line 48)
  is now wrong for images. Rewrite it: images are Media private-disk images
  under `secret-gift/{activity_id}/`, served through `MediaPublicApi::stream`
  after `canViewImage`; **sound** is still a raw `local` file with Range
  support. Must not reference `docs/Feature_Planning`.
- `app/Domains/Calendar/AGENTS.md` — the invariant "**Secret Gift files are on
  the `local` disk, not `public`**" (~line 24) must be split: sound stays on
  `local`; images are on Media's `private` disk, never public, and are
  **never deleted by Calendar** — removing an image means clearing the column
  and letting `media:gc` reclaim it. Add that SecretGift must keep its
  `MediaUsageProvider` registered or the whole private root stops being swept.

### Tests

`app/Domains/Calendar/Tests/Feature/SecretGift/SaveGiftTest.php` — the file
already fakes `local` in `beforeEach`; add `Storage::fake('private')`.

- Update `allows a participant to upload an image gift` — assert the stored
  `gift_image_path` starts with `secret-gift/{activityId}/`, the file exists on
  the `private` disk, and **nothing** was written to `local` or `public`.
- New `it does not delete the previous file when an image is replaced` — upload,
  replace, assert the column points at the new path **and the old file is still
  on disk** (GC's job, A4).
- New `it clears the path without deleting the file when the image is removed`
  — the `gift_image_remove` flow; this is untested today.
- The existing validation tests (`validates image file type`,
  `validates image file size`) and the text/sound tests must keep passing
  unchanged — the request shape does not move in this phase.

`app/Domains/Calendar/Tests/Feature/SecretGift/ServeFileTest.php` — add
`Storage::fake('private')`; the four existing image authorization tests
(giver sees own; recipient blocked before end; recipient allowed after end;
outsider 403) must pass against the Media-backed path.

- New `it streams the image bytes from the private disk` — the response body
  equals the stored bytes and `Content-Type` is the image mime type.
- New `it 404s when the row points at a file that no longer exists`.
- New `it sends a download disposition on the download route` — the
  `secret-gift.download-image` route still returns
  `Content-Disposition: attachment` with the `gift-image-…` filename. Untested
  today.
- New `it never exposes a gift image under a public storage url` — assert
  nothing lands on the `public` fake disk after an upload.

New `app/Domains/Calendar/Tests/Feature/SecretGift/GiftImageUsageProviderTest.php`

- `it reports every stored gift image path to the media registry` — two
  assignments with images, one without; `usedPaths()` contains exactly the two.
- `it registers the provider on the media usage registry at boot` — resolve
  `MediaUsageRegistry` from the container and assert a
  `SecretGiftMediaUsageProvider` is among `providers()`. This is the test that
  stops the private root from silently going unswept.

New `app/Domains/Calendar/Tests/Feature/SecretGift/LegacyGiftImageMoveTest.php`
(`Storage::fake('local')` + `Storage::fake('private')`, calling
`LegacyGiftImageMover` directly)

- `it moves a legacy local gift image onto the private disk and rewrites the row`
  — from `calendar/secret-gift/{a}/{g}.jpg` to `secret-gift/{a}/{g}.jpg`, source
  gone, bytes identical.
- `it is idempotent` — running it twice leaves the same single file and the same
  column value.
- `it leaves an already migrated row alone` — a row already on `secret-gift/…`
  is untouched and counted as already-migrated.
- `it reports a row whose source file is missing instead of failing` — the
  column is left as-is, the run does not throw, the row appears in the report.
- `it moves the image back on rollback` — `toLegacy()` restores the `local` path
  shape, so the migration's `down()` is real.
- `it serves a migrated image through the existing route` — end-to-end: legacy
  file, run the mover, then `GET route('secret-gift.image', …)` as the giver
  returns 200 with the right bytes. This is the one that proves §4.3.4
  ("existing gift images remain viewable after deploy").

### Acceptance

- ✅ A new gift image upload writes only to the `private` disk under
  `secret-gift/{activityId}/` and the assignment column holds that path.
- ✅ Nothing SecretGift does deletes an image file — replace and remove only
  clear or overwrite the column.
- ✅ `GET secret-gift.image` still returns 200 for the giver, 403 for an
  outsider, 403 for the recipient while the activity is `ACTIVE`, and 200 for
  the recipient once it is `ENDED` — the authorization rules are byte-for-byte
  the same as before.
- ✅ `GET secret-gift.download-image` still returns an `attachment` disposition.
- ✅ No gift image is reachable at any `/storage/…` URL; nothing is written to
  the `public` disk.
- ✅ `SecretGiftMediaUsageProvider` is registered at boot and reports every
  non-null `gift_image_path`.
- ✅ A gift image created on the old `local` layout is still viewable through
  the unchanged route after the migration runs, and running the migration's
  mover twice changes nothing.
- ✅ The gift form still submits `gift_image` / `gift_image_remove` and still
  renders `<x-shared::image-upload>` — no UI change in this phase.
- ✅ Sound upload, streaming, Range requests and removal are untouched and their
  tests pass unmodified.
- ✅ `./vendor/bin/sail composer deptrac` reports no new violation (no new edge:
  `CalendarPrivate → MediaPublic` already exists).
- ✅ `npm run gate` green.

---

## Phase 4 — SecretGift: swap to `<x-media::image-field>`, delete Shared `image-upload`

**Goal.** Replace the Shared upload widget in the gift form with
`<x-media::image-field>` (library hidden, consumer-supplied preview), move the
save endpoint to Media's `name[path]` / `name[file]` form shape, and delete
`<x-shared::image-upload>` now that its last consumer is gone.

**Depends on phase 2**, which gave `<x-media::image-field>` two new props:
`allowLibrary` (default `true`; when `false` the reuse picker button and modal
are not rendered) and `previewUrl` (default `null`; when set it is used as the
initial preview instead of any Media-built URL, and Media's URL helpers are not
called for the current path).

**Depends on phase 3**, which put gift-image bytes on Media's private disk:
`gift_image_path` now holds `secret-gift/{activityId}/{file}`, uploads go
through `MediaPublicApi::storePrivate`, the `secret-gift.image` route streams
them after `canViewImage`, and neither the service nor the controller ever
deletes a file. `SecretGiftService::saveGiftImage(SecretGiftAssignment, UploadedFile): string`
and `removeGiftImage(SecretGiftAssignment): void` are the two entry points.

Architecture sections: **§3.5** (request/controller shape), **§4** (the Blade
call, quoted there in full). Functional requirements **§4.3.2** and **§4.4**.
Assumption **A6** (delete the Blade, keep the Shared lang file, leave
`sound-upload` in Shared).

### The form-shape change

Today the image half of the gift form is a flat file field plus a boolean:

- `gift_image` — `['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120']`
- `gift_image_remove` — `['nullable', 'boolean']`, coerced from Alpine's
  `"true"`/`"false"` strings in `SaveGiftRequest::prepareForValidation()`
  (the same method also coerces `gift_sound_remove` — **leave that half alone**).

It becomes Media's convention, which has no remove flag: an empty `path` with no
`file` *is* the removal.

- `gift_image.path` — `['nullable', 'string', 'max:255']`
- `gift_image.file` — `['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120']`

Note this is a form-shape change with exactly one client, the browser form
(architecture §9). There is no API consumer to version.

The reference implementation of the server side is
`ActivityController::resolveImage()` in
`app/Domains/Calendar/Private/Controllers/Admin/ActivityController.php`:

```php
$file = $request->file('image.file');
return $file
    ? $this->media->store(self::SCOPE, $file)
    : (($data['image']['path'] ?? null) ?: null);
```

The gift version differs in two ways: it calls `storePrivate` with a
per-activity scope, and it must go through `SecretGiftService` so the assignment
row is the only thing that writes `gift_image_path`.

### Deliverables

`.../SecretGift/Http/Requests/SaveGiftRequest.php`

- Replace the two image rules with the nested pair above.
- Delete the `gift_image_remove` branch of `prepareForValidation()`; keep the
  `gift_sound_remove` branch exactly as it is.

`.../SecretGift/Http/Controllers/SecretGiftController.php`

- In `saveGift()`, replace the two image blocks (`gift_image_remove` →
  `removeGiftImage`, `hasFile('gift_image')` → `saveGiftImage`) with a single
  resolution, guarded by `$request->has('gift_image')` so a submit that does not
  carry the image field at all leaves the gift alone:
  - a new upload (`$request->file('gift_image.file')`) → `saveGiftImage(...)`;
  - otherwise a non-empty `gift_image.path` → keep it (no write needed if
    unchanged; assign it if it differs);
  - otherwise → `removeGiftImage(...)`.
- Keep the text and sound handling, the `ACTIVE` guard and the 403 as they are.
- Add a short comment in the same spirit as `ActivityController::resolveImage()`'s:
  files are never deleted here, Media GC reclaims them.

`.../SecretGift/Resources/views/partials/_gift-preparation.blade.php`

- Replace the `<x-shared::image-upload …>` block (inside
  `<div x-show="giftMode === 'image'">`) with the call from architecture §4:

```blade
<x-media::image-field
    name="gift_image"
    :scope="'secret-gift/'.$activity->id"
    :path="$assignment->gift_image_path"
    :preview-url="$assignment->gift_image_path ? route('secret-gift.image', [$activity, $assignment]) : null"
    :allow-library="false"
    :show-alt="false"
    :show-caption="false"
    :max-size="5120"
    accept="image/jpeg,image/png"
    :label="__('secret-gift::secret-gift.upload_image')"
    :help-text="__('secret-gift::secret-gift.image_help')"
/>
```

  Keep the surrounding mode toggle, the text mode, the sound mode
  (`<x-shared::sound-upload>` stays) and the submit button untouched. The
  existing `secret-gift::secret-gift.upload_image` / `.image_help` lang keys are
  reused; the field's own chrome comes from `media::image-field.*`, so **no new
  lang keys**.

`app/Domains/Shared/Resources/views/components/image-upload.blade.php` —
**delete** (A6, decision #5 in architecture §7).

Explicitly **kept**: the Shared `image-upload` **lang file**, because
`app/Domains/Story/Private/Resources/views/components/cover-tab-custom.blade.php`
borrows `shared::image-upload.drop_or_click`, `.max_size` and `.size_error`
without ever using the component. Deleting it breaks the Story cover tab.
Also kept: `app/Domains/Shared/Resources/views/components/sound-upload.blade.php`
(DECISIONS #5, sound is a separate backlog task).

Before deleting, re-run the check that SecretGift was the last consumer:
`rg -n 'shared::image-upload|x-shared::image-upload' app/` should return only
the Story lang borrows.

`app/Domains/Shared/README.md` / `AGENTS.md` — remove `image-upload` from the
component inventory if it is listed there, note that its lang file survives for
Story's cover tab, and say that `sound-upload` is the remaining upload widget.
Must not reference `docs/Feature_Planning`.

### Tests

`app/Domains/Calendar/Tests/Feature/SecretGift/SaveGiftTest.php`

- Rewrite the image cases onto the new shape: `'gift_image' => ['file' => UploadedFile::fake()->image(...)]`
  for an upload, `'gift_image' => ['path' => $existing]` to keep, and
  `'gift_image' => ['path' => '']` to remove.
- `it uploads a gift image through the media image field shape` — the row holds
  a `secret-gift/{activityId}/…` path and the file is on the `private` disk.
- `it keeps the current image when only the path is submitted` — the column is
  unchanged and no new file is written.
- `it removes the image when an empty path is submitted with no file` —
  `gift_image_path` is null and, per phase 3's rule, the file is still on disk.
- `it prefers a new upload over a submitted path` — both sent; the row holds the
  newly stored path.
- `it still rejects a non image file` and `it still rejects a file over 5 MB` —
  the validation moved to `gift_image.file` and must still bite; assert the
  error key is `gift_image.file`.
- `it leaves the gift image alone when the field is not submitted` — a text-only
  submit that omits `gift_image` entirely does not clear an existing image.
- The text and sound tests must keep passing unmodified.

`app/Domains/Calendar/Tests/Feature/SecretGift/SecretGiftPageTest.php`

- `it renders the media image field without the reuse picker for the giver` —
  as the giver on an `ACTIVE` activity, the page contains the
  `{name}[file]` input for `gift_image` and does **not** contain the
  `media::image-field.choose_existing` label.
- `it previews an existing gift image through the gated route` — with a stored
  path, the rendered field's preview URL is the `secret-gift.image` route and no
  `/storage/` URL appears anywhere on the page.

New `app/Domains/Shared/Tests/Feature/SharedUploadComponentsTest.php` (or the
nearest existing Shared test file — check `app/Domains/Shared/Tests/` first and
extend rather than add if one fits)

- `it no longer ships the image upload component` — the Blade file does not
  exist, and rendering `<x-shared::image-upload />` fails.
- `it still ships the sound upload component` — renders.
- `it keeps the shared image-upload lang keys for the story cover tab` —
  `__('shared::image-upload.drop_or_click')`, `.max_size` and `.size_error`
  still resolve to real strings, not to their key names.

Also confirm the Story cover tab still renders: whatever test currently covers
`cover-tab-custom.blade.php` must pass unmodified.

### Acceptance

- ✅ The gift form posts `gift_image[path]` / `gift_image[file]`; there is no
  `gift_image_remove` field anywhere in the codebase.
- ✅ Uploading, keeping, replacing and removing a gift image all work through
  the new shape, and removal leaves the file on disk for GC.
- ✅ Invalid type and oversize files are still rejected, with the error attached
  to `gift_image.file`.
- ✅ The gift preparation page shows no "Choisir une image existante" button and
  no picker modal.
- ✅ An existing gift image previews via the `secret-gift.image` route; no
  `/storage/` URL appears on the page.
- ✅ `app/Domains/Shared/Resources/views/components/image-upload.blade.php` is
  deleted; `sound-upload.blade.php` and the `shared::image-upload` lang file are
  both still present, and the Story cover tab still renders.
- ✅ `rg 'x-shared::image-upload' app/` returns nothing.
- ✅ `./vendor/bin/sail composer deptrac` reports no new violation.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. Per assumption **A8**, VERIFY may be
skipped unless the user asks — in which case this is the smoke checklist the
user runs by hand. Roles: *giver* and *recipient* are confirmed users holding a
`calendar_secret_gift_assignments` row for the activity; *outsider* is any other
authenticated user.

| Surface | Check | OK? |
|---------|-------|-----|
| Gift preparation, image mode, no image yet (giver, ACTIVE) | Empty dropzone renders; "Téléverser" is present; **no** "Choisir une image existante" button and no picker modal | |
| Gift preparation, upload (giver, ACTIVE) | Selecting a file shows the local preview immediately, before submit; saving succeeds and the reloaded page shows the image | |
| Gift preparation, existing image (giver, ACTIVE) | The preview loads through `/calendar/secret-gift/…/image/…`; page source contains no `/storage/` URL for the gift | |
| Gift preparation, replace (giver, ACTIVE) | Uploading over an existing image shows the new one after save; the old one is no longer referenced | |
| Gift preparation, remove (giver, ACTIVE) | The trash button clears the preview; after save the field is back to its empty state | |
| Gift preparation, mobile 375 px (giver, ACTIVE) | Dropzone, buttons and preview fit without horizontal scroll; the mode toggle still works | |
| Gift preparation, text & sound modes (giver, ACTIVE) | The rich-text editor and `<x-shared::sound-upload>` still render and save exactly as before — nothing regressed by the image swap | |
| Gift preparation, activity ENDED (giver) | The warning banner shows and there is no save button; the image cannot be changed | |
| Reveal page, activity ACTIVE (recipient) | The gift image is not shown, and hitting the image URL directly returns 403 | |
| Reveal page, activity ENDED (recipient) | The image displays and the download link returns the file as an attachment | |
| Image URL, outsider | `GET /calendar/secret-gift/{a}/image/{assignment}` returns 403 | |
| Public disk, any role | `/storage/secret-gift/…` returns 404 — the bytes are not web-reachable | |
| Migrated legacy gift (giver and recipient) | A gift image uploaded **before** the deploy still displays after `artisan migrate`, at both the preview and the reveal | |
| Activity admin form, News, FAQ, Static page (admin) | `<x-media::image-field>` still shows the reuse picker and it still works — `allowLibrary` defaults to `true` | |
| Story cover tab, custom cover (author) | Still renders with its French labels — it borrows the `shared::image-upload` lang keys that survive the component's deletion | |
| Deleted-parent state | An assignment row deleted by a re-shuffle leaves no broken page: the new giver sees an empty gift form | |

## Open items

Everything below must be resolved by the phase named against it.

| # | Item | Needed by | Status |
|---|------|-----------|--------|
| O1 | `Storage::disk('private')->response()` despite `serve => false` | Phase 1 | **Resolved during PLAN.** `serve` only gates `url()`; `FilesystemAdapter::response()` streams via `readStream` and sets `Content-Type`/`Content-Length` itself. |
| O2 | Is the `private` disk configured? | Phase 1 | **Resolved during PLAN.** `config/filesystems.php` already defines it (`driver: local`, root `storage_path('app/private')`, `serve: false`). No config change. |
| O3 | Is SecretGift really the last `<x-shared::image-upload>` consumer? | Phase 4 | **Resolved during PLAN.** Only `_gift-preparation.blade.php` uses the component. Story's `cover-tab-custom.blade.php` uses three `shared::image-upload` **lang** keys and never the component — hence the lang file survives. Re-run the grep before deleting anyway. |
| O4 | GC guard granularity for private roots | Phase 1 | **Decided in this plan** (assumption A10): apply the zero-claim guard at the `secret-gift` root and sweep it recursively. Applying it per activity folder would leave shuffle orphans uncollectable, contradicting `01-functional.md` §5. If BUILD finds this unworkable, stop and surface it rather than reverting to per-folder. |
| O5 | `stream()` drops the `?int $width` parameter listed in architecture §3.1 | Phase 1 | **Decided in this plan** (assumption A9): assumption A3 means private images have no variants, so the parameter would have nothing to select. Re-add it the day private variants exist. |
| O6 | Legacy move vehicle: migration vs artisan command | Phase 3 | **Decided in this plan.** A database migration, because deploys run `artisan migrate` and a forgotten command would 404 every pre-existing gift image. Architecture §8 allows either. The logic lives in a separate `LegacyGiftImageMover` so it is testable after `Storage::fake`. |
| O7 | Production inventory of legacy gift images | Phase 3 | **Not verified — the user must check.** Before deploying phase 3, count the rows: `select count(*) from calendar_secret_gift_assignments where gift_image_path is not null and gift_image_path not like 'secret-gift/%'`, and confirm each file exists on the server under `storage/app/calendar/secret-gift/`. The mover reports missing sources instead of failing, so a mismatch is survivable, but it is better to know first. This is architecture §9's first risk. |
| O8 | Does a Shared test file already exist to extend in phase 4? | Phase 4 | **Resolved during BUILD.** None fits — `app/Domains/Shared/Tests/Feature/` holds page-level tests (header, footer, navigation, 404, theme). Component tests live in `Feature/View/Components/`, so the new file is `Feature/View/Components/UploadComponentsTest.php` (assumption A17). |
