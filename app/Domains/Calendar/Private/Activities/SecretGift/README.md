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
  component `secret-gift::secret-gift-component` **and** a config component,
  `secret-gift::secret-gift-config`. `configRules()` requires the registration
  deadline and bounds it against the activity's own `preview_starts_at` /
  `active_starts_at` with `DateOrderRule` (shared with Quote Contest, see the
  [Calendar README](../../../README.md)); `persistConfig()` writes the settings
  row in the activity's own transaction.
- `SecretGiftServiceProvider` loads the activity's own views (`secret-gift::`),
  translations, migrations, routes and the shuffle command, registers the
  private-media usage provider, and subscribes the listener below.
- `RemoveParticipantOnUserRemoved` listens to `Auth::UserDeleted` and
  `Auth::UserDeactivated` — see "Enrolment" below.

## Enrolment

**One predicate gates every enrolment write and read:**
`SecretGiftConfigService::isRegistrationOpen()`. Registration is open only
while the activity is in the `preview` state, `now` is before the settings'
`registration_ends_at`, and the activity has not been shuffled yet. The reader
page (`SecretGiftComponent`) and every write endpoint
(`SecretGiftParticipantController::store()`/`update()`/`destroy()`) call the
same method — hiding a form is presentation, the controller re-derives the
window itself on every POST/PUT/DELETE, so a forged request past a stale page
still gets refused.

**No settings row means closed, not open.** The deadline is mandatory on the
admin form, but `CalendarPublicApi::create()` does not run `configRules()` /
`persistConfig()` — only the admin `ActivityController` form does. Anything
that creates a Secret Gift activity another way (seeders, direct API calls)
must write the `calendar_secret_gift_settings` row itself, or the activity
reads as permanently closed for enrolment.

**`role_restrictions` is re-checked in the controller, not by the route.** The
enrolment routes only carry `web, auth, verified` — the same posture as
`QuoteContestEntryController`. `ActivityService::findVisibleBySlugOrFail()` is
what normally enforces `role_restrictions`, and these write routes never call
it, so `SecretGiftParticipantController::assertMayEnrol()` calls
`AuthPublicApi::hasAnyRole()` itself and 404s if the caller is excluded from
the activity's own restrictions. Skipping this would let a reader excluded
from the activity page still enrol by posting the activity id directly.

**Join, edit, leave.** `join()` is `updateOrCreate` on the unique
`(activity_id, user_id)` key, so a double submit saves rather than 500s.
Preferences are authored in the shared rich-text editor; a fresh join
pre-fills it with a French template (`secret-gift.preferences_template`) the
participant is expected to fill in. Text is purified with the `strict`
HTMLPurifier profile on every save, same as gift text. Leaving deletes the
participant row outright — there is no soft "paused" state.

**Participant list is enrolled-only.** `SecretGiftConfigService::participantsWithProfiles()`
is only ever called once the caller is confirmed to be a participant — an
outsider does not pay for the query. It never carries `preferences`, which
stay visible only to the one participant a giver has been assigned to. The
same list also renders in the admin config panel, and again below the gift
tabs once the activity is running.

**Un-enrolment on account removal is state-gated.** `UserDeleted` /
`UserDeactivated` both funnel into
`RemoveParticipantOnUserRemoved` → `SecretGiftService::removeParticipantsForUser()`,
which drops the participant row from every Secret Gift that has **not** been
shuffled yet. Once shuffled, the row and the assignment are left exactly as
they are — the pairing stands and whoever was due to receive from that user
simply gets no gift. This mirrors the existing, deliberately unaddressed
Jardino behaviour on the same two events (see the Jardino README).

## Shuffle

**Who can trigger it.** `moderator`, `admin` and `tech-admin` — the route
carries the `role:` middleware, matching the base activities admin CRUD and
broader than Quote Contest's category routes. A caller without one of those
roles is redirected to the dashboard with an error by the project's standard
`role:` middleware — it does not 403; the `ShuffleService` is never reached.

**Reachable from the admin panel**, inside the activity edit page: the config
component pushes a shuffle block to the `activity-config-extras` stack (see
"How it plugs in" and the [Calendar README](../../../README.md)) because the
button needs its own `<form>` and the config panel already renders inside the
activity form. It shows the live participant count and list, whether the
activity has already been shuffled, and disables the button with a reason when
shuffling is not currently allowed. The `secret-gift:shuffle {activity_id}`
Artisan command still exists alongside the button — nothing removed it, and
either path calls the same `ShuffleService`.

**When it is allowed.** `SecretGiftConfigService::isShuffleAllowedInState()` —
any state before `active` (so `draft`, `preview` or `ended`/`archived` before
the activity ever went active — the deadline is only a UI nudge, not an
enforced precondition on the shuffle itself). Once the activity reaches
`active`, shuffling is blocked for good, whether it was ever shuffled or not:
a running exchange must never see its pairings move under the participants'
feet. Before that point, re-shuffling is free and destructive — see below.

**The shuffle is a single cycle, not random pairs.** `ShuffleService` shuffles
the participants and assigns each one the next in the list, wrapping around.
This guarantees in one pass that nobody draws themselves and that everyone
both gives and receives exactly once. It needs at least two participants.
Re-running it deletes every existing assignment for the activity — and with it
the gifts already prepared, since the gift lives on the assignment row. Both
the controller and the admin panel read `isShuffleAllowedInState()` so a
disabled button and a refused POST always agree; the confirm modal is the only
thing standing between an admin and losing every prepared gift.

## Tables

| Table | Holds |
|---|---|
| `calendar_secret_gift_settings` | One row per activity: `registration_ends_at`. Unique on `activity_id`. Absence means enrolment reads as closed — see "Enrolment" above. |
| `calendar_secret_gift_participants` | One row per (activity, user) taking part, with free-text `preferences` shown to their giver. |
| `calendar_secret_gift_assignments` | One row per giver: who they give to, and the gift itself (text, image path, sound path). Unique per giver *and* per recipient within an activity. |

## Rules a reader would get wrong

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
The enrolment preferences field goes through the same profile for the same
reason.

## Not done

Deliberate non-goals: no notification of any kind (enrol, un-enrol, shuffle,
reveal — the recipient has to come back to the page); no reassignment or
auto-reshuffle after a post-shuffle deactivation; no auto-shuffle and nothing
blocks an activity from going `active` unshuffled or with fewer than two
participants; registration never reopens once the deadline passes or the
shuffle runs; preferences text is not moderated.

Gifts cannot be edited once the activity leaves the Active state.
