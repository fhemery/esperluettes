# Fanart moderation notification as system notification

**Status:** DONE — 2026-11-01 · **Domain(s):** `Fanart`

## What it does

When a moderator deletes a fanart (`fanart.moderation.destroy`), the
`fanart.deleted_by_moderation` notification to the story authors and the
fanartist is a system notification: `source_user_id` is null and the payload
carries no moderator identity. Text unchanged.

## Key behaviour

- Recipients: story authors + fanartist, minus the moderator (still excluded even
  if also an author or the fanartist).
- Payload: `actor_id` = 0, `actor_name` / `actor_slug` empty, no profile lookup.
- Audit event `Fanart.DeletedByModeration` still carries `moderatorId` (admin-only log).
- Already-sent notifications keep their old sender; no data migration.
- Other fanart notifications keep their actor.

## Where the code lives

| Concern | Path |
|---------|------|
| Sender / system flag | `app/Domains/Fanart/Private/Services/FanartNotifier.php` (`send(..., bool $system)`, `forFanart(?int $actorId)`) |
| Tests | `app/Domains/Fanart/Tests/Feature/DeleteFanartTest.php`, `FanartNotificationsTest.php` |
| Doc | `docs/notification-types.md` (row annotated) |

## Extension points used

`NotificationPublicApi::createNotification(..., ?int $sourceUserId)` with null.

## Decisions / counter-intuitive

- `$actorId` is kept in `send()` only for recipient exclusion; `$system` decides
  whether it is stored as sender.
- Plan and code agree; no deviation.

## Not done

- Non-goals: other fanart notifications, rewriting sent ones, hiding moderator from audit log.
- Open: same treatment for other moderator-triggered notifications (Story, Comment) — not requested.
- E2E: none written (DB assertions in PHP tests); nothing to retire.
- Visual QA passed manually.
