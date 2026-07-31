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

## Events Emitted

The Calendar domain emits no domain events.

## Listens To

- `Story::ChapterCreated` → Jardino `UpdateSnapshotWordCount::handleChapterCreated()` — adds chapter word count to all goal snapshots tracking the story.
- `Story::ChapterUpdated` → Jardino `UpdateSnapshotWordCount::handleChapterUpdated()` — applies the word delta (`after.wordCount - before.wordCount`) to snapshots.
- `Story::ChapterDeleted` → Jardino `UpdateSnapshotWordCount::handleChapterDeleted()` — subtracts the deleted chapter's word count from snapshots.

Subscriptions are wired in `JardinoServiceProvider::registerEventListeners()` via `EventBus`.

## Registry Registrations

`CalendarServiceProvider::boot()` registers two built-in types into `CalendarRegistry`:

| Type key | Registration class | Display component |
|----------|--------------------|-------------------|
| `jardino` | `JardinoRegistration` | `jardino::jardino-component` |
| `secret-gift` | `SecretGiftRegistration` | `secret-gift::secret-gift-component` |

New activity types follow the same pattern: implement `ActivityRegistrationInterface`, create a `ServiceProvider`, register both in `CalendarServiceProvider`.
