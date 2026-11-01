# Fanart moderation notification as system notification — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### REFINE (+ DESIGN+PLAN fast path) → BUILD (2026-11-01)

- 0 blocking questions (auto mode). Single-file-ish change in `FanartNotifier`.
- Trap: the moderator must still be excluded from the recipients (a moderator
  can also be a story author or the fanartist); with `sourceUserId = null` the
  existing "recipient != actor" filter must keep using the moderator id.
- The payload (`actor_id/name/slug`) is stored in `content_data` and would leak
  the moderator even if the sender column is null: blank it too.
- Assumption likeliest to be reversed: the audit event `Fanart.DeletedByModeration`
  keeps `moderatorId` (admin-only log, not shown to the fanartist).
- Verified: `deleted_by_moderation` display string never shows the actor name.

## 1. Functional spec

### 1.1 Overview

When a moderator deletes a fanart, the notification sent to the story authors
and to the fanartist is a system notification: it no longer shows or stores the
moderator as sender, so nobody can tell which moderator acted.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Notification système | Notification with no source user (`source_user_id` null); no sender is displayed. |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` | nothing | nothing |
| `user-confirmed` (fanartist, story author) | the notification, without any sender | — |
| Moderator / Admin | unchanged | delete a fanart via the moderation route (unchanged) |

### 1.4 Functional requirements

1. A moderator deletes a fanart (`fanart.moderation.destroy`).
2. The story authors and the fanartist each receive `fanart.deleted_by_moderation`
   with no sender (no avatar / name in the notification list) and a payload
   that carries no moderator identity.
3. Text stays « Le fanart « … » a été supprimé par la modération sur <histoire> ».
4. The moderator is never a recipient, even if also an author or the fanartist.

### 1.5 Lifecycle

Notifications already sent keep their old sender: no data migration (assumption).
A deactivated/deleted moderator is irrelevant now that nothing references them.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged. |
| Visibility / privacy | Moderator identity must not leak via sender column nor payload. |
| Settings | Unchanged (same type, same preference). |
| Notifications | Only `fanart.deleted_by_moderation` becomes system. |
| Domain events | `Fanart.DeletedByModeration` unchanged (keeps `moderatorId`, admin audit log only). |
| Statistics / Moderation / Media / Search | N/A. |
| Lifecycle / cascade | See 1.5. |
| i18n | No new strings. |
| Mobile / Accessibility | N/A — no UI change beyond missing sender. |

### 1.7 Out of scope

- Other fanart notifications (author-side accept/decline/delete keep their actor).
- Rewriting already-sent notifications.
- Other domains' moderation notifications.
- Hiding the moderator from the audit log.

### 1.8 Open questions

None blocking. Non-blocking: should the same hold for other moderator-triggered
notifications elsewhere (Story, Comment)? Not requested; left out.

## 2. Architecture

### 2.1 Domain placement

Fanart only. `FanartNotifier` (private service) is the sole sender; no other
domain changes. Notification's `createNotification(..., ?int $sourceUserId)`
already accepts null.

### 2.3 PHP architecture

- `FanartNotifier::send()` gains a separate sender: keep the `$actorId`
  parameter for recipient exclusion, add `bool $system = false`; when true,
  `createNotification(..., null)`.
- `fanartDeletedByModeration()` calls `send(..., $moderatorId, ..., system: true)`
  and builds the content without actor (`forFanart()` takes `?int $actorId`;
  null yields `actorId: 0`, empty name/slug, no profile lookup).
- Payload class and lang strings unchanged (display ignores actor).

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Integration in `DeleteFanartTest` / `FanartNotificationsTest` (existing helpers):
source user null, payload without moderator, moderator-as-author excluded.

### 2.7 Tradeoffs locked

| # | Question | Chosen | Why |
|---|----------|--------|-----|
| 1 | Anonymise via null source + blank payload vs. new notification type | Null source + blank payload | Same type keeps settings/translations; minimal. |

### 2.8 Risks acknowledged

Old notifications keep their moderator sender; revisit if the user wants a backfill.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Anonymous moderation notification | S | — | TODO |

### Phase 1 — Anonymous moderation notification

**Goal.** `fanart.deleted_by_moderation` is created with null source user and no
moderator in the payload. **Reads.** §2.3.

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartNotifier.php` (`send`, `forFanart`, `fanartDeletedByModeration`).
- Tests in `app/Domains/Fanart/Tests/Feature/DeleteFanartTest.php` (extend the moderator test: `source_user_id` null, `content_data` has no moderator id/name/slug) and `FanartNotificationsTest.php` (moderator who is also an author is not notified).

**Tests.** Failing first: `it('sends the moderation deletion as a system notification')`,
`it('does not notify a moderator who is also a story author')`.

**Acceptance.**
- ✅ Latest `fanart.deleted_by_moderation` row has `source_user_id` null and no actor data.
- ✅ Recipients unchanged (authors + fanartist, minus moderator).
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Notification list (fanartist) | Moderation notification shows no sender | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | "System notification" = `source_user_id` null and no moderator in the payload | REFINE | Yes |
| 2 | Only `fanart.deleted_by_moderation` changes (it is the only moderator-driven fanart notification) | REFINE | Yes |
| 3 | Already-sent notifications are not rewritten | REFINE | Yes |
| 4 | Audit event keeps `moderatorId` | REFINE | Yes |

## 5. Build & verify log

_Not written yet._
