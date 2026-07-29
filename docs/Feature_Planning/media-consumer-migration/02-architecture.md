# MultiEdit — migrate the remaining ImageService consumers — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

No new domain. **Media** is the owner and the destination: it absorbs
`ImageService` and gains one public method. The three consumers each lose their
direct dependency on `Shared\Services\ImageService`.

The end state deptrac must be able to prove: **no `*Private` layer other than
`MediaPrivate` references an image-processing class**, and
`App\Domains\Shared\Services\ImageService` no longer exists.

### 1.1 Changes in other domains

**Calendar** — direct calls, no extension point. `ActivityController` swaps
`ImageService` for `MediaPublicApi` and adopts the FAQ payload shape
(`image[file]` / `image[path]`). A new `ActivityMediaUsageProvider` is
registered in `CalendarServiceProvider::boot()`. The two public views move to
`<x-media::image>`. **Synchronous deletion is removed** — no
`deleteWithVariants` call survives.

**StaticPage** — same shape, on `header_image_path`. The image work stays in
`StaticPageService` (its existing seam) rather than moving to the controller:
minimum diff, and the service is already where `processHeaderImage` lives.
`deleteHeaderImage()` is **deleted outright**, along with the dead `string`
branch of `processHeaderImage` (decision #6).

**Profile** — the minimal case. `ProfileService` swaps
`ImageService::saveSquareJpg` for `MediaPublicApi::saveSquareJpg`. Nothing else
changes: it keeps its own `Storage::delete` calls, its own filenames, its own
default-SVG handling, and registers **no** usage provider (decision #2).

**Shared** — `Services/ImageService.php` is deleted. `<x-shared::image-upload>`
**stays** (tradeoff #5): after this task its only consumer is SecretGift, whose
images live on the private `local` disk and are outside Media entirely. Its
lang file stays regardless — Story's cover tab borrows those strings without
using the component.

## 2. Data model

**No table, no column, no migration.** Media owns no tables by design and this
task adds none. §2.1–2.3 of the template are therefore N/A; the lifecycle rules
of §5 of the spec are enforced by the usage providers below, not by the schema.

The only "data model" change is in `MediaService::FLAT_SCOPES`:

| Scope | Before | After | Reason |
|-------|--------|-------|--------|
| `activities` | absent | **added** | Calendar's real folder on disk |
| `calendar` | present | **removed** | never matched a folder; no files exist under `calendar/` |
| `profile` | present | **removed** | never matched a folder (real one is `profile_pictures/`), and Profile stays out of GC |
| `news`, `faq`, `static-pages` | present | unchanged | |

Adding `activities` also enrols the folder in `managedFolders()`, i.e. in the
sweep. That is safe on day one: `activities/` currently holds only dated
subfolders, and `originalsIn()` is non-recursive, so it returns `[]` and the
folder is skipped until the first flat upload.

## 3. PHP architecture

### 3.1 Public API

One method added to `MediaPublicApi`:

```php
/**
 * Save a square-cropped JPEG at a caller-chosen path on the managed disk.
 * The one non-scoped entry point: the caller owns the filename and the
 * file's lifecycle (Profile avatars are not garbage-collected).
 */
public function saveSquareJpg(
    string $targetPath,
    UploadedFile $file,
    int $size = 200,
    int $quality = 85,
): string
```

The `$disk` parameter of the old `ImageService::saveSquareJpg` is **dropped** —
Media owns the disk (`MediaService::DISK`). Delegates to
`MediaService::saveSquareJpg()`, which delegates to the relocated
`ImageService`.

No DTO, no event, no other signature change. `store()`, `listByScope()`,
`variantUrl()`, `originalUrl()`, `folderFor()`, `countUsages()` and
`hasVariants()` are untouched.

### 3.2 Services

**`Media\Private\Services\ImageService`** — the relocated file. Namespace
changes from `App\Domains\Shared\Services` to
`App\Domains\Media\Private\Services`; the body is unchanged. It keeps its
`$disk` parameters (they are an internal detail now, and `MediaService` is the
only caller).

**`MediaService`** — `use` statement retargeted, `FLAT_SCOPES` corrected, one
new method:

```php
public function saveSquareJpg(string $targetPath, UploadedFile $file, int $size, int $quality): string
{
    return $this->imageService->saveSquareJpg(self::DISK, $targetPath, $file, $size, $quality);
}
```

**`StaticPageService`** — `processHeaderImage(?UploadedFile $file): ?string`
becomes a one-line delegation to `MediaPublicApi::store('static-pages', $file)`.
`deleteHeaderImage()` and all three of its call sites are removed; `update()`
and `delete()` simply stop referencing the old path. `ImageService` is injected
via the constructor instead of `app()` — the existing `app(ImageService::class)`
service-location is replaced by a constructor-injected `MediaPublicApi`.

**`ProfileService`** — `private readonly ImageService $images` becomes
`private readonly MediaPublicApi $media`; the single call site drops the
`'public'` disk argument. `uploadProfilePicture`, `deleteProfilePicture` and the
user-deletion cleanup keep their `Storage::disk('public')->delete()` calls
verbatim.

**Usage providers** — two new classes, both mirroring
`FaqMediaUsageProvider` exactly:

```php
// Calendar\Private\Support\ActivityMediaUsageProvider
Activity::query()->whereNotNull('image_path')->pluck('image_path')->all();

// StaticPage\Private\Support\StaticPageMediaUsageProvider
StaticPage::query()->whereNotNull('header_image_path')->pluck('header_image_path')->all();
```

Both claim grandfathered dated paths too — harmless, since the sweep never
descends into those folders, and it keeps the providers honest about what the
domain references.

### 3.3 Policy / authorization

Unchanged. Both admin surfaces are already gated by route middleware
(`role:admin,tech-admin`); the picker inherits it. `GET /media/library` stays
`auth`-only (decision #5). No policy class is added.

### 3.4 Events and listeners

None added. `ProfileService` keeps emitting `AvatarChanged` unchanged — the
event carries the path, and the path shape does not change.

### 3.5 Routes, controllers, form requests

No new route, no route verb change (nothing here uses `PATCH`).

**`ActivityRequest`** — replaces `image` / `image_remove` with the FAQ payload:

```php
'image'      => ['nullable', 'array'],
'image.file' => ['nullable', 'image', 'max:2048'],
'image.path' => ['nullable', 'string', 'max:1024'],
```

The `prepareForValidation()` boolean coercion of `image_remove` goes away —
removal is now expressed by an empty `image.path` with no `image.file`.

**`StaticPageRequest`** — same substitution on `header_image` /
`header_image_remove`.

**`ActivityController`** — gains a private `resolveImage()` identical in shape
to `FaqQuestionController::resolveImage()` (minus alt), returning `?string`:

```php
$file = $request->file('image.file');
return $file ? $this->media->store('activities', $file) : (($data['image']['path'] ?? null) ?: null);
```

`store()` and `update()` both call it; `update()` no longer deletes anything.

## 4. Frontend architecture

**Admin forms** — `<x-shared::image-upload>` is replaced by
`<x-media::image-field>` in both forms, with alt, caption and usage count off
(decisions #3, #4):

```blade
<x-media::image-field
    name="image"                       {{-- header_image for StaticPage --}}
    scope="activities"                 {{-- static-pages for StaticPage --}}
    :path="old('image.path', $activity?->image_path)"
    :show-alt="false"
    :show-caption="false"
    :label="__('calendar::admin.activities.form.image')"
/>
```

**Public views** — all three move to `<x-media::image>` (tradeoff #1):

- `StaticPage/…/pages/show.blade.php` — the hand-rolled `<picture>` block and
  its `@php` path-splitting are deleted; replaced by
  `<x-media::image :path="$page->header_image_path" alt="" img-class="w-full h-auto" />`
  inside the existing `<figure>`-equivalent wrapper (the component brings its
  own `<figure>`, so the outer one goes).
- `Calendar/…/activity/show.blade.php` — plain swap, `:alt="$activity->name"`.
- `Calendar/…/components/activity-card.blade.php` — the fixed 230×220 crop is
  preserved by passing `img-class="w-[230px] h-[220px] object-cover"` and
  neutralising the component's `text-center` figure via `class="contents"`, so
  the `<figure>` does not introduce a layout box inside the fixed-size div.
  **This is the one visual regression risk in the task** (§9, risk 1).

No new Alpine store, no new JS module, no new CSS — `mediaImageField` and the
library endpoint already exist and are already loaded on admin pages.

**i18n** — the picker's strings live in `media::image-field`. Existing Calendar
and StaticPage labels are reused as-is. Two lang keys become unused
(`static::admin.form.header_image_help` stays; the `*_remove` helper strings, if
any, are removed with their fields).

## 5. Deptrac

Five new edges, all of the already-blessed `X → MediaPublic` shape that FAQ and
News carry today:

| Edge | Why |
|------|-----|
| `CalendarPrivate → MediaPublic` | `ActivityController` calls `MediaPublicApi`; `ActivityMediaUsageProvider` implements the Media contract |
| `CalendarPublic → MediaPublic` | `CalendarServiceProvider::boot()` resolves `MediaUsageRegistry` |
| `StaticPagePrivate → MediaPublic` | `StaticPageService` calls `MediaPublicApi` |
| `StaticPagePublic → MediaPublic` | `StaticPageServiceProvider::boot()` resolves `MediaUsageRegistry` |
| `ProfilePrivate → MediaPublic` | `ProfileService` calls `MediaPublicApi::saveSquareJpg` |

`ProfilePublic → MediaPublic` is **not** needed: Profile registers no provider.

`MediaPrivate → Shared` already exists and stays (Shared is universally
allowed); after relocation Media no longer depends on Shared *for images*.

The removals are what makes the task verifiable: once `Shared\Services\
ImageService` is gone, no layer can depend on it, so deptrac itself proves the
migration is complete.

## 6. Testing strategy

Integration (feature) tests, per the project default. Per consumer:

**Calendar** (`Calendar/Tests/Feature/Admin/`)
1. Creating an activity with an upload stores the file under `activities/`
   (flat, not dated) and persists the returned path.
2. Updating with `image.path` set to an existing path reuses it and stores no
   new file.
3. Updating with an empty payload clears `image_path` **and leaves the file on
   disk** — the explicit regression guard for deferred deletion.
4. `ActivityMediaUsageProvider` reports every non-null `image_path`
   (the guard the spec's §5 demands).

**StaticPage** (`StaticPage/Tests/Feature/Admin/`) — the same four, on
`header_image_path` and the `static-pages` scope.

**Profile** (`Profile/Tests/Feature/`) — the existing avatar tests must pass
unchanged; add one asserting the 200×200 JPEG still lands at
`profile_pictures/{userId}_*.jpg` and that **no** variant files are generated.

**Media** (`Media/Tests/Feature/MediaServiceTest.php`) — `folderFor('activities')`
resolves; `folderFor('calendar')` and `folderFor('profile')` now **throw**;
`saveSquareJpg` writes at the given path on the managed disk.

**Sweep safety** — one test asserting `gc()` skips `activities/` and
`static-pages/` when the providers claim nothing, and never descends into
`activities/2026/07/`.

**Vitest** — none. `mediaImageField` is already covered.

**Only VERIFY can check**: the activity card's 230×220 crop, the static-page
header's full-bleed rendering, and the picker actually opening in both admin
forms (Alpine does not run in feature tests).

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How far does the display migration go? | (a) `<x-media::image>` in all three views; (b) component for StaticPage, `variantUrl()` for Calendar's plain `<img>`; (c) storage only, views untouched | **(a)** | Removes StaticPage's duplicated `<picture>` and stops Calendar serving full-size originals in a 230-px box. Cost is a layout risk on the card, which VERIFY covers. (c) would leave Media not actually being the sole entry point. |
| 2 | How does `saveSquareJpg` reach Profile? | (a) raw target-path passthrough on `MediaPublicApi`; (b) a `profile-pictures` scope; (c) leave it in Shared | **(a)** | (b) would enrol `profile_pictures/` in the sweep, contradicting decision #2, and change avatar filenames. (c) blocks the relocation and the task's whole purpose. (a) is one honest exception to the scope invariant. |
| 3 | Where does image resolution live per consumer? | Controller (FAQ style) vs service | **Follow each domain's existing seam**: controller for Calendar, service for StaticPage | Decided in DESIGN, not asked. Minimum diff (rule #3); both end up depending on `MediaPublic` either way. |
| 4 | Do the providers claim grandfathered dated paths? | Claim everything vs filter to flat paths | **Claim everything** | A provider should state what the domain references, full stop. The sweep ignores dated folders anyway, so filtering would add code with no effect. |
| 5 | Does `<x-shared::image-upload>` get deleted, or move to Media? | (a) keep in Shared; (b) move into Media; (c) delete and inline into its last consumer | **(a) keep** | This task removes two of its three consumers. **Story's cover tab never used the component** — only its `shared::image-upload` lang strings, so the lang file stays either way. That leaves **SecretGift as the sole consumer**, and its images are private-disk, route-served, variant-less and never swept — none of Media's path/scope/GC model applies, so (b) would hand Calendar a `MediaPublic` edge for a component with no Media semantics. (c) is the honest cleanup but drags the gift-preparation flow into an `ImageService` task. WRAP proposes it as a follow-up row instead. |

## 8. File layout

```
app/Domains/Media/
  Private/Services/ImageService.php                        ← MOVED from Shared
  Private/Services/MediaService.php                        ← FLAT_SCOPES, saveSquareJpg, use
  Public/Api/MediaPublicApi.php                            ← saveSquareJpg
  Tests/Feature/MediaServiceTest.php                       ← scopes + saveSquareJpg
  README.md · AGENTS.md                                    ← WRAP

app/Domains/Shared/
  Services/ImageService.php                                ← DELETED

app/Domains/Calendar/
  Private/Support/ActivityMediaUsageProvider.php           ← NEW
  Public/Providers/CalendarServiceProvider.php             ← register provider
  Private/Controllers/Admin/ActivityController.php         ← resolveImage, no deletion
  Private/Requests/ActivityRequest.php                     ← image[] payload
  Private/Resources/views/pages/admin/activities/_form.blade.php   ← image-field
  Private/Resources/views/activity/show.blade.php                  ← x-media::image
  Private/Resources/views/components/activity-card.blade.php       ← x-media::image
  Tests/Feature/Admin/ActivityImageTest.php                ← NEW

app/Domains/StaticPage/
  Private/Support/StaticPageMediaUsageProvider.php         ← NEW
  Public/Providers/StaticPageServiceProvider.php           ← register provider
  Private/Services/StaticPageService.php                   ← store via Media, drop delete
  Private/Requests/StaticPageRequest.php                   ← header_image[] payload
  Private/Resources/views/pages/admin/_form.blade.php      ← image-field
  Private/Resources/views/pages/show.blade.php             ← x-media::image
  Tests/Feature/Admin/StaticPageImageTest.php              ← NEW

app/Domains/Profile/
  Private/Services/ProfileService.php                      ← MediaPublicApi
  Tests/Feature/ProfilePictureTest.php                     ← extend

deptrac.yaml                                               ← 5 edges
```

## 9. Risks acknowledged

1. **The activity card's fixed 230×220 crop.** `<x-media::image>` emits a
   `<figure class="media-image text-center">` wrapper the current markup does
   not have. If `class="contents"` does not neutralise it cleanly, the card
   layout shifts. *Trigger to revisit*: VERIFY shows any card movement — fall
   back to tradeoff #1 option (b) for the card only, keeping the component in
   the other two views.
2. **Admins lose immediate deletion without being told.** Nothing in the UI
   says the file survives removal. Accepted for an admin-only surface.
   *Trigger*: a first support question about disk usage.
3. **`activities/` and `static-pages/` join the sweep.** The unclaimed-scope
   guard plus the 7-day window make a provider mistake recoverable, but only
   within that window. The provider tests (§6) are the real defence.
4. **Payload shape change is a breaking form change.** Any bookmarked or
   scripted POST to those two admin endpoints with the old `image` /
   `header_image` field names silently stops attaching an image. Admin-only, no
   API consumers — accepted.
5. **`processHeaderImage()`'s `string` branch is assumed dead.** Confirmed by
   grep across `app/` and tests (no caller passes a string). *Trigger*: the gate
   fails on a call site the grep missed.
