# Calendar Domain — Agent Instructions

- README: [app/Domains/Calendar/README.md](README.md)

## Public API

- `CalendarPublicApi` — CRUD for `Activity` records (create, getOne, update, delete). Enforces admin-only write access and visibility rules. Other domains must call this, not `ActivityService` directly.
- `CalendarRegistry` — singleton that maps activity type keys to `ActivityRegistrationInterface` instances. New activity types register here at boot; the detail page looks up the display component key here.

## Non-Obvious Invariants

**Activity type is immutable after creation.** `CalendarPublicApi::update()` explicitly rejects type changes with a validation exception. Allowing it would orphan all type-specific rows linked by `activity_id`.

**Slug format is `{slugified-name}-{id}`, always.** Generated on creation and regenerated (same suffix) on name update. Never set the slug manually — the pattern is enforced in `ActivityService`, not the model.

**State is computed from timestamps, not stored.** `Activity::$state` is a computed `Attribute`, not a database column. Any code that needs to filter by state must call the attribute after loading the model. The listing query fetches all non-draft activities and sorts in PHP — no SQL `WHERE state =`.

**`getAllActivitiesSortedByState()` applies role restrictions without admin bypass.** Admins see the same listing as regular users. Draft and Archived are excluded. This is intentional per spec — it is different from the Public API `getOne()`, which does give admins access to Draft records.

**No FK constraint on `created_by_user_id` and all user-id columns in type tables.** Per project architecture rules, cross-domain FK constraints to `users` are forbidden. Store user ids as plain `unsignedBigInteger` columns.

**Jardino snapshot word counts are updated via delta, not recalculated.** `JardinoProgressService::updateSnapshotWordCount()` adds a delta to `current_word_count`. If a chapter event is missed, the snapshot drifts permanently — there is no reconciliation job.

**Secret Gift files are never on the `public` disk.** Access is gated by `SecretGiftService::canViewImage()` / `canViewSound()`; moving them to a public disk makes the visibility rules unenforceable. Sounds are raw files on the `local` disk. Images are Media-domain images on Media's `private` disk under `secret-gift/{activity_id}/`, stored with `MediaPublicApi::storePrivate()` and served with `MediaPublicApi::stream()` after `canViewImage()` — Media itself does no authorization, and `originalUrl()` / `variantUrl()` throw for a private path.

**Secret Gift never deletes a gift image.** Replacing or removing one only rewrites `gift_image_path`; `media:gc` reclaims the file after the grace window. That depends on `SecretGiftServiceProvider` keeping `SecretGiftMediaUsageProvider` registered on `MediaUsageRegistry` — Media's zero-claim guard sits at the `secret-gift/` **root**, so if the provider goes missing the whole root stops being swept and every orphan accumulates silently.

**Secret Gift shuffle is destructive on re-run.** `ShuffleService::performShuffle()` deletes all existing assignment **rows** before creating new ones — the uploaded files are not touched. Gift images survive as unclaimed paths and `media:gc` reclaims them; gift sounds are orphaned on the `local` disk with nothing to collect them. The Artisan command prompts for confirmation, but programmatic callers have no guard.

**A withdrawn quote-contest entry is a filter, not a deletion.** `calendar_quote_contest_entries.withdrawn_at` is stamped when the quoted story turns private or is excluded from events; the row and its votes stay. Every listing, every tally and the one-per-category check must filter on `withdrawn_at IS NULL` — a read path that forgets it silently resurrects a passage nobody may read any more. There is no database constraint expressing this, so keep the reads inside `QuoteContest*Service`. Nothing ever clears the column: a story returning to public does not restore its entries, the reader re-enters by hand.

**Quote-contest anonymity is a query shape, not a template rule.** A submitter's identity and a vote count exist in exactly one family of view models, `Results*ViewModel`, built only by `QuoteContestVoteService::resultsFor()` and only for `QuoteContestModerationController::ROLES`. `VoteEntryViewModel` and `MyEntryViewModel` have no field for either, and adding one would make a Blade slip enough to leak who submitted what — the *Votes* tab is seen by every confirmed user. The *Résultats* tab is likewise absent from the tabs array for everyone else rather than hidden in the template.

**Quote-contest vote counts are computed on read.** One `GROUP BY entry_id` in `resultsFor()`, over the live entries only. There is deliberately no denormalised counter on the entry: its only readers are the handful of moderators who open the tab, and it would need invalidating on every vote, replacement, withdrawal and deletion — including the privacy withdrawals that no user action triggers. Do not add one to "save a query" on a page nobody but moderation loads.

**A quote-contest phase comes from `QuoteContestPhaseService::phaseFor()` and nowhere else.** Every screen and every write guard asks that one method, so a read-only view and the write it hides can never disagree. It reads four datetimes (the activity's two, the settings' two) and puts a boundary instant in the *later* phase. Recomputing a phase from raw dates anywhere else is the bug this centralisation exists to prevent.

**A quote-contest activity is only gated by its `role_restrictions`.** Nothing in the code restricts the contest page to confirmed users — decision #1 is enforced by the admin setting `user-confirmed` + `moderator` + `admin` on the activity itself. The reader write routes re-check phase and ownership in their services, since a forged POST never went past a rendered page.

## Events Emitted

The Calendar domain emits no domain events.

## Listens To

- `Story::ChapterCreated` → Jardino `UpdateSnapshotWordCount::handleChapterCreated()` — adds chapter word count to all goal snapshots tracking the story.
- `Story::ChapterUpdated` → Jardino `UpdateSnapshotWordCount::handleChapterUpdated()` — applies the word delta (`after.wordCount - before.wordCount`) to snapshots.
- `Story::ChapterDeleted` → Jardino `UpdateSnapshotWordCount::handleChapterDeleted()` — subtracts the deleted chapter's word count from snapshots.

- `Story::VisibilityChanged` → QuoteContest `WithdrawEntriesOnStoryIneligible::handleVisibilityChanged()` — stamps `withdrawn_at` on the story's live contest entries unless the new visibility is `public` or `community`. The event carries the new visibility, so no Story read is made.
- `Story::ExcludedFromEvents` → QuoteContest `WithdrawEntriesOnStoryIneligible::handleExcludedFromEvents()` — withdraws the story's live contest entries unconditionally.

Subscriptions are wired in `JardinoServiceProvider::registerEventListeners()` and `QuoteContestServiceProvider::registerEventListeners()` via `EventBus`.

## Registry Registrations

`CalendarServiceProvider::boot()` registers three built-in types into `CalendarRegistry`:

| Type key | Registration class | Display component | Config component |
|----------|--------------------|-------------------|------------------|
| `jardino` | `JardinoRegistration` | `jardino::jardino-component` | — |
| `secret-gift` | `SecretGiftRegistration` | `secret-gift::secret-gift-component` | — |
| `quote-contest` | `QuoteContestRegistration` | `quote-contest::quote-contest-component` | `quote-contest::quote-contest-config` |

New activity types follow the same pattern: implement `ActivityRegistrationInterface`, create a `ServiceProvider`, register both in `CalendarServiceProvider`.

**A type may carry its own configuration.** Beyond `displayComponentKey()`, a registration can return a `configComponentKey()` — a Blade component rendered inside the admin activity create/edit form — and back it with `configRules()` (validation rules merged into `ActivityRequest` for that type only) and `persistConfig(int $activityId, array $validated)`. `ActivityController::store()`/`update()` run the activity write and `persistConfig()` in a single `DB::transaction()`, so an activity can never exist without its type config; throwing from `persistConfig()` rolls the activity back. Jardino and Secret Gift declare none of the three; Quote Contest is the reference implementation. On create the type is chosen in the same form, so every declared config panel is rendered and toggled client-side on the `activity_type` select — only the submitted type's rules are applied server-side. A panel needing its own `<form>` (Quote Contest's category editor) must push it to the `activity-config-extras` stack, which the create/edit pages render after `</form>`: nested forms are illegal HTML and browsers silently drop the inner one.
