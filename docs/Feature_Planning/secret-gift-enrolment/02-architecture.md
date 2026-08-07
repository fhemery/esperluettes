# Secret Gift — enrolment — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Entirely inside the existing `SecretGift` activity plugin
(`app/Domains/Calendar/Private/Activities/SecretGift/`). Nothing here needs a
new domain or a new extension point on base Calendar — `ActivityRegistrationInterface`
(`configComponentKey()` / `configRules()` / `persistConfig()`) already exists
and is currently unused by SecretGift (`SecretGiftRegistration::configComponentKey()`
returns `null`); this feature is the first thing that makes it do something.

The one change to base Calendar is a **removal**, not a new capability: per
functional spec §8/decision #7, `requires_subscription` and `max_participants`
on `calendar_activities` are dead code (nothing ever read or enforced them) and
are dropped. That column drop cascades through `Activity`'s `#[Fillable]`/casts,
`ActivityToCreateDto`/`ActivityToUpdateDto`/`ActivityDto`, `ActivityService::create/update`,
the admin `ActivityRequest` rules, and `_form.blade.php` — all base-Calendar
files, none of them SecretGift's.

### 1.1 Changes in other domains

None. `AuthPublic` (for the deactivation/deletion listener), `MediaPublicApi`
(already used for gift images/sounds) and `Shared`'s `ProfilePublicApi`
(already used for display names) cover everything this feature needs, and all
three are already allowed dependencies of `CalendarPrivate`.

## 2. Data model

### 2.1 Tables

**New** — `calendar_secret_gift_settings`, one row per Secret Gift activity,
mirroring `calendar_quote_contest_settings`' shape minus the notification
markers it doesn't need:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `activity_id` | FK → `calendar_activities.id` | unique, `cascadeOnDelete` |
| `registration_ends_at` | datetime, not null | |
| `created_at` / `updated_at` | timestamps | |

**Unchanged shape, now populated** — `calendar_secret_gift_participants`
(`activity_id`, `user_id`, `preferences` nullable text) already has everything
this feature needs; no migration required for it.

**`calendar_activities`** — drop `requires_subscription` (boolean) and
`max_participants` (nullable int) columns. `down()` re-adds them with their
original defaults so the migration is reversible.

### 2.2 Model

```php
#[Table('calendar_secret_gift_settings')]
#[Fillable(['activity_id', 'registration_ends_at'])]
class SecretGiftSettings extends Model
{
    protected $casts = ['registration_ends_at' => 'datetime'];
    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
}
```

### 2.3 Lifecycle rules

- `calendar_secret_gift_settings` cascades on activity deletion (`cascadeOnDelete`
  on `activity_id`), same as `calendar_secret_gift_participants` and
  `calendar_secret_gift_assignments` already do — unchanged, existing behaviour
  extended to the new table.
- Per functional spec §5: a participant row is deleted automatically when the
  user is deactivated or deleted, **only if the activity has not been
  shuffled yet** (`ShuffleService::hasBeenShuffled()` false). After shuffle,
  nothing touches the assignment — matches the existing, deliberately
  unaddressed Jardino gap.

## 3. PHP architecture

### 3.1 Public API

None. Nothing outside SecretGift needs to enrol, read participants, or trigger
a shuffle — `CalendarPublicApi` gains no new methods.

### 3.2 Services

**`SecretGiftConfigService`** (new) — the admin-facing counterpart to
`QuoteContestConfigService`, owning the settings row and the registration-window
query:

```php
class SecretGiftConfigService
{
    public function __construct(private readonly ShuffleService $shuffle) {}

    public function settingsFor(int $activityId): ?SecretGiftSettings;
    public function saveSettings(int $activityId, mixed $registrationEndsAt): SecretGiftSettings;

    /** state === preview && now < registration_ends_at && not yet shuffled */
    public function isRegistrationOpen(Activity $activity): bool;

    /** Participants joined with display names, for the shuffle screen and the reader-facing list. */
    public function participantsWithProfiles(int $activityId): Collection;
}
```

**`SecretGiftService`** (existing, extended) — join/leave/preferences on the
participant side, alongside its existing assignment-side methods:

```php
public function join(Activity $activity, int $userId, ?string $preferences): SecretGiftParticipant;
public function updatePreferences(SecretGiftParticipant $participant, ?string $preferences): void;
public function leave(Activity $activity, int $userId): void;

/** Auth-event listener entry point: deletes every not-yet-shuffled participant row for this user. */
public function removeParticipantsForUser(int $userId): void;
```

Controllers call these services; none of the three new controller actions
touch `SecretGiftParticipant`/`SecretGiftSettings` directly.

### 3.3 Policy / authorization

- **Join / edit preferences / leave**: no new role gate. Reachable only through
  routes that sit behind the same `role_restrictions` check that already gates
  the activity page (`web, auth, verified` middleware, same route prefix group
  as the existing reader-facing `SecretGiftController` routes). Each controller
  action re-derives `SecretGiftConfigService::isRegistrationOpen($activity)`
  itself rather than trusting that the page was rendered — same posture
  `QuoteContestEntryController`/`QuoteContestVoteController` already document
  ("a forged POST never went past a rendered page").
- **Shuffle screen and action**: route middleware
  `role:moderator,admin,tech-admin` — broader than QuoteContest's category-CRUD
  gate (`admin,tech-admin`) because functional spec §3 explicitly includes
  moderators, matching the role set the base activities-admin CRUD already
  uses. Embedded inside the existing activity edit page via
  `configComponentKey()`; no new admin nav entry (neither Jardino nor
  QuoteContest register one — the config-panel hook is the established
  mechanism for a per-type admin screen).
- **Participant list visibility**: enrolled-only, enforced by only rendering
  the list inside the Blade component's existing `$isParticipant` branch — the
  same mechanism that already gates gift/assignment info in that view, not a
  separate authorization layer.

### 3.4 Events and listeners

No new domain events emitted — functional spec §6 states no other domain
plausibly needs to react to enrolment.

**New listener**, subscribed as lazy closures in
`SecretGiftServiceProvider::registerEventListeners()` (matching QuoteContest's
pattern — `app(Listener::class)->handle($event)` inside a closure, not
resolved eagerly at boot, per that provider's documented reason: eager
resolution froze singletons at boot and broke unrelated tests):

```php
$eventBus->subscribe(UserDeleted::class, static fn ($e) => app(SecretGiftService::class)->removeParticipantsForUser($e->userId));
$eventBus->subscribe(UserDeactivated::class, static fn ($e) => app(SecretGiftService::class)->removeParticipantsForUser($e->userId));
```

### 3.5 Routes, controllers, form requests

No `PATCH`. New reader-facing controller `SecretGiftParticipantController`,
same prefix group as the existing `SecretGiftController` reader routes
(`calendar/secret-gift/{activity}`, `web, auth, verified`):

| Method | Path | Action |
|---|---|---|
| POST | `/participants` | `store` — join, preferences from the same submitted form |
| PUT | `/participants` | `update` — edit own preferences |
| DELETE | `/participants` | `destroy` — leave |

New admin-facing controller `SecretGiftShuffleController`, under
`admin/calendar/secret-gift/{activity}`, `role:moderator,admin,tech-admin`:

| Method | Path | Action |
|---|---|---|
| POST | `/shuffle` | `shuffle` — calls `ShuffleService::performShuffle()`; the controller itself blocks when `$activity->state === ActivityState::ACTIVE` (`ShuffleService` only knows about the ≥2-participants rule, not activity state) |

New form request `SavePreferencesRequest`: `'preferences' => ['nullable', 'string', 'max:65535']`
— same shape as `SaveGiftRequest`'s `gift_text` rule; sanitized in the
controller with `Purifier::clean($text, 'strict')`, same as `gift_text`.

## 4. Frontend architecture

- `secret-gift.blade.php`'s `@if(!$isParticipant)` branch is replaced: a
  "Join" form wraps `<x-editor::rich-text name="preferences">`, pre-filled
  from `secret-gift::secret-gift.preferences_template` (already exists in the
  lang file) via `old('preferences', ...)`.
- When `$isParticipant` and registration is open: the same rich-text component
  re-opens pre-filled with `$participant->preferences` for editing, plus a
  "leave" action behind `<x-shared::confirm-modal>` (same component
  QuoteContest uses for category deletion).
- Participant list — display names via `ProfilePublicApi::getPublicProfiles()`
  (bulk, one call) — renders whenever `$isParticipant`, independent of whether
  registration is still open.
- New anonymous component `secret-gift::secret-gift-config`
  (`Blade::anonymousComponentPath`, mirroring `quote-contest-config`), embedded
  in the activity admin edit page: a `<x-shared::datetime-local-input>` for
  `registration_ends_at` inline in the form, and — `@push('activity-config-extras')`-ed
  after `</form>`, since it needs its own `<form>` — the shuffle screen
  (participant count, participant list, "Shuffle" button behind
  `<x-shared::confirm-modal>` given the shuffle's documented destructive
  re-run behaviour).
- No new Alpine store or JS module: the rich-text component, confirm-modal and
  datetime-local input are all existing shared components.

## 5. Deptrac

No new edges. Everything used — `AuthPublic` (events), `MediaPublicApi`,
`Shared` (`ProfilePublicApi`) — is already in `CalendarPrivate`'s allow-list.

## 6. Testing strategy

Integration tests (default level):

- Join / edit preferences / leave: happy path, rejected once
  `registration_ends_at` has passed, rejected once shuffled, inherits the
  activity's `role_restrictions` gate.
- `registration_ends_at` admin-form validation: ordering against
  `preview_starts_at`/`active_starts_at` via `DateOrderRule` (mirrors
  QuoteContest's existing `DateOrderRule` tests).
- Shuffle: role gate (moderator/admin/tech-admin allowed, others 403), blocked
  under 2 participants, blocked once the activity is `active`, re-shuffle
  replaces prior assignments (existing `ShuffleService` behaviour, now reachable
  from the UI).
- Deactivation/deletion listener: participant row removed pre-shuffle, left
  untouched post-shuffle.
- Regression coverage for the `max_participants`/`requires_subscription`
  removal: existing tests that set or assert those fields
  (`ActivityControllerTest`, `UpdateCalendarPublicApiTest`, seeders/helpers)
  updated to drop them.

No unit tests: nothing here is isolated enough to warrant one over a feature
test. No vitest: no new client-side behaviour. Visual QA (VERIFY): join → edit
→ leave as `user-confirmed`, participant list visibility, shuffle screen as
moderator and as admin, the "not enrolled" state as a plain `user`, and the
deadline-passed state.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where does `registration_ends_at` live? | (A) new `calendar_secret_gift_settings` table via the `configComponentKey()`/`configRules()`/`persistConfig()` extension point, validated with `DateOrderRule` against sibling activity dates in the same payload — the pattern already used for QuoteContest's two dates. (B) a plain column on `calendar_activities`, validated inline in `ValidatesActivityPayload` alongside the other four lifecycle dates. | A | Keeps the base `Activity` table generic; the codebase already paid for this pattern once (`QuoteContestSettings`, `DateOrderRule`, no DB read needed). B would repeat the exact mistake this task is removing two fields for. |

## 8. File layout

```
app/Domains/Calendar/Private/Activities/SecretGift/
├── Database/Migrations/
│   └── 2026_08_10_120000_create_calendar_secret_gift_settings_table.php
├── Http/
│   ├── Controllers/
│   │   ├── SecretGiftParticipantController.php   (new)
│   │   └── SecretGiftShuffleController.php        (new)
│   └── Requests/
│       └── SavePreferencesRequest.php             (new)
├── Listeners/
│   └── RemoveParticipantOnUserRemoved.php          (new)
├── Models/
│   └── SecretGiftSettings.php                      (new)
├── Services/
│   └── SecretGiftConfigService.php                 (new)
└── Resources/views/components/
    └── secret-gift-config.blade.php                (new)

app/Domains/Calendar/Database/Migrations/
└── 2026_08_10_121000_drop_participant_limit_fields_from_activities_table.php   (new)
```

## 9. Risks acknowledged

- Shuffle stays destructive on re-run (`Calendar/AGENTS.md`'s existing
  warning — deletes all assignment rows, gift sound files are orphaned on
  `local` disk with nothing to collect them). This feature makes the action
  reachable from the UI instead of only an Artisan command with a confirmation
  prompt, which raises the chance of an accidental click; mitigated with a
  confirm-modal, same as the destructive category-delete action elsewhere.
  Revisit if moderators report accidental re-shuffles.
- `removeParticipantsForUser` scans every activity the user has a participant
  row in, unscoped by activity — fine at the current scale of a handful of
  concurrent Secret Gift activities; revisit if that assumption stops holding.
