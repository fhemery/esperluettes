# MultiEdit — migrate the remaining ImageService consumers — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Calendar, StaticPage and Profile still call `Shared\Services\ImageService`
directly, so the Media domain is not yet the single entry point for images that
its own documentation claims. This task moves those three consumers onto
`MediaPublicApi` and then relocates `ImageService` into Media.

For end users the change is almost invisible. The one visible gain is for
**admins**: the activity form and the static-page form get the same image
control FAQ and News already have — upload, remove, and **reuse an image
already in the same library**. The one visible loss is that removing an image no
longer deletes its file immediately; the file is reclaimed later by the
scheduled sweep.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Scope (*portée*) | A logical image bucket that maps to one folder on the public disk (`activities`, `static-pages`, `faq`, `news`, …). The reuse picker lists one scope at a time. |
| Usage provider | The declaration by which a domain tells Media "these are the image paths I still reference". Without it, that domain's files are invisible to the sweep. |
| Sweep (`media:gc`) | The daily 03:30 job that deletes images no provider claims and older than 7 days. |
| Grandfathered image | An existing file sitting in a dated subfolder (`activities/2026/07/…`, `static-pages/2025/08/…`). Still displayed normally, but never listed in the reuse picker and never swept. |
| « Choisir une image existante » | The reuse picker's label in the admin forms. |

## 3. Roles & visibility

Nothing in this task changes who may see or do what. The table records the
status quo the migration must preserve.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Activity images and static-page header images on the public pages, as today | — |
| `user` (non-confirmed) | Same as guest | — |
| `user-confirmed` | Same as guest | Upload their **own** profile picture (unchanged) |
| Author / co-author of the target | N/A — these are site-managed images, not author-owned content | — |
| Moderator | Nothing specific | — |
| Admin / tech-admin | The activity and static-page admin forms | Upload, remove **and now reuse** images in those two forms |

Both admin surfaces are already gated on `role:admin,tech-admin`
(`Calendar/Private/routes.php`, `StaticPage/Private/routes.php`); the reuse
picker inherits that gate.

## 4. Functional requirements

### 4.1 Admin adds or replaces an activity image

1. The admin opens the activity create or edit form.
2. The image control offers three actions: upload a file, remove the current
   image, or **« Choisir une image existante »**.
3. The picker lists images stored **directly under `activities/`**, newest
   first, paginated. Grandfathered images in `activities/YYYY/MM/` are **not**
   listed (§5).
4. On save, a newly uploaded file is stored in the `activities` scope with its
   responsive variants; a reused image simply re-uses its existing path.
5. No alt or caption input is shown. The public page keeps using the activity
   name as the image's alt text.
6. No usage count is shown.

### 4.2 Admin adds or replaces a static-page header image

Identical to 4.1, with the `static-pages` scope. The public page keeps
rendering the header as a decorative image (empty alt), as it does today.

### 4.3 Admin removes an image

1. The admin removes the image and saves.
2. The page or activity immediately stops showing it.
3. **The file is not deleted at that moment.** If no other content references
   it, the sweep reclaims it once it is more than 7 days old. If another page or
   activity reuses the same path, the file is kept.

This is a deliberate consequence of enabling reuse: deleting on removal could
destroy a file another piece of content still displays.

### 4.4 A member changes their profile picture

Unchanged in every user-visible respect. The avatar remains a single 200×200
JPEG with no responsive variants, its old file is still deleted immediately on
replacement or removal, and default generated SVG avatars keep working as
today. Only the internal call path changes.

### 4.5 Existing images keep working

Every image already displayed — activity images, static-page headers, News
headers, avatars — must still display, unchanged, after the migration. No file
is moved and no stored path is rewritten.

## 5. Lifecycle

| Event | Behaviour |
|-------|-----------|
| Activity or static page deleted | Its image path disappears with the row; the file becomes unclaimed and is swept after the 7-day window — unless another content reuses it. |
| Image removed from a form | As above (§4.3). Deferred, never immediate. |
| Grandfathered dated files | Displayed forever, never listed in the picker, never swept (the sweep is non-recursive and does not descend into `YYYY/MM/`). Same treatment News already received. |
| User deactivated / reactivated | N/A — no image in this task belongs to a user, except the avatar, whose behaviour is unchanged. |
| User deleted | Unchanged: Profile still deletes the avatar file and the generated default SVG itself. |
| Provider forgotten for a scope | The sweep's unclaimed-scope guard skips any folder that holds files but has zero claimed paths, so nothing is deleted. Each new provider is guarded by a test. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged. Reuse picker is admin/tech-admin only, by inheriting the existing route gates. |
| Visibility / privacy | Unchanged. `GET /media/library` stays `auth`-only: the user confirmed no privacy requirement on media listing, now or foreseeably (decision #5). All the scopes involved hold publicly displayed images anyway. |
| Settings | N/A — no user preference involved. |
| Notifications | N/A — nobody is notified about an admin image change. |
| Domain events | N/A — no new event. Profile keeps emitting `AvatarChanged` exactly as today. |
| Statistics | N/A — no counter. |
| Moderation | N/A — admin-managed images are not reportable content. |
| Lifecycle / cascade | §5. The material change is deletion becoming deferred for Calendar and StaticPage; Profile keeps immediate deletion. |
| Media | The whole point. Calendar and StaticPage adopt `MediaPublicApi` plus the Media Blade components and each register a usage provider; Profile adopts `MediaPublicApi` only, and stays out of the sweep entirely. |
| Search | N/A — images are not indexed. |
| i18n | The picker's French strings already exist in Media's lang files; no new user-facing wording is introduced. |
| Mobile | Inherited from `<x-media::image-field>`, already used on FAQ and News admin forms. |
| Accessibility | Alt text stays as today: activity name for Calendar, empty (decorative) for the static-page header. No alt input is added — see decision #3. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Do the Calendar and StaticPage admin forms get the Media reuse picker? | Yes, both — full `<x-media::image-field>` adoption, which entails dropping synchronous deletion in favour of the 7-day sweep. |
| 2 | Does Profile join the sweep (register a usage provider)? | No. Expose `saveSquareJpg` through `MediaPublicApi` and nothing more; Profile keeps managing its own files end to end, and the bogus `profile` scope is removed rather than fixed. |
| 3 | Add alt / caption inputs to the two admin forms? | No — off for both. No schema change; current alt behaviour preserved. |
| 4 | Show the "used N times" count in those forms? | No. |

## 8. Out of scope

- **Advanced (block) mode for static pages** — separate backlog task
  `multiedit-static-pages/`. Its provider will later extend the same StaticPage
  usage provider with `content_blocks` paths; this task only covers
  `header_image_path`.
- **Chapters MultiEdit** — separate task.
- **Migrating grandfathered dated files** into the flat scope folders. They stay
  where they are.
- **SecretGift gift images** (`gift_image_path`) — stored on the private `local`
  disk, served through a controller, outside the Media domain entirely.
- **Any schema change.** No migration is expected anywhere in this task.
- **Making avatars responsive** or moving avatar deletion to the sweep.
- **Tightening the `/media/library` route's authorisation** — explicitly
  declined (decision #5).

## 9. Open questions

None — both questions raised during REFINE were resolved on 2026-07-29
(decisions #5 and #6):

| # | Question | Resolution |
|---|----------|------------|
| 1 | `GET /media/library` is `auth`-only, so any member can enumerate any scope's image paths. | **Closed.** No privacy requirement on media listing, now or foreseeably. Leave the route as-is and raise no backlog row. |
| 2 | `StaticPageService::processHeaderImage()` accepts `UploadedFile|string`, but no caller passes a string. | **Closed.** Drop the dead branch — unused code goes. |

## Notes parked for DESIGN

Technical constraints surfaced during REFINE, not functional requirements:

- `MediaService::FLAT_SCOPES` has **two** wrong entries, not one: `calendar`
  (real folder `activities/`) and `profile` (real folder `profile_pictures/`).
  Add `activities`; remove both `calendar` and `profile`.
- New deptrac edges: `Calendar → MediaPublic`, `StaticPage → MediaPublic`,
  `Profile → MediaPublic`.
- `MediaPublicApi` gains a `saveSquareJpg`-shaped method for Profile.
- Final step: relocate `ImageService` to `Media/Private/Services` and delete the
  `Shared` copy; no `*Private → Shared\ImageService` edge should remain.
- `app/Domains/Media/README.md` states three things this task falsifies
  (the `calendar` scope warning, "`ImageService` stays in Shared for now", and
  the list of unmigrated consumers). Update at WRAP.
