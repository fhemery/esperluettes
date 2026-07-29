# MultiEdit — migrate the remaining ImageService consumers — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads one phase at a time.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — 9 decisions, all settled. None
  are re-opened here.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Media scope corrections (`FLAT_SCOPES`) | S | — | DONE |
| 2 | Calendar → `MediaPublicApi` | M | 1 | DONE |
| 3 | StaticPage → `MediaPublicApi` | M | 1 | DONE |
| 4 | Profile → `MediaPublicApi::saveSquareJpg` | S | — | TODO |
| 5 | Relocate `ImageService` into Media | S | 2, 3, 4 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/5)` resume correctly.

**No migration, no table, no column.** This is a refactor: the bottom-up rule of
the slicing skill degrades to "the scope registry first, then each consumer, then
the relocation". §2 of the architecture is explicit that the schema is untouched
— any BUILD phase that finds itself writing a migration has misread the task.

**Reference implementation.** FAQ is the worked example of every single piece
below: `FAQ/Private/Controllers/Admin/FaqQuestionController::resolveImage()`,
`FAQ/Private/Support/FaqMediaUsageProvider`, the `image[]` rules in
`FaqQuestionRequest`, and the `<x-media::image-field>` call in FAQ's admin
`_form.blade.php`. Phases 2 and 3 are "do what FAQ does". Read those four files
rather than re-deriving the shape from this plan.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Phases 2, 3 and 4 are mutually independent once phase 1 has landed (phase 4
  does not even need phase 1). Phase 5 needs all three. The architecture's
  stated order — Calendar → StaticPage → Profile → relocation — is kept because
  Calendar is the riskiest and buys the most learning, but 2/3/4 may be
  reordered without consequence if something blocks.

### Why the `FLAT_SCOPES` fix is its own phase, and `saveSquareJpg` is not

Both were candidates for a single "phase 0" of Media groundwork. They are split:

- **`FLAT_SCOPES` (phase 1) stands alone.** Removing `calendar` and `profile` is
  a self-contained cleanup of two scopes that never matched a folder — verified:
  no production caller passes either string to `folderFor()` (only
  `MediaLibraryController` calls it, with a user-supplied scope, and it is meant
  to reject unknown ones). Adding `activities` enrols a folder in the sweep, and
  *that* deserves its own test and its own revertable commit rather than being
  buried in the Calendar diff — it is the one change in this task that can delete
  files.
- **`saveSquareJpg` (in phase 4) does not.** Its only caller is `ProfileService`.
  Shipping a public API method three phases before anything calls it is
  speculative code (AGENTS rule #2) and leaves dead public surface if the task
  stalls. It costs nothing to land it in the same commit as its caller, and
  `MediaServiceTest` covers it there just as well.

The consequence is that phase 4 touches Media *and* Profile. That is accepted:
it is still one small, green, revertable commit.

---

## Phase 1 — Media scope corrections

**Goal.** Make `MediaService::FLAT_SCOPES` describe the folders that actually
exist on disk, so Calendar has a scope to store into and the two phantom scopes
stop lying.

**Deliverables.**
- `app/Domains/Media/Private/Services/MediaService.php` — `FLAT_SCOPES` becomes
  `['news', 'faq', 'static-pages', 'activities']`: `activities` added,
  `calendar` and `profile` removed (architecture §2 table, decision #2).
- `app/Domains/Media/Tests/Feature/MediaServiceTest.php` — extended.

Nothing outside Media is touched. No consumer uses the new scope yet; the
`activities/` folder currently holds only dated subfolders, and `originalsIn()`
is non-recursive, so enrolling it in `managedFolders()` is inert on day one.

**Tests.** (`app/Domains/Media/Tests/Feature/MediaServiceTest.php`)
- `folderFor('activities')` returns `activities`.
- `folderFor('calendar')` throws — it is no longer a known scope.
- `folderFor('profile')` throws — same.
- `folderFor('news' | 'faq' | 'static-pages')` unchanged (existing assertions
  must still pass).
- **Sweep safety, the one that matters:** with files present only under
  `activities/2026/07/` and no usage provider claiming anything, `gc()` deletes
  nothing and never descends into the dated subfolder. This is the guard for
  §5 of the spec ("provider forgotten for a scope") and risk 3 of §9.

**Acceptance.**
- ✅ `folderFor('activities')` resolves; `folderFor('calendar')` and
  `folderFor('profile')` both throw.
- ✅ `media:gc` leaves every file under `activities/YYYY/MM/` untouched, with or
  without a registered provider.
- ✅ `GET /media/library?scope=calendar` (or `profile`) is rejected as an unknown
  scope, exactly as `bogus` already is.
- ✅ No file outside `app/Domains/Media/` is modified.
- ✅ `npm run gate` green.

---

## Phase 2 — Calendar → `MediaPublicApi`

**Goal.** Move activity images onto the `activities` scope end to end: storage
through `MediaPublicApi`, reuse picker in the admin form, `<x-media::image>` on
both public views, and a usage provider so the files are visible to the sweep.

Follow the FAQ recipe (architecture §3.5 and §4); do not invent a variant of it.

**Deliverables.**
- `app/Domains/Calendar/Private/Support/ActivityMediaUsageProvider.php` — **new**,
  mirroring `FaqMediaUsageProvider`; returns every non-null `image_path`,
  including grandfathered dated ones (decision/tradeoff #4).
- `app/Domains/Calendar/Public/Providers/CalendarServiceProvider.php` — register
  the provider in `boot()` against `MediaUsageRegistry` (see
  `NewsServiceProvider::boot()` for the exact call).
- `app/Domains/Calendar/Private/Requests/ActivityRequest.php` — `image` /
  `image_remove` replaced by the `image` / `image.file` / `image.path` rules of
  §3.5; the `prepareForValidation()` boolean coercion of `image_remove` is
  deleted.
- `app/Domains/Calendar/Private/Controllers/Admin/ActivityController.php` —
  `ImageService` dependency replaced by `MediaPublicApi`; private
  `resolveImage()` added (shape of `FaqQuestionController::resolveImage()`, minus
  alt); `store()` and `update()` call it. **The `deleteWithVariants()` call in
  `update()` is removed and not replaced** (decision #1: deletion is deferred to
  the sweep). The `'activities/' . date('Y/m')` folder literal disappears with it.
- `app/Domains/Calendar/Private/Resources/views/pages/admin/activities/_form.blade.php`
  — `<x-shared::image-upload>` → `<x-media::image-field name="image"
  scope="activities" :show-alt="false" :show-caption="false">` (decisions #3, #4).
- `app/Domains/Calendar/Private/Resources/views/activity/show.blade.php` —
  `<x-media::image :path="$activity->image_path" :alt="$activity->name" />`.
- `app/Domains/Calendar/Private/Resources/views/components/activity-card.blade.php`
  — `<x-media::image>` with `img-class="w-[230px] h-[220px] object-cover"` and
  `class="contents"` on the figure. **Note:** the component *merges* attributes,
  so the rendered class is `contents media-image text-center` — `display:contents`
  must be what neutralises the wrapper, not the absence of the other classes.
  See open item O1.
- Any now-unused Calendar lang key for the old remove checkbox is deleted with
  its field.
- `deptrac.yaml` — `CalendarPrivate → MediaPublic` and
  `CalendarPublic → MediaPublic` (architecture §5).

**Tests.** (`app/Domains/Calendar/Tests/Feature/Admin/ActivityImageTest.php`,
new; `ActivityControllerTest.php` updated for the new payload shape)
- `test_creating_an_activity_with_an_upload_stores_it_flat_under_activities` —
  the stored path is `activities/<name>.<ext>`, **not** `activities/YYYY/MM/…`,
  and it is what is persisted on the row.
- `test_updating_with_an_existing_path_reuses_it_and_stores_no_new_file` —
  `image[path]` set, no `image[file]`: the row keeps that path and the disk file
  count does not change.
- `test_removing_the_image_clears_the_path_but_leaves_the_file_on_disk` — the
  explicit regression guard for deferred deletion (§4.3, §6).
- `test_activity_usage_provider_reports_every_non_null_image_path` — including a
  grandfathered `activities/2026/07/…` path (the guard §5 of the spec demands).
- `test_a_non_admin_cannot_post_to_the_activity_admin_endpoints` — the existing
  route gate must still hold after the payload change (keep or assert the
  existing coverage in `ActivityControllerTest`).

**Acceptance.**
- ✅ A new upload lands directly under `activities/` with its 400w/800w variants.
- ✅ Saving with `image[path]` pointing at an existing library image creates no
  new file and persists that path.
- ✅ Clearing the image nulls `image_path` and the file is still on disk
  afterwards.
- ✅ `ActivityMediaUsageProvider` is registered and claims every non-null
  `image_path`; `media:gc` therefore never deletes a referenced activity image.
- ✅ `grep -rn "ImageService" app/Domains/Calendar/` returns nothing.
- ✅ Grandfathered `activities/YYYY/MM/…` images still render (same
  `-{width}w.{ext}` naming, same `[400, 800]` widths — verified against
  `ImageService::process()` and `MediaService::variantUrl()`), and are absent
  from the picker.
- ✅ Deptrac green with the two new edges and no `Calendar → Shared\ImageService`.
- ✅ `npm run gate` green.

---

## Phase 3 — StaticPage → `MediaPublicApi`

**Goal.** Same migration for the static-page header image, keeping the image
work in `StaticPageService` — the domain's existing seam (tradeoff #3).

**Deliverables.**
- `app/Domains/StaticPage/Private/Support/StaticPageMediaUsageProvider.php` —
  **new**, over `header_image_path`.
- `app/Domains/StaticPage/Public/Providers/StaticPageServiceProvider.php` —
  register it in `boot()`.
- `app/Domains/StaticPage/Private/Services/StaticPageService.php` —
  `MediaPublicApi` constructor-injected, replacing the `app(ImageService::class)`
  service location; `processHeaderImage(?UploadedFile $file): ?string` becomes a
  one-line delegation to `store('static-pages', $file)`, and its dead `string`
  branch goes (decision #6, assumption A3 — re-grep to confirm before deleting);
  `deleteHeaderImage()` **and all three of its call sites** are removed, so
  `update()` and `delete()` stop deleting files.
- `app/Domains/StaticPage/Private/Requests/StaticPageRequest.php` —
  `header_image` / `header_image_remove` replaced by the `header_image[]` rules.
- `app/Domains/StaticPage/Private/Resources/views/pages/admin/_form.blade.php` —
  `<x-media::image-field name="header_image" scope="static-pages"
  :show-alt="false" :show-caption="false">`.
- `app/Domains/StaticPage/Private/Resources/views/pages/show.blade.php` — the
  hand-rolled `<picture>` block and its `@php` path-splitting are **deleted**,
  replaced by `<x-media::image :path="$page->header_image_path" alt=""
  img-class="w-full h-auto" />`. The component brings its own `<figure>`, so the
  outer wrapper goes with it.
- `deptrac.yaml` — `StaticPagePrivate → MediaPublic`,
  `StaticPagePublic → MediaPublic`.

**Tests.** (`app/Domains/StaticPage/Tests/Feature/Admin/StaticPageImageTest.php`,
new; `StaticPageControllerTest.php` / `UpdateStaticPageTest.php` /
`DeleteStaticPageTest.php` updated where they assert deletion)
- The same four as phase 2, on `header_image_path` and the `static-pages` scope:
  flat upload, reuse-by-path, removal-leaves-file-on-disk, provider-claims-all.
- `test_deleting_a_static_page_leaves_its_header_file_on_disk` — the third call
  site of the removed `deleteHeaderImage()`; deletion is now the sweep's job.
- `test_a_non_admin_cannot_post_to_the_static_page_admin_endpoints` — existing
  gate preserved through the payload change.

**Acceptance.**
- ✅ Uploads land flat under `static-pages/`; reuse persists the chosen path with
  no new file.
- ✅ Removing the header, and deleting the page itself, both leave the file on
  disk.
- ✅ `deleteHeaderImage()` no longer exists anywhere in the codebase, and
  `processHeaderImage()` accepts only `?UploadedFile`.
- ✅ `grep -rn "ImageService" app/Domains/StaticPage/` returns nothing.
- ✅ Existing grandfathered `static-pages/YYYY/MM/…` headers still render.
- ✅ Deptrac green with the two new edges.
- ✅ `npm run gate` green.

---

## Phase 4 — Profile → `MediaPublicApi::saveSquareJpg`

**Goal.** Give Media the one non-scoped entry point Profile needs (decision #8)
and switch `ProfileService` onto it, changing nothing a user can see.

This is the minimal case: **no usage provider, no `<x-media::image>`, no scope**
(decision #2). Profile keeps its own filenames, its own `Storage::delete()`
calls, its own default-SVG handling, and stays out of the sweep entirely.

**Deliverables.**
- `app/Domains/Media/Public/Api/MediaPublicApi.php` — `saveSquareJpg(string
  $targetPath, UploadedFile $file, int $size = 200, int $quality = 85): string`,
  documented as the deliberate exception to the scope invariant. The old `$disk`
  parameter is dropped: Media owns `MediaService::DISK`.
- `app/Domains/Media/Private/Services/MediaService.php` — thin
  `saveSquareJpg()` delegating to `ImageService` with `self::DISK`.
- `app/Domains/Profile/Private/Services/ProfileService.php` — `ImageService
  $images` → `MediaPublicApi $media`; the single call site drops the `'public'`
  argument. `uploadProfilePicture`, `deleteProfilePicture` and the user-deletion
  cleanup keep their `Storage::disk('public')->delete()` calls **verbatim**, and
  `AvatarChanged` keeps being emitted unchanged (§3.4).
- `deptrac.yaml` — `ProfilePrivate → MediaPublic`. **Not**
  `ProfilePublic → MediaPublic`: Profile registers no provider.

**Tests.**
- `app/Domains/Media/Tests/Feature/MediaServiceTest.php` —
  `test_save_square_jpg_writes_at_the_given_path_on_the_managed_disk`: the file
  lands exactly at `$targetPath`, is a 200×200 JPEG, and **no `-{w}w` variant is
  generated next to it**.
- Profile avatar coverage — extend the existing tests (see open item O2 for
  where they live) with:
  `test_uploading_a_profile_picture_still_lands_at_profile_pictures_userid_jpg`
  and `test_no_media_variants_are_generated_for_an_avatar`.
- The whole existing Profile feature suite must pass **unchanged** — that is the
  real acceptance criterion for §4.4. Do not adjust an existing Profile
  assertion to make this phase pass; if one fails, the migration is wrong.
- `test_replacing_an_avatar_still_deletes_the_previous_file_immediately` — the
  guard that Profile did *not* inherit deferred deletion.

**Acceptance.**
- ✅ Avatars are still a single 200×200 JPEG at
  `profile_pictures/{userId}_*.jpg`, with no responsive variants.
- ✅ Replacing or removing an avatar still deletes the old file immediately.
- ✅ Default generated SVG avatars still work; `AvatarChanged` still fires with
  the same payload shape.
- ✅ No existing Profile test was modified to accommodate the change.
- ✅ `grep -rn "ImageService" app/Domains/Profile/` returns nothing.
- ✅ Deptrac green with `ProfilePrivate → MediaPublic` and **no**
  `ProfilePublic → MediaPublic`.
- ✅ `npm run gate` green.

---

## Phase 5 — Relocate `ImageService` into Media

**Goal.** The finish line: `App\Domains\Shared\Services\ImageService` ceases to
exist, and deptrac proves the migration is complete.

This phase can only run once phases 2, 3 and 4 have landed —
`grep -rn "Shared\\\\Services\\\\ImageService" app/` must return nothing outside
Media before starting. That grep is the entry condition.

**Deliverables.**
- `app/Domains/Media/Private/Services/ImageService.php` — the moved file.
  Namespace `App\Domains\Shared\Services` → `App\Domains\Media\Private\Services`;
  **the body is unchanged**, `$disk` parameters included (they are an internal
  detail now, and `MediaService` is the only caller).
- `app/Domains/Shared/Services/ImageService.php` — **deleted**.
- `app/Domains/Media/Private/Services/MediaService.php` — `use` retargeted.
- Any container binding or `use` left pointing at the old FQCN — updated.
- `deptrac.yaml` — if a Shared-image-specific edge or exception exists, it is
  removed. `MediaPrivate → Shared` stays (Shared is universally allowed).
- `<x-shared::image-upload>` **stays where it is**, with its lang file
  (decision #9). Do not delete it; WRAP proposes the follow-up row.

**Tests.**
- No new test. The existing Media, FAQ, News, Calendar, StaticPage and Profile
  suites are the regression net; a namespace move that breaks anything shows up
  there immediately.
- If a test referenced the old FQCN directly, retarget it — that is the only
  permitted test edit in this phase.

**Acceptance.**
- ✅ `grep -rn "Shared\\\\Services\\\\ImageService" app/ tests/` returns nothing.
- ✅ `app/Domains/Shared/Services/ImageService.php` does not exist.
- ✅ The only class importing `Media\Private\Services\ImageService` is
  `MediaService`.
- ✅ `<x-shared::image-upload>` still exists and SecretGift's flow is untouched.
- ✅ Deptrac green — and it is deptrac, not a grep, that now makes any future
  `*Private → image processing` edge impossible.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Risk 1 of architecture §9 — the activity card's fixed 230×220
`object-cover` crop under `<x-media::image>` — is the headline item; rows 1 and 2
are its verdict. The fallback if it fails is tradeoff #1 option (b) **for the
card only**: keep the raw `<img>` with `variantUrl()` there, component everywhere
else.

| Surface | Role | Check | OK? |
|---------|------|-------|-----|
| Activity card, calendar/home listing (desktop) | guest | Image is still exactly 230×220, cropped not squashed; card height and neighbours unchanged vs. before the migration (compare with a `git stash` screenshot) | |
| Activity card (mobile, ~375px) | guest | Card does not overflow or reflow; the `display:contents` figure introduces no extra box or centring artefact | |
| Activity card, activity with **no** image | guest | No empty figure, no broken image, no layout hole — the component renders nothing when `$path` is null | |
| Activity show page | guest | Image renders at a sensible size with `alt` = activity name; responsive variant served (check `srcset` in devtools, not the full-size original) | |
| Activity show page — **known width change** (found in phase 2) | guest | The old `<img>` was a direct flex item of a `flex flex-col gap-4` column, so `align-items: stretch` widened it to the container. Under the component's `<figure>` the img is inline and centred at its natural width, capped by `max-w-full`. Confirm the narrower, centred image is acceptable — it is inherent to the plain swap decision #7 authorised, but it *is* visible. If not acceptable, pass `img-class="w-full h-auto"` | |
| Activity show page, **grandfathered** dated image (`activities/2026/07/…`) | guest | Still displays; its `-400w`/`-800w` variants resolve | |
| Static page show, page with a header | guest | Header is full-bleed as before (`w-full h-auto`), no double `<figure>`, no leftover wrapper margin; compare against the previous hand-rolled `<picture>` | |
| Static page show, page with **no** header | guest | Renders cleanly, no gap | |
| Static page show (mobile) | guest | Header scales, no horizontal scroll | |
| Activity admin form — create | admin | Picker opens (« Choisir une image existante »), upload works, preview shows, **no alt / caption / usage-count fields** | |
| Activity admin form — edit, existing image | admin | Current image previews; Remove clears the preview and, after save, the activity shows no image | |
| Activity picker, `activities` scope | admin | On day one the list is **empty** (all existing files are in dated folders) — confirm the empty state is not a broken/blank panel; after one upload the new image appears | |
| Static page admin form — create + edit | admin | Same three actions, same absent fields, picker lists the `static-pages` scope | |
| Both admin forms, validation error replay | admin | After a failed save, the chosen/uploaded path survives via `old('image.path')` — the image is not silently lost | |
| Both admin forms (mobile) | admin | `<x-media::image-field>` layout usable at ~375px (inherited from FAQ/News, confirm not regressed) | |
| Profile picture edit | user-confirmed | Upload still produces a 200×200 avatar; it appears immediately everywhere the avatar shows | |
| Profile with default (generated SVG) avatar | user-confirmed | Unchanged rendering | |
| Deleted parent / removed image | admin then guest | After removing an activity's image and saving, the public card and show page stop displaying it — while the file is still on disk (deferred deletion, §4.3) | |

## Open items

Each must be resolved before the phase that needs it starts.

- **O1 — `class="contents"` on `<x-media::image>` merges, it does not replace.**
  *Phase 2.* Verified by reading
  `Media/Private/Resources/views/components/image.blade.php`: the figure renders
  `$attributes->merge(['class' => 'media-image text-center'])`, so the card's
  figure will carry `contents media-image text-center`. `display:contents`
  should make `text-center` moot, but this is exactly risk 1 and only VERIFY can
  settle it. Also confirm Tailwind's `contents` utility survives the build (it is
  a core utility, but it may not be in use anywhere else in the project yet).
- **O2 — `Profile/Tests/Feature/ProfilePictureTest.php` does not exist.**
  *Phase 4.* Architecture §8 names it as a file to extend. The real avatar
  coverage lives in `app/Domains/Profile/Tests/Feature/ProfileEditTest.php`
  (plus `ProfileModerationEmptyImageTest.php` and `UserDeletedCleanupTest.php`).
  BUILD should extend `ProfileEditTest.php` rather than create the named file,
  unless the new assertions justify a dedicated one.
- **O3 — the exact lang keys retired with the old remove checkbox.** *Phases 2
  and 3.* `static::admin.form.header_image_help` stays per §4; the `*_remove`
  helper strings, if any exist in Calendar's and StaticPage's lang files, go with
  their fields. Grep at the start of each phase; do not touch
  `shared::image-upload`'s lang file (decision #9 — Story borrows those strings).
- **O4 — `processHeaderImage()`'s dead `string` branch.** *Phase 3.* Assumption
  A3, re-confirmed by grep at DESIGN, but re-run it once more before deleting
  (risk 5 of §9): a call site in a seeder or command would fail the gate.
- **Verified, not open** (recorded so BUILD does not re-check): `x-admin::layout`
  supports `@push('scripts')` and both admin forms already use it, so the
  `mediaImageField` Alpine component — registered via `@once @push('scripts')`
  inside `image-field.blade.php` — needs no bundling work. No production code
  calls `folderFor('calendar')` or `folderFor('profile')`. Both current
  consumers pass widths `[400, 800]`, matching the component's default, so
  grandfathered images resolve through `variantUrl()` unchanged.
