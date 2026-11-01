# `fanart.removed_with_content` records who removed the content

**Status:** DONE — 2026-11-01 · **Domain(s):** `Fanart`, `Story`

## What it does

When a chapter or story is deleted, the `fanart.removed_with_content`
notification to the fanartists now has the deleting user as sender
(`source_user_id`) and as `actor_id` / `actor_name` / `actor_slug` in the payload
(snapshot at send time). Displayed text, recipients and links are unchanged.

## Key behaviour

- Chapter deleted by user U: sender and actor = U. Story deleted from the story page by U: same.
- Co-author deleting counts as the remover (not the story creator).
- Story deleted as a side effect of an author's account removal: no remover
  (sender null, `actor_id` 0, empty name/slug), as before. The author's profile
  may be gone, and `NotificationPublicApi::createNotification` throws
  `ValidationException` for a source without public profile, which
  `FanartNotifier::send` swallows, which would silently drop the notification.
- Already-sent notifications untouched. The remover is never a recipient.

## Where the code lives

| Concern | Path |
|---------|------|
| Events (optional `?int $deletedByUserId = null`, last ctor arg, tolerant `fromPayload`, version stays 1) | `app/Domains/Story/Public/Events/StoryDeleted.php`, `ChapterDeleted.php` |
| Who deletes | `Story/Private/Services/StoryService.php` (`deleteStory(Story, ?int $deletedByUserId = null)`), `ChapterService.php` (`deleteChapter` passes `$userId`), `Story/Private/Controllers/StoryController.php` (`Auth::id()`); `deleteStoriesByAuthor` passes nothing |
| Listeners forwarding the actor | `app/Domains/Fanart/Private/Listeners/DeleteFanartsOnChapterDeleted.php`, `DeleteFanartsOnStoryDeleted.php` |
| Notifier (profile lookup, sender) | `app/Domains/Fanart/Private/Services/FanartNotifier.php` (`fanartRemovedWithContent`) |
| Tests | `Fanart/Tests/Feature/FanartLifecycleTest.php` (3), `Story/Tests/Unit/DeletedEventsPayloadTest.php` |
| Docs | `docs/notification-types.md`, `app/Domains/Fanart/AGENTS.md` |

## Extension points used

`NotificationPublicApi::createNotification(..., ?int $sourceUserId)`; domain events (additive field).

## Decisions / counter-intuitive

- The remover comes from the events, not `Auth` in the listener: listeners must not rely on request state.
- Unlike `fanart.deleted_by_moderation` (system notification, no sender), this one keeps its sender.
- Plan and code agree; no deviation.

## Not done

- Non-goals: showing the remover in the displayed text; rewriting existing notifications; other Story/ReadList notifications' sender handling; moderator-triggered deletion (none exists).
- Open: none.
- E2E: none written (server-rendered text, covered by PHP tests); nothing to retire.
