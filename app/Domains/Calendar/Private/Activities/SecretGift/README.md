# Secret Gift

A secret-santa-style exchange. Each participant is secretly assigned another
participant to prepare a gift for — a rich-text message plus an optional image
and sound file. Givers work on their gift while the activity is Active; the gift
they *receive*, and the identity of their giver, are revealed only once the
activity has Ended.

This is a Calendar activity plugin. The generic parts — activity states, role
restrictions, the registry — are documented in the
[Calendar README](../../../README.md).

## How it plugs in

- `SecretGiftRegistration` (type key `secret-gift`) exposes the display
  component `secret-gift::secret-gift-component`; no config component.
- `SecretGiftServiceProvider` loads the activity's own views (`secret-gift::`),
  translations, migrations, routes and the shuffle command.
- It listens to no domain events.

## Tables

| Table | Holds |
|---|---|
| `calendar_secret_gift_participants` | One row per (activity, user) taking part, with free-text `preferences` shown to their giver. |
| `calendar_secret_gift_assignments` | One row per giver: who they give to, and the gift itself (text, image path, sound path). Unique per giver *and* per recipient within an activity. |

## Rules a reader would get wrong

**The shuffle is a single cycle, not random pairs.** `ShuffleService` shuffles
the participants and assigns each one the next in the list, wrapping around. This
guarantees in one pass that nobody draws themselves and that everyone both gives
and receives exactly once. It needs at least two participants. Re-running it
deletes every existing assignment for the activity — and with it the gifts
already prepared, since the gift lives on the assignment row.

**The shuffle is triggered manually**, by an admin running
`sail artisan secret-gift:shuffle {activity_id}`. There is no scheduler, so the
moment of the draw is a human decision made once registration is judged closed.

**Reveal is driven by activity state, not by a flag.** A recipient can read the
gift text, view the image and play the sound only when the activity is Ended or
Archived; before that, only the giver can reach their own files. The check is in
`SecretGiftService::canViewImage()` / `canViewSound()` and is re-applied on every
file request — the file routes are not guessable-but-public, they are authorised
per request.

**Gift assets are never publicly addressable** — the whole point is that they
must stay unreachable before the reveal — but image and sound take two different
routes to that.

*Images* are Media-domain images on Media's **private** disk, under
`secret-gift/{activity_id}/`. `SecretGiftService::saveGiftImage()` calls
`MediaPublicApi::storePrivate()`, and the controller serves them with
`MediaPublicApi::stream()` *after* `canViewImage()` — Media performs no
authorization of its own and cannot build a URL for a private path. Consequently
**Calendar never deletes a gift image**: replacing or removing one only rewrites
`gift_image_path`, and `media:gc` reclaims the file once
`SecretGiftMediaUsageProvider` stops claiming it (which is why that provider must
stay registered in `SecretGiftServiceProvider`).

*Sound* is still a raw file on the `local` disk under
`calendar/secret-gift/{activity_id}/`, deleted synchronously on replace/removal
and streamed by the controller with HTTP Range support so the browser can seek.

Gift images written before the move live on `local` under the old
`calendar/secret-gift/…` layout; the data migration
`move_secret_gift_images_to_media_private` relocates them (see
`LegacyGiftImageMover`, which is reversible).

**Gift text is purified on save** with the `strict` HTMLPurifier profile — it is
authored in the shared rich-text editor and rendered as HTML to the recipient.

## Not done

- **There is no way to sign up.** No route, controller or command creates a
  `calendar_secret_gift_participants` row; only the test helpers do. In practice
  participants must be inserted by hand before the shuffle, and `preferences` can
  never be filled in from the UI. A non-participant visiting the activity page
  just sees a "you are not taking part" message. The activity is unusable as
  shipped; this is the one thing to fix before running it again.
- Gifts cannot be edited once the activity leaves the Active state, and there is
  no notification when the reveal happens — the recipient has to come back to the
  page.
