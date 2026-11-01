# `fanart.removed_with_content` records who removed the content — context

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### REFINE → BUILD (2026-11-01)

- 0 questions asked (auto mode). Request read as: the notification has no sender
  (`source_user_id` null) and no `actor_*` in its payload; wanted = the remover.
- Likeliest reversal: A1 (story deleted via account deletion stays sender-less).
- Trap: `NotificationPublicApi::createNotification` throws `ValidationException` if the
  source user has no public profile, and `FanartNotifier::send` swallows it, which would
  silently drop the notification. Hence A1: never pass a remover who may be gone.
- Event contract change is additive (nullable, default null, `fromPayload` tolerant) so
  stored/audit events and other listeners/tests keep working.
- Display text unchanged (it never mentions the remover).

### BUILD phase 1 -> WRAP (2026-11-01)

- No plan deviation. Visual QA (§3.2 notification list render) not run; text and payload keys unchanged.

## 1. Functional spec

### 1.1 Overview

When a chapter or a story is deleted, fanartists whose fanart disappears get
`fanart.removed_with_content`. That notification currently has no sender and an
empty actor in its payload. It must carry the id (and name/slug snapshot) of the user
who removed the content.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| remover | The user who deleted the chapter / story that took the fanart with it |

### 1.3 Roles & visibility

Unchanged. Only authors can delete a chapter/story; recipients are the fanartists
(non-author submitters), as today. The remover is never a recipient.

### 1.4 Functional requirements

1. Chapter deleted by user U: each notified fanartist's notification has sender U and
   payload `actor_id` / `actor_name` / `actor_slug` = U.
2. Story deleted by author U from the story page: same.
3. Story deleted as a side effect of an author's account removal: no remover is
   recorded (A1); behaviour as today.
4. Recipient set, text and links unchanged. Already-sent notifications untouched.

### 1.5 Lifecycle

Payload is a snapshot (name/slug copied at send time). If the remover is later
deactivated/deleted, the notification keeps its snapshot, like other fanart notifications.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | No change |
| Visibility / privacy | Remover is a public profile; text does not display them |
| Settings | N/A — notification type and preference unchanged |
| Notifications | Only this type's sender/payload change |
| Domain events | `Story.Deleted` and `Chapter.Deleted` gain an optional `deletedByUserId` (additive) |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | See 1.5, A1 |
| Media / Search | N/A |
| i18n | N/A — no new wording |
| Mobile / Accessibility | N/A |

### 1.7 Out of scope

- Showing the remover in the displayed text.
- Rewriting existing notifications.
- Other Story/ReadList notifications' sender handling.
- Moderator-triggered story/chapter deletion (none exists).

### 1.8 Open questions

None blocking. Non-blocking: if the request meant only `source_user_id` and not the
payload actor fields, the extra payload fields are harmless.

## 2. Architecture

### 2.1 Domain placement

- **Story**: `StoryDeleted` and `ChapterDeleted` get `public readonly ?int $deletedByUserId = null`
  (last constructor arg; in `toPayload` / `fromPayload`, tolerant of absence; version stays 1).
  `StoryService::deleteStory(Story $story, ?int $deletedByUserId = null)`; the controller
  passes `Auth::id()`, `deleteStoriesByAuthor` passes nothing. `deleteChapter` already has `$userId`.
- **Fanart**: both listeners forward `$event->deletedByUserId` as `$actorId` to
  `FanartNotifier::fanartRemovedWithContent` (parameter already exists but is unused).
  The notifier builds the content with actor id/name/slug (profile lookup, like `forStory`)
  and sends with the actor as sender when set; null keeps today's behaviour.

No table, no deptrac edge (Fanart already uses `StoryPublicApi`, `ProfilePublicApi`, Story events).

### 2.6 Testing strategy

Integration in `FanartLifecycleTest` / `FanartNotificationsTest`; a Story event round-trip
(payload with and without the field).

### 2.7 Tradeoffs locked

| # | Question | Options | Chosen | Why |
|---|----------|---------|--------|-----|
| 1 | Where to get the remover | read `Auth` in listener / add to events | events | listeners must not rely on request state; events are the contract |
| 2 | Account-removal path | pass the author / nothing | nothing | sender may be gone, send would silently drop the notification |

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Carry the remover through events into the notification | S | — | DONE |

### Phase 1 — Carry the remover

**Goal.** `fanart.removed_with_content` has the remover as sender and in the payload.
**Reads.** §2.1.

**Deliverables.**
- `app/Domains/Story/Public/Events/StoryDeleted.php`, `ChapterDeleted.php`: optional `deletedByUserId` (payload round-trip).
- `app/Domains/Story/Private/Services/StoryService.php` (`deleteStory`), `ChapterService.php` (`deleteChapter` passes `$userId`), `Story/Private/Controllers/StoryController.php` (passes `Auth::id()`).
- `app/Domains/Fanart/Private/Listeners/DeleteFanartsOnChapterDeleted.php`, `DeleteFanartsOnStoryDeleted.php`: forward actor.
- `app/Domains/Fanart/Private/Services/FanartNotifier.php` (`fanartRemovedWithContent`): actor fields in payload + sender.
- `docs/notification-types.md`: annotate the row; Fanart `AGENTS.md` line 16 if wording needs it.

**Tests.**
- Chapter deletion by an author: notification `source_user_id` = author, payload `actor_id` = author.
- Story deletion by an author via controller: same.
- Account-removal story deletion: still notifies, `source_user_id` null.
- Event payload round-trip with and without `deletedByUserId`.

**Acceptance.**
- Checks above pass; existing Fanart/Story/ReadList/Statistics tests green.
- `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Notification list | A removed-with-content notification still renders, text unchanged | n/a in browser: Blade render of a payload, covered by PHP tests (FanartNotificationsTest/FanartLifecycleTest, 27 pass); no client-side behaviour |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Story deleted through author account removal records no remover (author's profile may be gone; send would swallow the failure) | REFINE | yes |
| A2 | "Remover ID" means both notification sender and payload `actor_id`, with name/slug snapshot | REFINE | yes |
| A3 | Co-author deleting a story/chapter is the remover (not the story creator) | REFINE | yes |

## 5. Build & verify log

- Phase 1 DONE (2026-11-01): events carry `deletedByUserId`; notifier sets sender + `actor_*`. Tests: 3 in FanartLifecycleTest, Story `DeletedEventsPayloadTest`.
- VERIFY (2026-11-01): PASS. The single QA row is server-rendered text with no Alpine/JS/asset change, so it was moved to PHP feature tests (Fanart notification tests re-run: 27 pass). No browser spec written. Handoff VERIFY -> WRAP: no flaky or manual rows; no feature spec to delete.
