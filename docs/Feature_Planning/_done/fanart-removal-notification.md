# Notify fanartist when their role is revoked

**Status:** DONE — 2026-11-01 · **Domain(s):** `Fanart`

## What it does

When a story author revokes a user's fanartist role, the ex-fanartist gets a
`fanart.role_revoked` notification (website, and Discord via their generic
channel preference). Text (fr): « Votre rôle de fanartiste sur <story> vous a été
retiré », story name links to the story. Preference label: « Mon rôle de
fanartiste sur une histoire m'a été retiré » (group Fanarts).

## Key behaviour

- Sender = revoking author; payload like other `fanart.*` types (`actor_*`, story title/slug).
- Sent only if a role row was actually deleted (double click / stale page: nothing).
- Revoker is never a recipient.
- The ex-fanartist's existing fanarts are untouched.
- No notification when roles vanish through user or story deletion.
- Plan and code agree; no deviation. Request wording ("fanart removal") was
  already covered by five notification paths; role revocation was the real gap.

## Where the code lives

| Concern | Path |
|---------|------|
| Content class | `app/Domains/Fanart/Public/Notifications/FanartRoleRevokedNotification.php` |
| Registration | `app/Domains/Fanart/Public/Providers/FanartServiceProvider.php` (type map, `'role_revoked'`) |
| Sender | `app/Domains/Fanart/Private/Services/FanartNotifier.php` (`fanartRoleRevoked`) |
| Trigger | `app/Domains/Fanart/Private/Services/FanartistService.php` (`revoke($storyId, $userId, $actorId)`), `FanartistController::destroy` passes `Auth::id()` |
| Lang | `app/Domains/Fanart/Private/Resources/lang/fr/notification.php` (`settings.type_role_revoked`, `role_revoked.display`) |
| Tests | `Tests/Feature/FanartistManagementTest.php`, `FanartNotificationsTest.php` |
| Catalog | `docs/notification-types.md` (regenerated with `notifications:export-types-doc`) |

## Extension points used

Notification type registry (same loop as `role_given` / `role_declined`).

## Decisions / counter-intuitive

- `$actorId` added to `revoke()` and notification done in the service, matching `grant()`.
- Test `does not notify when nobody held the role` guards the `$deleted > 0` condition, not the call (asserts an absence).
- Type count is now eleven (Fanart README, AGENTS.md, `FanartNotificationContent` docblock).

## Not done

- Non-goals: removing fanarts on revocation; notifying on role loss via user/story deletion; rewriting past notifications.
- Open: none. Assumed French wording (reversible).
- E2E: none written; nothing to retire. Visual QA passed manually.
