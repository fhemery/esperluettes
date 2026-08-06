# MultiEdit — migrate the remaining ImageService consumers

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-07-29 · **Domain(s):** `Media`, `Calendar`,
`StaticPage`, `Profile`

## What it does

Finishes the adoption started by `multiedit/`: the last three domains that
called `Shared\Services\ImageService` directly — Calendar, StaticPage and
Profile — now go through `MediaPublicApi`, and `ImageService` has moved into
`Media/Private/Services` where deptrac makes it unreachable from anywhere else.
Calendar and StaticPage got the full treatment (`<x-media::image-field>` with
the reuse picker in admin, `<x-media::image>` in public views, a
`MediaUsageProvider`, deferred deletion via `media:gc`). Profile is a
deliberate exception: it only borrows `saveSquareJpg` and keeps managing its
own files. **No schema change anywhere** — this was a refactor.

## Key behaviour

- **Nothing deletes image files anymore except `media:gc`.** Removing an
  activity's or a page's image clears the path column; the file survives the
  7-day grace window and is then swept. Calendar and StaticPage each register a
  provider so the sweep knows what is still claimed.
- **New uploads are flat, old ones are grandfathered.** New files land under
  `activities/` and `static-pages/`. Images uploaded before this task still live
  under `activities/YYYY/MM/` and `static-pages/YYYY/MM/`: they render normally
  and are never swept, but they do **not** appear in the reuse picker
  (`listByScope` is non-recursive). Same grandfathering News already had.
- **The `activities` scope is new; `calendar` and `profile` were phantoms.**
  Both were declared in `MediaService::FLAT_SCOPES` and matched no folder on
  disk. They are gone.
- **No alt, no caption, no usage count** on either admin form — the fields are
  switched off, and there is no column to store them in.
- **Profile is outside the sweep.** See `Profile/AGENTS.md`.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `Media/Public/Api/MediaPublicApi.php` (`saveSquareJpg` added) |
| Image processing (now internal) | `Media/Private/Services/ImageService.php` |
| Scope registry | `Media/Private/Services/MediaService.php` (`FLAT_SCOPES`) |
| Usage providers | `Calendar/Private/Support/ActivityMediaUsageProvider.php`, `StaticPage/Private/Support/StaticPageMediaUsageProvider.php` |
| Admin forms | `Calendar/…/views/pages/admin/activities/_form.blade.php`, `StaticPage/…/views/pages/admin/_form.blade.php` |
| Public views | `Calendar/…/views/activity/show.blade.php`, `…/components/activity-card.blade.php`, `StaticPage/…/views/pages/show.blade.php` |
| Profile | `Profile/Private/Services/ProfileService.php` |
| Tests | `Calendar/Tests/Feature/Admin/ActivityImageTest.php`, `StaticPage/Tests/Feature/Admin/StaticPageImageTest.php`, `Media/Tests/Feature/MediaServiceTest.php`, `Profile/Tests/Feature/ProfileEditTest.php` |
| Migrations | none |

## Extension points used

- **`MediaUsageProvider` registry** — Calendar and StaticPage each register one
  in their `ServiceProvider::boot()`, claiming `activities.image_path` and
  `static_pages.header_image_path` respectively. Without it `media:gc` would
  either skip the scope or delete live files.

## Decisions worth remembering

- **#7 — the component goes in all three public views**, including the activity
  card with its fixed 230×220 `object-cover` crop. That was the headline layout
  risk; it held.
- **#8 — `saveSquareJpg` takes a raw target path.** Media owns the disk, the
  caller owns the path. It is the one deliberately non-scoped method on
  `MediaPublicApi`, and it exists so Profile can use Media without joining GC.
- **#2 — Profile does not join the GC loop**, and the bogus `profile` scope was
  removed rather than corrected.
- **#5 — `GET /media/library` stays `auth`-only.** Any member can enumerate any
  scope's image paths. Explicitly accepted; no privacy requirement now or
  foreseeably. Do not re-raise it.
- **#9 — `<x-shared::image-upload>` stays in Shared.** This task stripped it to
  a single consumer (SecretGift, private `local` disk, no Media semantics), so
  moving it to Media would be wrong. Its lang file stays regardless — Story's
  cover tab borrows the strings without using the component.

## Not done

Deliberate non-goals:

- Advanced (block) mode for static pages → `multiedit-static-pages/`.
- Chapters MultiEdit → `chapters-multi-edit/`.
- Backfilling grandfathered dated files into the flat folders.
- SecretGift `gift_image_path` (private `local` disk, served by a controller).
- Responsive avatars, or moving avatar deletion to the sweep.
- Tightening `/media/library` authorisation (decision #5).

Pushed to the backlog:

- **`shared-image-upload-cleanup/`** — `<x-shared::image-upload>` now has
  exactly one consumer. Decide whether SecretGift's private-disk flow moves to
  `<x-media::image-field>`, the component collapses into SecretGift, or it stays.

Verification: the visual QA checklist (in the retired `03-plan.md`) was run
manually by the maintainer rather than by the `visual-verifier` agent, so the
`OK?` column was left unfilled. The driver script written for it was discarded.
