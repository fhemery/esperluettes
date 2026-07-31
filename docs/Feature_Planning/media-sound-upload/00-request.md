# Gift sound on Media — request

*Leftover pushed back by `shared-image-upload-cleanup/` (decision #5, spec §8).*

## What I want

Give the gift **sound** the same treatment the gift image just got: bytes owned
by Media on the private disk, served by SecretGift after its own
`canViewSound()` check, reclaimed by `media:gc` instead of deleted inline. Then
retire `<x-shared::sound-upload>` — the last upload widget left in Shared.

## Why

`shared-image-upload-cleanup/` moved gift images to `MediaPublicApi::storePrivate`
/ `stream` and deleted `<x-shared::image-upload>`. Sound was deliberately left
behind, so SecretGift now writes to two different disks for the same feature:
images to Media's `private`, sound raw to `local` under
`calendar/secret-gift/{activity_id}/`, deleted synchronously. Shared still hosts
an upload widget that duplicates Media's field. The orphan leak the image half
fixed is also still open for sound: a shuffle deletes assignment rows without
touching files, so every sound uploaded before a re-shuffle stays on disk
forever.

## Constraints or ideas I already have

- The private half of Media already exists: `storePrivate`, `stream`, `exists`,
  private-root GC with the zero-claim guard at the root. See
  `app/Domains/Media/README.md` → "Private images".
- Media is an **image** domain today — `storePrivate` runs uploads through
  `ImageService`. Storing an mp3 needs a decision: teach Media a raw
  private-file store, or keep sound out of Media and only unify the widget.
  That is the first tradeoff to arbitrate.
- The sound endpoint implements HTTP **Range** requests so the browser can seek.
  `MediaPublicApi::stream()` does not; whatever ships must keep seeking working.
- Visibility rules (`canViewSound`) stay in SecretGift, as with the image.
- `SecretGiftMediaUsageProvider` currently claims image paths only; it would
  have to claim sound paths too, or GC would eat them.

## Explicitly out of scope

- Changing gift visibility or timing rules.
- Touching the image half again.
