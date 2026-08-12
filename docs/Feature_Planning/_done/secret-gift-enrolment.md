# Secret Gift — enrolment

**Status:** DONE — 2026-08-09 · **Domain:** `Calendar`, the `SecretGift`
activity plugin ([domain README](../../../app/Domains/Calendar/Private/Activities/SecretGift/README.md))
· absorbs the dropped `calendar-subscription/` proposal, see [`calendar.md`](./calendar.md)

## What it does

Secret Gift had assignments and gifts but **no way to sign up** — rows had to be
inserted by hand. This adds the enrolment half: a mandatory
`registration_ends_at` on the admin form, a join / edit-preferences / leave
surface on the activity page during `preview`, an enrolled-only participant
list, and a shuffle button for moderators and admins inside the activity edit
page. Enrolment is **Secret-Gift-owned**, not a base-Calendar mechanism: the two
dead columns on `calendar_activities` (`requires_subscription`,
`max_participants`) were dropped rather than made to work.

## Key behaviour

- **Window.** Registration is open iff the activity is `preview` **and** now <
  `registration_ends_at` **and** the shuffle has not run. One predicate,
  `SecretGiftConfigService::isRegistrationOpen()`, read by the view component and
  re-derived by every write endpoint.
- **No settings row ⇒ closed.** The deadline is mandatory on the admin form, but
  `CalendarPublicApi::create()` bypasses `configRules()`, so a row-less activity
  is possible; it fails safe to closed. This was never arbitrated by the user.
- **Who may enrol.** Anyone who can see the activity page — `auth` + `verified`
  plus the activity's own `role_restrictions`, re-checked in the controller
  (404), because the route middleware does not check it.
- **Who may shuffle.** `moderator`, `admin`, `tech-admin` — broader than
  QuoteContest's category routes. Allowed at any state before `active`, with ≥2
  participants; re-shuffling is free until then and blocked for good afterwards.
- **Participant list.** Enrolled callers only (an outsider does not even pay for
  the query), display names + avatars, never preferences, never pairings. Also
  rendered below the gift tabs on a running activity.
- **Preferences** are rich text, purified with the `strict` profile on save,
  pre-filled at join with a French template string. `join()` is `updateOrCreate`,
  so a double submit is a save, not a 500 on the unique key.
- **Lifecycle.** `UserDeleted` / `UserDeactivated` un-enrol the user from every
  Secret Gift that has **not** been shuffled; shuffled ones are left untouched —
  the pairing stands and one person simply receives nothing (decision #10).

## Where the code lives

All paths under `app/Domains/Calendar/Private/Activities/SecretGift/` unless said
otherwise.

| Concern | Path |
|---------|------|
| Registration / extension point | `SecretGiftRegistration.php` |
| Config + window service | `Services/SecretGiftConfigService.php` |
| Enrolment writes | `Services/SecretGiftService.php` (`join`/`updatePreferences`/`leave`/`removeParticipantsForUser`) |
| Controllers | `Http/Controllers/SecretGiftParticipantController.php`, `SecretGiftShuffleController.php` |
| Routes | `Http/routes.php` |
| Request | `Http/Requests/SavePreferencesRequest.php` |
| Listener | `Listeners/RemoveParticipantOnUserRemoved.php` |
| Model | `Models/SecretGiftSettings.php` |
| Views | `Resources/views/components/secret-gift.blade.php` (reader), `secret-gift-config.blade.php` (admin) |
| Date ordering rule | `Calendar/Private/Support/DateOrderRule.php` (moved out of QuoteContest) |
| Migrations | `Database/Migrations/2026_08_10_120000_create_calendar_secret_gift_settings_table.php`; `Calendar/Database/Migrations/2026_08_10_121000_drop_participant_limit_fields_from_activities_table.php` |
| Tests | `Calendar/Tests/Feature/SecretGift/{AdminConfig,Enrolment,EnrolmentPage,ParticipantCleanup,RegistrationWindow,ShuffleScreen}Test.php` (61 cases) |
| E2E fixtures | `Calendar/Database/Seeders/E2eSecretGiftSeeder.php` + `e2e/support/fixtures.ts` (`GIFTS`) |

## Extension points used

- `ActivityRegistrationInterface::configComponentKey()` / `configRules()` /
  `persistConfig()` — the deadline rides in the activity form payload under the
  `secret_gift` key; `persistConfig()` runs inside the activity's transaction.
- The `activity-config-extras` Blade stack — the shuffle block needs its own
  `<form>` and the config panel renders *inside* the activity form, so it is
  pushed to the stack both admin pages print after `</form>` (as QuoteContest does).
- `EventBus` on `UserDeleted` / `UserDeactivated`, listener resolved lazily.
- No notification, no moderation topic, no statistics, no admin-nav entry — the
  shuffle is reached through the existing activity edit page.

## Decisions worth remembering

- **Enrolment is Secret-Gift-owned.** A generic base-Calendar mechanism would
  have served exactly one consumer; `calendar-subscription/` was closed as
  absorbed and the two unused columns deleted.
- **The deadline lives on `calendar_secret_gift_settings`**, not on
  `calendar_activities` — the base table stays generic.
- **No participant cap, ever.** `max_participants` removed, no waiting list.
- **Registration never reopens** once the deadline passes or the shuffle runs.
- **The shuffle is the destructive action.** Re-running deletes every existing
  assignment and the gifts on them; that is why it is behind a confirm modal and
  hard-blocked from `active` onwards.

## Where the code and the plan disagree

- **A1 (reversible, flagged for the user).** Phase 7's acceptance said the
  shuffle route "403s" for non-moderators. The project's `role:` middleware
  denies by **redirecting to the dashboard with an error**, never with a 403, so
  that is what ships and what the tests assert. The security property is intact
  (the request never reaches `ShuffleService`). Changing it means a custom abort
  or a controller-level check, diverging from every other admin route.
- Architecture §3.3 claimed the `web,auth,verified` middleware enforces
  `role_restrictions`. It does not — only `findVisibleBySlugOrFail()` does, and
  these routes never call it. The controller adds an explicit
  `AuthPublicApi::hasAnyRole()` check (404).
- The plan assumed the participant list would be plain display names; it ships
  with avatars, on both the reader page and the admin panel.
- Decision #3 says strict ordering; `DateOrderRule` compares with `>=` / `<=`,
  so `registration_ends_at` equal to `preview_starts_at` or `active_starts_at`
  is accepted.

## Not done

Deliberate non-goals: any notification (enrol, un-enrol, shuffle, reveal);
reassignment or auto-reshuffle after a post-shuffle deactivation; auto-shuffle
or blocking activation when an activity goes `active` unshuffled or with <2
participants; reopening registration; moderation of preferences text.

Nothing was cut mid-build; all 7 phases are DONE. No new backlog rows.

Known, not fixed: VERIFY found the base-Calendar activity page **header** clips
its state badge by ~1 px at 390 px on a long activity name
(`Calendar/Private/.../activity/show.blade.php`) — not this feature's markup.
The artisan `secret-gift:shuffle` command still exists alongside the button.

**E2E:** `e2e/tests/features/secret-gift-enrolment.spec.ts` (18 tests, all green
at VERIFY) was **deleted**; its confirm-modal half was **promoted** to
`e2e/tests/core/confirm-modal.spec.ts`, since `<x-shared::confirm-modal>` guards
destructive actions in Story and Calendar alike and only a browser can tell that
*Annuler* closes without submitting. The page objects and the seeder stay —
the promoted spec drives them.
