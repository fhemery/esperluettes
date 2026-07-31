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

**Gift assets are private files, not Media-domain images.** They are written to
the `local` disk under `calendar/secret-gift/{activity_id}/` and streamed back
through the controller. This is deliberate: the whole point is that they must not
be publicly addressable before the reveal. The sound endpoint implements HTTP
Range requests so the browser can seek.

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
