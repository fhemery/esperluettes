# Discord notifications

**Status:** DONE except the preferences hint — 2026-07-27 · **Domain(s):**
`Discord`, with an extension point in `Notification`

Read this before touching the bot API or the notification channel registry. The
bot-facing contract is documented separately in
[`docs/Discord_Api_Usage.md`](../../Discord_Api_Usage.md) — that file is user
documentation for bot developers and is maintained by hand.

## What it does

Site notifications can be delivered to Discord as DMs. Discord registers itself
as a notification *channel*; when a notification is dispatched, Notification
calls the channel's delivery callback, which queues one row per notification
plus one row per recipient. A bot polls, sends the DMs and reports back.

## Key behaviour

- **The dependency runs one way only: Discord → Notification, never the
  reverse.** Notification has no knowledge of Discord; it calls a registered
  callback. Keep it that way.
- **Two tables, not one per recipient.** `discord_pending_notifications` holds
  the notification reference, `discord_pending_recipients` the per-recipient
  delivery state — so the API response does not duplicate content per recipient.
- **Only the notification id is stored**, never a copy of its content.
- **Users with no linked Discord account are silently skipped** at queue time.
  If every recipient is skipped, no pending notification row is created at all.
- **The queue is pre-filtered.** The bot never applies preference logic: only
  users opted in to the Discord channel for that notification type are queued.
- **`data` is the stored payload, passed through verbatim.** Keys are
  type-specific; nothing is normalised, renamed or supplemented. **Renaming a
  field in a notification type is a breaking change for the bot.**
- **Partial delivery is supported** — `markSent` with `failedRecipients` leaves
  those recipients pending for the next poll.
- **Disconnecting deletes that user's pending recipient rows immediately**;
  siblings are unaffected.
- **Deleting a `notifications` row cascades** to both Discord tables.
- The whole channel sits behind the `features.discord_notifications` flag.

## Where the code lives

| Concern | Path |
|---------|------|
| Channel registration | `Public/Providers/DiscordServiceProvider.php` |
| Queue | `Private/Services/DiscordNotificationQueueService.php` |
| Repository | `Private/Repositories/DiscordPendingNotificationRepository.php` |
| Bot API | `Private/Controllers/Api/{Notifications,Users}Controller.php`, `Private/api.routes.php` |
| API auth | `Private/Middleware/DiscordApiAuth.php` |
| Cleanup listeners | `Private/Listeners/{CleanDiscordNotificationsOnDisconnect,RemoveDiscordAssociationsOnUserDeleted}.php` |
| Feature flag | `Private/Support/DiscordFeatureToggles.php` |
| Tables | `discord_pending_notifications`, `discord_pending_recipients` |

## Decisions worth remembering

- **The `NotificationContent` interface was deliberately left unchanged.** The
  original plan called for adding `getUrl()`, `getActorName()` and
  `getTargetDescription()` to it and implementing them in every existing
  content class — that was **cancelled**. Passing the stored payload through
  verbatim removed all per-domain work. Do not reintroduce it.
- Preferences live entirely in Notification (`notification_preferences`,
  `channel = 'discord'`); Discord has no preferences controller.
- Because the channel default is `false`, only opted-in rows exist.

## Testing note carried forward

Tests of the bot payload must use a fixture **whose payload keys differ from its
rendered text** — otherwise a derived payload passes by coincidence and the
verbatim guarantee is not actually tested. See `Tests/Fixtures/`.

## Not done

- **The preferences UI hint.** A user who opts in without a linked account gets
  nothing and is told nothing. Needed `DiscordPublicApi::isLinked()` — the
  Discord domain had **no public API at all** — and a warning in the
  Discord column of the preferences page.
  → [`discord-link-hint`](./discord-link-hint.md), now done.

The pre-loop planning document is in git history:
`git show f1d50704:docs/Feature_Planning/Discord_Notifications.md`.
