# Secret Gift — enrolment — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Promote `DateOrderRule` to Calendar core | S | — | TODO |
| 2 | Drop `requires_subscription` / `max_participants` | S | — | TODO |
| 3 | Settings table + `registration_ends_at` config panel | M | 1 | TODO |
| 4 | Enrolment service, write endpoints, authorization | M | 3 | TODO |
| 5 | Participant cleanup on deactivation / deletion | S | 4 | TODO |
| 6 | Participant-facing UI (join / edit / leave / list) | M | 4 | TODO |
| 7 | Moderator & admin shuffle screen | M | 3, 4 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

**Sequencing logic.** Phases 1–2 are risk-free groundwork that touches base
Calendar and nothing else (a shared helper move and a dead-column removal), so
they can land and be forgotten. Phase 3 opens the extension point and gives
every later phase the one predicate they all consult
(`isRegistrationOpen()`). Phase 4 puts the whole server-side security surface
in place — including the `role_restrictions` re-check — before any UI exists to
call it (slicing rule #5). Phase 5 closes the data-integrity gap on user
removal *before* phase 6 makes enrolment reachable by real users. Phases 6 and
7 are the two independent front-ends onto that server surface: either may slip
without the other breaking.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- **Domain docs**: a phase fixes only the statements it makes *false* (there is
  one, in phase 2). The full `README.md` / `AGENTS.md` refresh for
  `app/Domains/Calendar/` and its SecretGift sub-module is WRAP's job — do not
  pre-empt it phase by phase.
- Run the gate without spilling its output into the transcript:
  `npm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log`

---

## Phase 1 — Promote `DateOrderRule` to Calendar core

**Goal.** Move the same-payload date-comparison validation rule out of the
QuoteContest plugin so a second activity type can use it without importing a
sibling plugin's namespace and rendering its French strings.

**Context.** `DateOrderRule` today lives at
`app/Domains/Calendar/Private/Activities/QuoteContest/Support/DateOrderRule.php`.
It is a `DataAwareRule` + `ValidationRule` that compares one date field of the
activity-admin payload to another field of the *same* payload (no DB read), and
carries its own French message so a violation renders as a field error on the
activity form — `configRules()` has no hook to contribute custom messages to
`ActivityRequest`. Phase 3 needs it for Secret Gift's `registration_ends_at`.
This is **pure relocation: no behaviour change, no signature change.**

Deptrac puts every plugin in the single `CalendarPrivate` layer, so a
cross-plugin import would pass the gate — it is still the wrong shape, because
a Secret Gift admin who mistypes a date would be shown
`quote-contest::quote-contest.validation.invalid_date`.

**Deliverables.**
- Move `app/Domains/Calendar/Private/Activities/QuoteContest/Support/DateOrderRule.php`
  → `app/Domains/Calendar/Private/Support/DateOrderRule.php`, namespace
  `App\Domains\Calendar\Private\Support`. Keep `notBefore()` / `notAfter()` /
  `setData()` / `validate()` exactly as they are.
- Its one hard-coded message — the parse-failure fallback currently
  `__('quote-contest::quote-contest.validation.invalid_date')` — becomes
  `__('calendar::calendar.validation.dates.invalid_date')`.
- `app/Domains/Calendar/Private/Resources/lang/fr/calendar.php`: add
  `validation.dates.invalid_date => 'Cette date n\'est pas valide.'` (the exact
  string currently in the quote-contest lang file).
- `app/Domains/Calendar/Private/Activities/QuoteContest/Resources/lang/fr/quote-contest.php`:
  remove `validation.invalid_date`.
- `app/Domains/Calendar/Private/Activities/QuoteContest/QuoteContestRegistration.php`:
  update the `use` statement. Nothing else in that file changes.
- Update the class docblock so it no longer describes itself as the contest's
  rule — it is now the Calendar-wide one, used by any type whose
  `configRules()` compares dates carried in the activity payload.

**Tests.**
- No new test. The existing
  `app/Domains/Calendar/Tests/Feature/QuoteContest/AdminConfigTest.php` already
  covers every branch of the rule (three ordering violations, plus the
  `invalid_date` assertion around line 200) — its assertion on the message key
  must be updated to `calendar::calendar.validation.dates.invalid_date` and must
  still pass. If it does not, the move broke something.

**Acceptance.**
- ✅ `grep -rn "QuoteContest\\\\Support\\\\DateOrderRule" app/` returns nothing.
- ✅ `grep -rn "quote-contest.validation.invalid_date" app/` returns nothing.
- ✅ `AdminConfigTest` passes unchanged apart from the one message-key string.
- ✅ `npm run gate` green.

---

## Phase 2 — Drop `requires_subscription` / `max_participants`

**Goal.** Remove the two `calendar_activities` columns that never enforced
anything, per functional spec §8 and decision #7, along with every reference to
them.

**Context.** Architecture §1 is explicit that this is the *only* change this
feature makes to base Calendar, and that it is a removal, not a new capability.
Nothing outside `app/Domains/Calendar/` constructs `ActivityToCreateDto` /
`ActivityToUpdateDto` / reads `ActivityDto`, so the DTO constructor change has
no cross-domain blast radius (verified during PLAN). The columns are dropped
outright — decision #7 rules out any cap or waiting list in any form, so
nothing replaces them.

**Deliverables.**
- New migration
  `app/Domains/Calendar/Database/Migrations/2026_08_10_121000_drop_participant_limit_fields_from_activities_table.php`
  — `up()` drops both columns; `down()` re-adds them with their original
  definitions from `2025_10_20_000000_create_activities_table.php`
  (`boolean('requires_subscription')->default(false)` and
  `integer('max_participants')->nullable()`), so the migration is reversible.
- `Private/Models/Activity.php` — remove both names from `#[Fillable]` and
  `requires_subscription` from `$casts`.
- `Public/Contracts/ActivityDto.php` — remove both promoted properties and both
  lines of `fromModel()`.
- `Public/Contracts/ActivityToCreateDto.php` and
  `Public/Contracts/ActivityToUpdateDto.php` — remove both promoted properties.
- `Private/Requests/ActivityCreateRequest.php` and
  `Private/Requests/ActivityUpdateRequest.php` — remove the two payload lines.
- `Private/Services/ActivityService.php` — remove the two assignments in
  `create()` and the two in `update()`.
- `Private/Requests/Admin/ActivityRequest.php` — remove the two rules.
- `Private/Controllers/Admin/ActivityController.php` — remove the two named
  arguments in `store()` and in `update()`.
- `Private/Resources/views/pages/admin/activities/_form.blade.php` — remove the
  `<x-shared::toggle name="requires_subscription">` block and the
  `max_participants` block from the *Restrictions & Settings* section. The
  section keeps its heading and the `role_restrictions` multi-select.
- `Private/Resources/lang/fr/admin.php` — remove the `fields.requires_subscription`
  and `fields.max_participants` keys.
- `Database/Seeders/E2eCalendarSeeder.php` — remove `'requires_subscription' => false`
  from the `activity()` helper (mass assignment on a dropped column throws).
- `app/Domains/Calendar/README.md` — the first paragraph currently claims
  "`requires_subscription` and `max_participants` are stored on
  `calendar_activities` and editable in the admin form but **enforce nothing**".
  That sentence becomes false with this phase; rewrite it to say the fields were
  removed as dead code, leaving the rest of the paragraph (about the core
  announcing nothing) intact.

**Tests.**
- `app/Domains/Calendar/Tests/helpers.php` — drop both keys from
  `makeActivityCreateDto()`'s `$base` array (the DTO no longer accepts them).
- `app/Domains/Calendar/Tests/Feature/SecretGift/helpers.php` — drop
  `'requires_subscription' => true` from `createActiveSecretGift()`'s
  `$baseOverrides`.
- `app/Domains/Calendar/Tests/Feature/Admin/ActivityControllerTest.php` — the
  `it('accepts optional fields')` case posts and asserts both fields. Rewrite it
  to exercise a field that still exists (`description`, or `role_restrictions`),
  or delete it if nothing optional is left worth asserting — do not leave a test
  that asserts on dropped columns.
- `app/Domains/Calendar/Tests/Feature/UpdateCalendarPublicApiTest.php` — remove
  the two named arguments and the two `expect()` assertions.
- New assertion in `ActivityControllerTest`:
  `it('no longer exposes the removed participant-limit fields on the admin form')`
  — GET `calendar.admin.activities.create` as an admin, assert the response does
  **not** contain `name="max_participants"` nor `name="requires_subscription"`.

**Acceptance.**
- ✅ `grep -rn "requires_subscription\|max_participants" app/ e2e/` returns
  nothing outside the new migration's `down()`.
- ✅ The admin activity create and edit forms render without the two inputs.
- ✅ The migration rolls back cleanly (`migrate:rollback` re-adds both columns).
- ✅ `npm run gate` green.

---

## Phase 3 — Settings table + `registration_ends_at` config panel

**Goal.** Give Secret Gift its own settings row carrying the mandatory
registration deadline, wired through the activity admin form via the existing
`configComponentKey()` / `configRules()` / `persistConfig()` extension point,
and expose the single predicate every later phase consults.

**Context.** Read **architecture §2 (data model), §3.2 (services) and tradeoff
#1 in §7** — the deadline deliberately does *not* live on `calendar_activities`
(that is the mistake phase 2 just removed two columns for). `SecretGiftRegistration`
currently returns `null` from `configComponentKey()`, `[]` from `configRules()`
and no-ops in `persistConfig()`; this phase is the first thing that makes them
do something. QuoteContest is the reference implementation of the same three
methods — `QuoteContestRegistration`, `QuoteContestConfigService::saveSettings()`,
`QuoteContestSettings`, and the migration
`2026_08_02_100000_create_calendar_quote_contest_settings_table.php` — mirror
their shape. `ActivityController::store()`/`update()` already run the activity
write and `persistConfig()` inside one `DB::transaction()`, so throwing from
`persistConfig()` rolls the activity back; no controller change is needed.

Phase 1 moved `DateOrderRule` to
`App\Domains\Calendar\Private\Support\DateOrderRule` — import it from there.

**This phase builds the deadline field only.** The shuffle screen also lives in
this component but is phase 7's; leave the `activity-config-extras` push out for
now.

**Deliverables.**
- Migration
  `app/Domains/Calendar/Private/Activities/SecretGift/Database/Migrations/2026_08_10_120000_create_calendar_secret_gift_settings_table.php`
  — `id`, `foreignId('activity_id')->unique()->constrained('calendar_activities')->cascadeOnDelete()`,
  `dateTime('registration_ends_at')` not null, `timestamps()`. `down()` drops
  the table.
- `.../SecretGift/Models/SecretGiftSettings.php` — exactly the shape given in
  architecture §2.2: `#[Table('calendar_secret_gift_settings')]`,
  `#[Fillable(['activity_id', 'registration_ends_at'])]`,
  `protected $casts = ['registration_ends_at' => 'datetime']`, `activity()`
  `BelongsTo`.
- `.../SecretGift/Services/SecretGiftConfigService.php` — new, constructor
  `__construct(private readonly ShuffleService $shuffle)`:
  - `settingsFor(int $activityId): ?SecretGiftSettings`
  - `saveSettings(int $activityId, mixed $registrationEndsAt): SecretGiftSettings`
    — `updateOrCreate(['activity_id' => …], ['registration_ends_at' => Carbon::parse(…)])`
  - `isRegistrationOpen(Activity $activity): bool` — true iff
    `$activity->state === ActivityState::PREVIEW`
    **and** a settings row exists
    **and** `now() < registration_ends_at`
    **and** `! $this->shuffle->hasBeenShuffled($activity)`.
    **A missing settings row means registration is closed** (fail safe): the
    field is mandatory on the admin form, so its absence means the activity was
    created outside that form and has no declared deadline. See Open item 2.
  - `participantsWithProfiles(int $activityId): Collection` — phase 6 and 7 both
    need it, so it lands here now: read `SecretGiftParticipant` rows for the
    activity, bulk-resolve names with
    `ProfilePublicApi::getPublicProfiles(array $userIds)` (one call, returns
    `[userId => ProfileDto]`), return a `Collection<int, ProfileDto>` ordered by
    `display_name`, dropping ids with no profile. **It must never carry
    `preferences`** — that column is only ever read for the assigned giver
    (functional spec §6, privacy row).
- `.../SecretGift/SecretGiftRegistration.php`:
  - add `public const CONFIG_KEY = 'secret_gift';`
  - `configComponentKey()` returns `'secret-gift::secret-gift-config'`
  - `configRules()` returns
    ```php
    [self::CONFIG_KEY . '.registration_ends_at' => [
        'bail',
        'required',
        DateOrderRule::notBefore('preview_starts_at', 'secret-gift::secret-gift.validation.registration_ends_before_preview_start'),
        DateOrderRule::notAfter('active_starts_at', 'secret-gift::secret-gift.validation.registration_ends_after_activity_start'),
    ]]
    ```
    (`bail` keeps a missing value on Laravel's translated `required` message;
    every other failure goes to `DateOrderRule`, which carries its own French
    message.)
  - `persistConfig()` reads `$validated[self::CONFIG_KEY]`, returns early if it
    is not an array, else calls
    `app(SecretGiftConfigService::class)->saveSettings($activityId, $config['registration_ends_at'])`.
- `.../SecretGift/Resources/views/components/secret-gift-config.blade.php` —
  new anonymous component, `@props(['activity' => null])`. One
  `surface-bg p-6 rounded-lg` section with a heading, a hint paragraph, and a
  `<x-shared::datetime-local-input id="sg_registration_ends_at"
  name="secret_gift[registration_ends_at]">` whose value is
  `old('secret_gift.registration_ends_at', $settings?->registration_ends_at?->format('Y-m-d\TH:i') ?? '')`,
  with `<x-shared::input-error :messages="$errors->get('secret_gift.registration_ends_at')" />`.
  Resolve the settings row through `SecretGiftConfigService::settingsFor()`
  inside `@php`, guarded by `$activity !== null` (the create form renders the
  panel with `$activity = null`).
- `.../SecretGift/SecretGiftServiceProvider.php` — add
  `Blade::anonymousComponentPath($base . '/Resources/views/components', 'secret-gift')`
  next to the existing `Blade::componentNamespace(...)` call, so the anonymous
  config component resolves under the same `secret-gift::` prefix. (The provider
  currently registers only the class-component namespace; QuoteContest's
  provider does both and documents why.)
- `.../SecretGift/Resources/lang/fr/secret-gift.php` — new `config` block
  (`section_title`, `registration_ends_at`, `registration_hint`) and two new
  `validation` keys (`registration_ends_before_preview_start`,
  `registration_ends_after_activity_start`), all in French.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/SecretGift/AdminConfigTest.php`, modelled on
`Tests/Feature/QuoteContest/AdminConfigTest.php` (a local
`secretGiftFormPayload()` helper building the generic activity fields plus the
`secret_gift` block):
- `it('persists the settings row atomically when the activity is created')`
- `it('updates the settings row when the activity is edited')`
- `it('refuses a missing registration deadline and creates nothing')` — asserts
  `assertSessionHasErrors(['secret_gift.registration_ends_at'])` and
  `Activity::query()->count() === 0`
- `it('refuses a deadline before the preview start, in French, and creates nothing')`
  — asserts the error message key is
  `secret-gift::secret-gift.validation.registration_ends_before_preview_start`
- `it('refuses a deadline after the activity start, in French')`
- `it('renders the deadline field on the create form and prefills it on edit')`
- `it('cascades the settings row when the activity is deleted')`
- Unit-ish coverage of the predicate, as a feature test in the same file or a
  sibling `RegistrationWindowTest.php`:
  `it('reports registration open during preview before the deadline')`,
  `it('reports registration closed once the deadline has passed')`,
  `it('reports registration closed once the activity has been shuffled')`,
  `it('reports registration closed once the activity is active')`,
  `it('reports registration closed when no settings row exists')`.

**Also update.** `app/Domains/Calendar/Tests/Feature/SecretGift/helpers.php` —
`createActiveSecretGift()` builds activities through `CalendarPublicApi`, which
bypasses `configRules()`, so it currently leaves no settings row and every
Secret Gift in the suite would read as "registration closed". Give it a
`array $settings = []` parameter (same shape as `createQuoteContest()`) that
creates a `SecretGiftSettings` row, defaulting `registration_ends_at` to
`now()->subHour()` for the already-active fixtures, and return it on the result
object as `->settings`. Add a `createPreviewSecretGift(TestCase $t, array $overrides = [], array $settings = [])`
helper producing a PREVIEW activity with an open registration window
(`preview_starts_at` in the past, `registration_ends_at` in the future,
`active_starts_at` further out) — phases 6 and 7 depend on it.

**Acceptance.**
- ✅ Creating a Secret Gift activity from the admin form without a deadline is
  refused, in French, and no `calendar_activities` row is written.
- ✅ Creating one with a valid deadline writes exactly one
  `calendar_secret_gift_settings` row pointing at it.
- ✅ A deadline outside `preview_starts_at … active_starts_at` is refused with
  the corresponding French message on `secret_gift.registration_ends_at`.
- ✅ Deleting the activity deletes its settings row.
- ✅ `isRegistrationOpen()` returns false for every one of: not-preview,
  deadline passed, already shuffled, no settings row.
- ✅ The existing Secret Gift suite (`SecretGiftPageTest`, `SaveGiftTest`,
  `ServeFileTest`, `GiftImageUsageProviderTest`) still passes.
- ✅ `npm run gate` green.

---

## Phase 4 — Enrolment service, write endpoints, authorization

**Goal.** Land the complete server-side enrolment surface — join, edit
preferences, leave — with every refusal enforced in the controller, before any
UI exists that could be mistaken for the protection.

**Context.** Read **architecture §3.2 (services), §3.3 (policy) and §3.5
(routes, controllers, form requests)**. `calendar_secret_gift_participants`
(`activity_id`, `user_id`, `preferences` nullable text, unique on
`(activity_id, user_id)`) already exists and needs no migration.

Phase 3 left behind `SecretGiftConfigService::isRegistrationOpen(Activity $activity): bool`
— true only during `preview`, before the deadline, and before the shuffle. Every
action in this phase re-derives it itself rather than trusting that a page was
rendered: a forged POST never went past a rendered page. This is the same
posture `QuoteContestEntryController` documents.

**The `role_restrictions` gate needs an explicit check.** Architecture §3.3 says
these routes "sit behind the same `role_restrictions` check that already gates
the activity page". That is true of the *page* — `ActivityService::findVisibleBySlugOrFail()`
calls `AuthPublicApi::hasAnyRole($activity->role_restrictions)` and 404s — but
the `web, auth, verified` route middleware does **not** do it. So the controller
must re-check it, or a user excluded by `role_restrictions` could enrol by
POSTing the activity id. See Open item 4.

**Deliverables.**
- `.../SecretGift/Services/SecretGiftService.php` (existing class, extended —
  leave its assignment/gift methods alone):
  - `join(Activity $activity, int $userId, ?string $preferences): SecretGiftParticipant`
    — `updateOrCreate` on `(activity_id, user_id)` so a double submit is
    idempotent rather than a unique-constraint 500.
  - `updatePreferences(SecretGiftParticipant $participant, ?string $preferences): void`
  - `leave(Activity $activity, int $userId): void` — deletes the row; a no-op if
    there is none.
- `.../SecretGift/Http/Requests/SavePreferencesRequest.php` — `authorize(): true`,
  rules `['preferences' => ['nullable', 'string', 'max:65535']]`, French message
  for `preferences.max` (mirror `SaveGiftRequest`'s `gift_text_max`).
- `.../SecretGift/Http/Controllers/SecretGiftParticipantController.php` — new,
  constructor takes `SecretGiftService`, `SecretGiftConfigService` and
  `AuthPublicApi`. A private guard used by all three actions:
  ```php
  private function assertMayEnrol(Activity $activity): void
  {
      if (! $this->auth->hasAnyRole((array) ($activity->role_restrictions ?? []))) {
          abort(404);   // same posture as findVisibleBySlugOrFail()
      }
      if (! $this->config->isRegistrationOpen($activity)) {
          abort(403);   // the page never offers the action; reaching it means a forged request
      }
  }
  ```
  Actions, each ending on `back()->with('success', …)`:
  - `store(SavePreferencesRequest $request, Activity $activity)` — guard, then
    `join()` with the purified preferences.
  - `update(SavePreferencesRequest $request, Activity $activity)` — guard, then
    resolve the caller's participant row via `SecretGiftService::getParticipant()`
    (403 if none), then `updatePreferences()`.
  - `destroy(Activity $activity)` — guard, then `leave()`.
  Preferences are sanitised in the controller with
  `Purifier::clean($text, 'strict')` — the same profile `SecretGiftController::saveGift()`
  uses for `gift_text`, since both are authored in the shared rich-text editor
  and rendered back as HTML. Empty/blank input stores `null`.
- `.../SecretGift/Http/routes.php` — add to the existing
  `['web', 'auth', 'verified']` group, under prefix
  `calendar/secret-gift/{activity}` and name prefix `secret-gift.`:
  `POST /participants` → `store` (`secret-gift.participants.store`),
  `PUT /participants` → `update` (`secret-gift.participants.update`),
  `DELETE /participants` → `destroy` (`secret-gift.participants.destroy`).
  **No `PATCH`** — the production WAF resets that verb.
- `.../SecretGift/Resources/lang/fr/secret-gift.php` — `flash.joined`,
  `flash.preferences_saved`, `flash.left`, `validation.preferences_max`.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/SecretGift/EnrolmentTest.php`, using the
`createPreviewSecretGift()` helper phase 3 added:
- `it('creates a participant row with purified preferences when a confirmed user joins')`
- `it('stores a null preferences column when the submitted text is blank')`
- `it('strips a script tag from submitted preferences')` — proves the Purifier
  `strict` pass
- `it('is idempotent when the same user posts twice')` — one row, no 500
- `it('updates only the caller\'s own preferences')`
- `it('deletes the participant row when the user leaves')`
- `it('refuses joining once the registration deadline has passed', 403)`
- `it('refuses joining once the activity has been shuffled', 403)`
- `it('refuses joining once the activity is active', 403)`
- `it('refuses editing preferences after registration closed', 403)`
- `it('refuses leaving after registration closed', 403)` — and asserts the row
  survives
- `it('refuses editing preferences for a user who never joined', 403)`
- `it('404s for a user whose role is excluded by the activity role_restrictions')`
  — the security test for the guard above; create the activity with
  `role_restrictions => [Roles::USER_CONFIRMED]` and post as a plain `user`
- `it('redirects a guest to login')`

**Acceptance.**
- ✅ POST `/calendar/secret-gift/{activity}/participants` as a `user-confirmed`
  during an open window creates exactly one row and redirects back with a flash.
- ✅ A `user` excluded by `role_restrictions` gets 404 on all three verbs.
- ✅ All three verbs return 403 once the deadline passed, once shuffled, and
  once the activity is active — asserted independently.
- ✅ `<script>` in submitted preferences is gone from the stored column.
- ✅ No route uses `PATCH`.
- ✅ `npm run gate` green.

---

## Phase 5 — Participant cleanup on deactivation / deletion

**Goal.** Remove a user's not-yet-shuffled participant rows when they are
deactivated or deleted, and leave everything alone once the shuffle has
happened.

**Context.** Read **architecture §2.3 (lifecycle rules) and §3.4 (events and
listeners)**, and functional spec §5. Decision #10: before shuffle the row is
removed — the same effect as un-enrolling, applied automatically; after shuffle
nothing is touched, the assignment stands and the person who would have received
a gift from them simply doesn't get one. That matches the existing, deliberately
unaddressed Jardino gap and is **not** something to "improve" here.

`ShuffleService::hasBeenShuffled(Activity $activity): bool` already exists
(`SecretGiftAssignment::where('activity_id', …)->exists()`). Phase 4 left
`SecretGiftService` owning the participant-side writes.

`UserDeleted` and `UserDeactivated` live in
`App\Domains\Auth\Public\Events` and each carry a single `public readonly int $userId`.
`AuthPublic` is already an allowed dependency of `CalendarPrivate` — no deptrac
change.

**Deliverables.**
- `.../SecretGift/Services/SecretGiftService.php` — add
  `removeParticipantsForUser(int $userId): void`: load the user's participant
  rows across all activities (with their `activity`), and delete each one whose
  activity has not been shuffled. Unscoped by activity on purpose — see
  architecture §9's second risk; it is fine at a handful of concurrent Secret
  Gift activities.
- `.../SecretGift/Listeners/RemoveParticipantOnUserRemoved.php` — new, thin:
  a `handle(UserDeleted|UserDeactivated $event): void` that calls
  `SecretGiftService::removeParticipantsForUser($event->userId)`.
- `.../SecretGift/SecretGiftServiceProvider.php` — add a private
  `registerEventListeners()` called from `boot()`, subscribing both events on
  `EventBus` **as lazy closures**:
  ```php
  $eventBus->subscribe(UserDeleted::class, static fn ($e) => app(RemoveParticipantOnUserRemoved::class)->handle($e));
  $eventBus->subscribe(UserDeactivated::class, static fn ($e) => app(RemoveParticipantOnUserRemoved::class)->handle($e));
  ```
  Resolve inside the closure, never at boot — `QuoteContestServiceProvider::registerEventListeners()`
  documents why (eager resolution drags cross-domain public APIs into the
  container on every request and freezes what they hold at boot time).

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/SecretGift/ParticipantCleanupTest.php`:
- `it('removes a participant row when the user is deleted before the shuffle')`
- `it('removes a participant row when the user is deactivated before the shuffle')`
- `it('leaves the participant row and the assignments alone after the shuffle')`
  — build with `createShuffledSecretGift()`, fire `UserDeleted`, assert both the
  participant row and both assignment rows are untouched
- `it('removes rows only for the removed user')` — a second participant in the
  same activity survives
- `it('spans every activity the user was enrolled in')` — two pre-shuffle
  activities, both cleaned

Fire the events through `EventBus` exactly as the app does, not by calling the
listener directly — the subscription wiring is half of what this phase delivers.

**Acceptance.**
- ✅ Deleting a user with a pre-shuffle enrolment leaves zero
  `calendar_secret_gift_participants` rows for them.
- ✅ Deactivating a user has the same effect.
- ✅ A shuffled activity's participant and assignment rows are byte-for-byte
  unchanged after either event.
- ✅ Both subscriptions resolve the listener lazily (no `app(...)` outside a
  closure in the provider).
- ✅ `npm run gate` green.

---

## Phase 6 — Participant-facing UI (join / edit / leave / list)

**Goal.** Replace the static "you are not taking part" message with the real
enrolment surface: join with a pre-filled preferences editor, edit it, leave,
and see who else is enrolled.

**Context.** Read **architecture §4 (frontend architecture)** and functional
spec §4.1, §4.2 and §6 (the visibility/privacy row).

What earlier phases left behind, and that this phase only *calls*:
- `SecretGiftConfigService::isRegistrationOpen(Activity $activity): bool` and
  `participantsWithProfiles(int $activityId): Collection<int, ProfileDto>`
  (ordered by `display_name`, never carrying `preferences`).
- Three routes, all already enforcing the rules server-side (403/404 on their
  own): `secret-gift.participants.store` (POST),
  `secret-gift.participants.update` (PUT), `secret-gift.participants.destroy`
  (DELETE), each taking the activity id. **This phase adds no authorization** —
  hiding a button is not a rule.

`SecretGiftComponent` (`.../SecretGift/View/Components/SecretGiftComponent.php`)
is the class component behind `secret-gift::secret-gift-component`; it already
resolves `$participant` via `SecretGiftService::getParticipant()` and passes
`$isParticipant`, `$isPreview`, `$isActive`, `$isEnded`, `$assignmentAsGiver`,
`$assignmentAsRecipient`, `$recipientProfile`, `$recipientPreferences`,
`$giverProfile` to `secret-gift::components.secret-gift`.

**Deliverables.**
- `.../SecretGift/View/Components/SecretGiftComponent.php` — inject
  `SecretGiftConfigService`; add to the view payload:
  `$participant` (the model, for its `preferences`), `$registrationOpen`
  (`isRegistrationOpen($this->activity)`), and `$participants`
  (`participantsWithProfiles()`, resolved **only when `$isParticipant`** — an
  outsider must not pay for, or be able to trigger, that query).
- `.../SecretGift/Resources/views/components/secret-gift.blade.php` —
  restructure the top-level branch. Target shape:
  - A participant-list block, rendered **whenever `$isParticipant`**,
    independent of state and of whether registration is still open: display
    names only, no preferences, no pairing information. Empty state (the caller
    is the only participant) gets its own line rather than an empty list.
  - `@if($isPreview)`:
    - `$isParticipant && $registrationOpen` → a `<form method="POST">` with
      `@method('PUT')` posting to `secret-gift.participants.update`, wrapping
      `<x-editor::rich-text name="preferences" id="sg_preferences">` seeded from
      `old('preferences', $participant->preferences)`; plus a "leave" button that
      dispatches `open-modal` for an `<x-shared::confirm-modal
      name="sg-leave" :action="route('secret-gift.participants.destroy', $activity->id)" method="DELETE">`.
    - `$isParticipant && ! $registrationOpen` → the saved preferences rendered
      read-only, with a "registration closed" line. No edit form, no leave
      button.
    - `! $isParticipant && $registrationOpen` → a `<form method="POST">` to
      `secret-gift.participants.store` wrapping the same rich-text editor,
      seeded from `old('preferences', __('secret-gift::secret-gift.preferences_template'))`
      — that key already exists in the lang file and holds the likes / dislikes /
      fanart / genres / other-info template. Submit button = "Join".
    - `! $isParticipant && ! $registrationOpen` → the existing
      `not_participant` message, reworded to say registration is closed.
  - The remaining branches (`!$isParticipant` outside preview →
    `not_participant`; `!$assignmentAsGiver` → `no_assignment_yet`; else the
    existing two-tab gift UI) stay exactly as they are.
  - `<x-shared::flash-block />` or the page's existing flash surface must show
    the `flash.joined` / `flash.preferences_saved` / `flash.left` messages the
    controllers set — check whether `calendar::activity.show` already renders
    one and add it if not.
- `.../SecretGift/Resources/lang/fr/secret-gift.php` — new `enrolment` block:
  `join_title`, `join_button`, `preferences_label`, `preferences_hint`,
  `save_preferences`, `leave_button`, `leave_confirm_title`,
  `leave_confirm_body`, `leave_confirm_cancel`, `leave_confirm_confirm`,
  `registration_closed`, `registration_closed_participant`,
  `participants_title`, `participants_alone`. Reword the existing
  `not_participant` if it now reads wrong. French only.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/SecretGift/EnrolmentPageTest.php` — feature
tests hitting `route('calendar.activities.show', $activity->slug)`:
- `it('offers the join form with the preferences template to a confirmed non-participant during the open window')`
  — asserts the form action and that the template's first phrase is present
- `it('shows the edit form and the leave action to an enrolled user during the open window')`
- `it('shows neither the join form nor the leave action once the deadline has passed')`
- `it('shows neither once the activity has been shuffled')`
- `it('shows the participant list only to enrolled users')` — a second
  participant's display name is visible to an enrolled user and absent for a
  non-enrolled one
- `it('never renders another participant\'s preferences in the list')` — seed a
  distinctive preferences string on another participant, `assertDontSee` it
- `it('shows the alone state when the caller is the only participant')`
- `it('still shows the participant list to an enrolled user after registration closed')`
- `it('leaves the active-state gift tabs untouched')` — the existing
  `SecretGiftPageTest` cases must still pass; add one assertion here that an
  ACTIVE shuffled activity renders the gift-preparation tab and no join form.

**Acceptance.**
- ✅ A `user-confirmed` on a PREVIEW activity with an open window sees a join
  form pre-filled with the preferences template.
- ✅ After joining, the same page offers an edit form and a confirm-modal-backed
  leave action, and lists the other participants by display name.
- ✅ No participant's `preferences` text appears anywhere in the participant
  list markup, for any viewer.
- ✅ A non-enrolled viewer sees no participant list at all.
- ✅ Once the deadline passes or the shuffle runs, no join / edit / leave
  control is rendered, and the participant list still is.
- ✅ The existing `SecretGiftPageTest` and `SaveGiftTest` pass unchanged.
- ✅ `npm run gate` green.

---

## Phase 7 — Moderator & admin shuffle screen

**Goal.** Make the existing `ShuffleService` reachable from the admin activity
edit page, for moderators, admins and tech admins, with the participant list and
count in front of it.

**Context.** Read **architecture §3.3 (policy — shuffle screen and action),
§3.5 (routes), §4 (frontend) and §9 (risks)**, plus functional spec §4.4.

What earlier phases left behind:
- `secret-gift::secret-gift-config` — the anonymous Blade component at
  `.../SecretGift/Resources/views/components/secret-gift-config.blade.php`,
  already returned by `SecretGiftRegistration::configComponentKey()` and already
  rendering the `registration_ends_at` field. It is rendered by
  `calendar::pages.admin.activities._form` **inside** the activity's `<form>`,
  with `$activity = null` on the create page.
- `SecretGiftConfigService::participantsWithProfiles(int $activityId)` —
  display names only, ordered, never carrying preferences.

`ShuffleService::performShuffle(Activity $activity): int` already exists: it
shuffles the participant user ids into a single cycle, throws
`InvalidArgumentException` under 2 participants, deletes **every** existing
assignment for the activity inside a transaction, and returns the participant
count. `hasBeenShuffled(Activity $activity): bool` is its companion.
`ShuffleService` knows nothing about activity state — **the controller** blocks
`ActivityState::ACTIVE` (decision #6: re-shuffling is free until the activity
goes active, then blocked entirely, shuffled or not).

**Deliverables.**
- `.../SecretGift/Http/Controllers/SecretGiftShuffleController.php` — new,
  single action `shuffle(Activity $activity)`:
  - reject with 403 if `$activity->state === ActivityState::ACTIVE` (or ENDED /
    ARCHIVED — anything at or past ACTIVE);
  - call `ShuffleService::performShuffle($activity)`, catching
    `InvalidArgumentException` and returning `back()->with('error', …)` with the
    "not enough participants" message rather than a 500;
  - on success `back()->with('success', __('secret-gift::secret-gift.flash.shuffled', ['count' => $count]))`.
- `.../SecretGift/Http/routes.php` — a new group, mirroring QuoteContest's admin
  group but with the broader role set functional spec §3 asks for:
  ```php
  Route::middleware(['web', 'auth', 'role:' . Roles::MODERATOR . ',' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
      ->prefix('admin/calendar/secret-gift/{activity}')
      ->name('calendar.admin.secret-gift.')
      ->group(fn () => Route::post('/shuffle', [SecretGiftShuffleController::class, 'shuffle'])->name('shuffle'));
  ```
  (`role` is the registered middleware alias; `Roles` constants live in
  `App\Domains\Auth\Public\Api\Roles`.)
- `.../SecretGift/Resources/views/components/secret-gift-config.blade.php` —
  append, **guarded by `@if($activity)`**, a `@push('activity-config-extras')`
  block. It must be pushed, not inlined: it needs its own `<form>`, and the
  component renders inside the activity's form — nested forms are illegal HTML
  and browsers silently drop the inner one, so its button would submit the
  activity instead. The create/edit pages render `@stack('activity-config-extras')`
  after `</form>`. The block contains:
  - the participant count (the raw `SecretGiftParticipant` row count for the
    activity — that is what `performShuffle()` counts, not the profile-resolved
    list) and the participant list from `participantsWithProfiles()`, with an
    empty state;
  - a "Shuffle" button, disabled with an explanatory line when the count is < 2,
    and disabled with a different line when `$activity->state` is ACTIVE or
    later;
  - the button opens an `<x-shared::confirm-modal name="sg-shuffle"
    :action="route('calendar.admin.secret-gift.shuffle', $activity->id)"
    method="POST">` whose body **states plainly that re-running destroys every
    existing assignment and the gifts attached to them** (architecture §9, first
    risk: this feature makes a destructive Artisan command clickable);
  - a line saying whether the activity has already been shuffled.
- `.../SecretGift/Resources/lang/fr/secret-gift.php` — extend the `config` block
  with `shuffle_title`, `participants_count`, `participants_empty`,
  `shuffle_button`, `already_shuffled`, `shuffle_disabled_not_enough`,
  `shuffle_disabled_active`, `shuffle_confirm_title`, `shuffle_confirm_body`,
  `shuffle_confirm_cancel`, `shuffle_confirm_confirm`; and `flash.shuffled`,
  `flash.shuffle_not_enough_participants`. French only.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/SecretGift/ShuffleScreenTest.php`:
- `it('allows a moderator to shuffle')` — 3 participants on a PREVIEW activity,
  assert 3 `calendar_secret_gift_assignments` rows and a redirect back
- `it('allows an admin to shuffle')`
- `it('allows a tech admin to shuffle')`
- `it('refuses a confirmed user', 403)` and `it('refuses a plain user', 403)` —
  the security test for the role gate, in this phase, not a later one
- `it('redirects a guest to login')`
- `it('refuses to shuffle fewer than 2 participants')` — asserts an error flash
  and zero assignment rows, not a 500
- `it('refuses to shuffle once the activity is active', 403)` — and asserts the
  existing assignments are untouched
- `it('replaces the previous assignments when re-shuffled while still in preview')`
  — assignment ids change, count stays equal to the participant count
- `it('shows the participant list and count on the admin edit page')` — GET
  `calendar.admin.activities.edit` as a moderator, assert a participant's
  display name and the count are present
- `it('never shows a participant\'s preferences on the admin edit page')`
- `it('renders no shuffle panel on the create page')` — `$activity` is null
  there

**Acceptance.**
- ✅ POST `admin/calendar/secret-gift/{activity}/shuffle` succeeds for
  moderator, admin and tech admin, and 403s for `user-confirmed` and `user`.
- ✅ Shuffling with 3 participants produces exactly 3 assignments, each giver
  distinct from their recipient.
- ✅ Shuffling an ACTIVE activity is refused and changes no row.
- ✅ Re-shuffling a PREVIEW activity replaces the prior assignment set.
- ✅ The panel is absent from the activity **create** page and present on the
  **edit** page of a Secret Gift activity only.
- ✅ The confirm modal's body names the destructive consequence.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

Seed hint: a PREVIEW Secret Gift with an open window, at least three
participants, and one with a distinctive `preferences` string, plus a second
activity whose deadline has already passed.

| Surface | Check | OK? |
|---------|-------|-----|
| Admin → activity create, type `secret-gift` | The type panel appears on selecting the type; it shows the *fin d'inscription* field and **no** shuffle panel. The Restrictions section shows no "Inscription requise" toggle and no "Nombre max de participants" input. | |
| Admin → activity create, bad deadline | Submitting a deadline before `preview_starts_at` shows a French error under the field; the form keeps every entered value. | |
| Admin → activity edit, Secret Gift | Deadline prefilled. Below the form: participant count, participant list by display name, and the shuffle button. No participant's preferences text anywhere on the page. | |
| Admin → activity edit, < 2 participants | Shuffle button disabled with a readable explanation, not silently inert. | |
| Admin → shuffle confirm modal | Opens on click; body plainly warns that re-running destroys existing assignments and the gifts on them; cancel closes it and changes nothing. | |
| Admin → after shuffling | Success flash with the count; page reloads showing "already shuffled". | |
| Admin → activity edit, ACTIVE activity | Shuffle button disabled with the "activity is active" reason. | |
| Admin → activity edit, as **moderator** | Same panel and same shuffle button as an admin sees; reachable from the admin nav. | |
| Activity page, PREVIEW + open, non-participant (`user-confirmed`) | Join form with the rich-text editor pre-filled with the likes/dislikes/fanart/genres template; toolbar renders; no participant list. | |
| Activity page, right after joining | Success flash; editor now holds the saved preferences; leave button present; participant list appears. | |
| Activity page, participant list | Display names only. No preferences, no pairing hints, no "who has whom". Sensible when the caller is the only participant (the *alone* line, not an empty box). | |
| Activity page, leave flow | Confirm modal opens, cancel is harmless, confirm removes the enrolment and returns the page to the join form. | |
| Activity page, PREVIEW + deadline passed, participant | Preferences shown read-only, no edit form, no leave button, "registration closed" line, participant list still visible. | |
| Activity page, PREVIEW + deadline passed, non-participant | "Registration closed" message, no join form, no participant list. | |
| Activity page, PREVIEW + already shuffled, before the deadline | Same closed state as above — the shuffle closes registration early. | |
| Activity page, ACTIVE + shuffled, participant | The existing two-tab gift UI is unchanged: recipient name, their preferences, gift editor. No enrolment controls leaked into it. | |
| Activity page as a role excluded by `role_restrictions` | 404, not a rendered page with the button missing. | |
| Activity page, **mobile** (≈390px) | Join form, rich-text toolbar, participant list and leave button all usable and unclipped. | |
| Admin activity edit, **mobile** | Shuffle panel and participant list readable; confirm modal fits. | |
| After deactivating a participant (pre-shuffle) | Their name is gone from the participant list on the next load, for both the activity page and the admin panel. | |

## Open items

1. **`DateOrderRule` relocation is PLAN's call, not the architecture's.**
   Architecture §6 says the deadline is validated "via `DateOrderRule`" without
   saying where the class lives. It sits in the QuoteContest plugin today.
   Phase 1 moves it to `Calendar/Private/Support/`; the alternative is that
   SecretGift imports the sibling plugin's namespace, which passes deptrac (one
   `CalendarPrivate` layer) but would render `quote-contest::` French strings on
   a Secret Gift admin form. **Needed before phase 3.** Reversible: revert phase
   1 and change one `use` statement.
2. **A Secret Gift activity with no settings row reads as "registration
   closed".** `registration_ends_at` is mandatory on the admin form, but
   `CalendarPublicApi::create()` bypasses `configRules()`, so a row can be
   created without one. PLAN chose fail-safe (closed) over fail-open. Not
   arbitrated by the user; surface it in the WRAP summary. **Needed before phase
   3.**
3. **Test fixtures must create the settings row.** `createActiveSecretGift()` in
   `Tests/Feature/SecretGift/helpers.php` builds through the public API, so
   without a settings row every existing Secret Gift fixture would read as
   closed. Phase 3 adds the row and a `createPreviewSecretGift()` helper.
   Mechanical, but if it is missed, phases 4/6/7 will fail in confusing ways.
   **Needed before phase 3.**
4. **The `role_restrictions` gate on the write routes is not what architecture
   §3.3 implies.** It says the routes "sit behind the same `role_restrictions`
   check that already gates the activity page (`web, auth, verified`
   middleware)". That middleware does not check `role_restrictions` — only
   `ActivityService::findVisibleBySlugOrFail()` does, and the write routes never
   call it. QuoteContest has the same shape and accepts it. Phase 4 adds an
   explicit `AuthPublicApi::hasAnyRole()` check in the controller (404, matching
   the page's posture) so that functional spec §3's "whoever can already see the
   activity page can enrol" is actually enforced rather than assumed. **Needed
   at phase 4.**
5. **Participant list presentation is unspecified beyond "display names".**
   Architecture §4 says display names via `ProfilePublicApi::getPublicProfiles()`;
   it does not say whether avatars or profile links are included. PLAN assumes
   plain display names, no avatar, no link — the minimum that satisfies
   functional spec §4.1.4. Cheap to extend at phase 6 if VERIFY finds it bare.
6. ~~The docs gate is already red on this branch~~ — **fixed**, in a standalone
   commit before phase 1, not part of any phase. Two uncommitted working-tree
   changes each broke links: this task's own REFINE step deleted
   `docs/Feature_Planning/calendar-subscription/` per decision #1 (absorbed),
   breaking `_done/calendar.md`'s pointer to it; separately, `Quotes.md` /
   `Quotes_Architecture.md` / `Quotes_Implementation_Plan.md` were deleted by
   hand, breaking three pointers in `annotations/`. Neither break came from a
   prior commit — both were sitting uncommitted, which is exactly why they went
   unnoticed. Links now point at `app/Domains/Quote/README.md` and
   `secret-gift-enrolment/` instead. `npm run gate -- --docs` is green.
7. **Config payload key.** Architecture does not name the form key for the
   plugin block. PLAN picks `secret_gift` (so the input is
   `secret_gift[registration_ends_at]` and the error key
   `secret_gift.registration_ends_at`), matching QuoteContest's `quote_contest`.
   Fixed at phase 3; phases 6 and 7 do not depend on it.
