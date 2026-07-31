# SecretGift gift images on Media (private) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.

## 1. Overview

SecretGift gift images move onto Media’s new private-image capability: stored
off the public disk, previewed and revealed only through SecretGift’s existing
auth-gated routes, edited with `<x-media::image-field>` without a reuse
library. `<x-shared::image-upload>` is deleted once unused. Gift text and sound
behaviour stay as today (sound remains on Shared).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Gift image | Image privée d’une assignation Secret Gift |
| Private Media image | Fichier géré par Media hors disque public, sans URL `/storage/…` |
| Library picker | Modal « Choisir une image existante » de `<x-media::image-field>` |

## 3. Roles & visibility

Unchanged SecretGift rules:

| Role | Can see gift image | Can upload/replace/remove |
|------|--------------------|---------------------------|
| Guest | No | No |
| Giver (auth) | Yes | Yes while activity ACTIVE |
| Recipient | Only when activity ENDED/ARCHIVED | No |
| Outsider | No | No |

No new moderator override.

## 4. Functional requirements

### 4.1 Media private images (platform)

1. A consuming domain can store an image that is **not** web-public.
2. Private images never get an `asset('storage/…')` URL from Media.
3. Bytes are read only when the consumer asks Media to stream a path it has
   already authorised.
4. Unused private images are reclaimable by Media GC the same way public ones
   are (provider-claimed paths survive; orphans age out).

### 4.2 Image-field without library

1. `<x-media::image-field>` accepts a flag to hide the reuse picker (default:
   picker shown, today’s behaviour).
2. The field accepts an optional external preview URL so private images can show
   a non-asset preview in edit forms.

### 4.3 SecretGift preparation — image mode

1. Image mode uses `<x-media::image-field>` with library hidden and preview via
   the existing `secret-gift.image` route when a path exists.
2. Upload / replace / remove still go through the gift save endpoint; form
   shape follows Media’s `name[path]` / `name[file]` convention (like Calendar
   activity admin), not the old flat file + `_remove` boolean.
3. Reveal and serve keep the same routes and timing rules; only the storage
   backend changes.
4. Existing gift images on the old private disk remain viewable after deploy
   (migrated, not forced re-upload).

### 4.4 Shared cleanup

1. After SecretGift no longer references `<x-shared::image-upload>`, that Blade
   component is removed from Shared.
2. Shared `image-upload` lang file stays (Story cover borrows three keys).
3. `<x-shared::sound-upload>` stays in Shared (follow-up task).

## 5. Lifecycle

- Replace/remove clears `gift_image_path`; Media GC deletes the orphan file
  after the normal grace window (no immediate disk delete from SecretGift).
- Shuffle that deletes assignment rows frees paths for GC (fixes today’s
  orphan-file leak on `local`).
- User deactivation/deletion: unchanged assignment ownership rules.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged SecretGift auth |
| Visibility / privacy | Private disk + consumer route; never public URL |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | Deferred GC via usage provider |
| Media | New private store/stream + GC; SecretGift registers provider |
| Search | N/A |
| i18n | Shared image-upload lang kept; Media field uses media lang |
| Mobile | Unchanged gift form |
| Accessibility | Unchanged field chrome |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 2 | Privacy model | Media private store+serve (A) |
| 3 | `allowLibrary` | Yes |
| 4 | Editor text | Leave as-is |
| 5 | Sound | Deferred to backlog |

## 8. Out of scope

- Moving sound to Media.
- Collapsing widgets into SecretGift (rejected / reverted).
- Making gift images public on Media.
- Changing giver/recipient timing rules.
- Story cover UI / deleting Shared image-upload lang.
- Activity header images (already Media; env symlink is ops, not this feature).

## 9. Open questions

None blocking.
