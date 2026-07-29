# Discord Domain

## Purpose and scope

The Discord domain manages the integration between the website and the external Discord bot ([Hestia](https://github.com/DrOwlFR/Hestia)). It owns two concerns:

1. **User connection via code exchange** — authenticated website users generate a short-lived one-time code; the Discord bot exchanges that code to link a Discord identity to a website user account.
2. **Bot-facing REST API** — a set of JSON endpoints secured by a shared API key, allowing the bot to connect users, look up their roles, and remove Discord links.
3. **Notification delivery queue** — Discord registers itself as an external notification channel; notifications for opted-in, linked users are queued in `discord_pending_notifications` and drained by the bot via a polling endpoint.

Out of scope: Discord OAuth flows, and any Discord-specific permission enforcement beyond role look-up.

See [docs/Discord_Api_Usage.md](../../../docs/Discord_Api_Usage.md) for the bot-facing API contract. That document is written for bot developers and is maintained by hand — keep it in step with any change to these endpoints.

## Key concepts

### Code exchange flow

The connection flow is initiated on the website, not in Discord:

1. An authenticated user visits their profile/settings and triggers the "Connect Discord" action.
2. The web UI calls `POST /discord/connect/code` (auth-protected). The server generates an 8-character hex one-time code valid for **5 minutes**, stores it, and returns it.
3. Any previous unused codes for the same user are deleted before the new code is created (one live code per user at a time).
4. The user copies the code into the Discord bot (`/connect <code>`).
5. The bot calls `POST /api/discord/users` with the code, its own Discord user ID, and username. The server validates the code (not expired, not already used), marks it consumed, and creates or updates the `discord_users` mapping.

### Conflict handling during link

`linkDiscordUser` enforces two exclusivity rules:
- A website user can only be linked to one Discord identity. Attempting to link a second Discord ID to the same user returns `user_already_linked`.
- A Discord identity can only be linked to one website user. Attempting to link the same Discord ID to a second user returns `discord_id_taken`.

If the bot re-submits an already-established link (same user, same Discord ID) the call is idempotent and succeeds without re-emitting `DiscordConnected`.

### API authentication

All `/api/discord/*` routes are protected by the `discord.api` middleware alias (`DiscordApiAuth`). Authentication is a static shared secret: the bot sends `Authorization: Bearer <key>` and the server compares it with the `DISCORD_BOT_API_KEY` environment variable using `hash_equals` (timing-safe). If `DISCORD_BOT_API_KEY` is empty or unset, **all requests are denied**.

### UI component access gating

The `<x-discord::discord />` Blade component supports a restricted-preview mode: if the `DISCORD_RESTRICTED_ACCESS_USER_IDS` environment variable is set to a comma-separated list of user IDs, the component renders as empty for all other users. This allows staged rollout without feature-flag infrastructure.

## Architecture decisions

**No foreign key from `discord_users.user_id` to `users`** — per project architecture rules, cross-domain FK constraints are forbidden. The integrity relationship is maintained at the application level: `RemoveDiscordAssociationsOnUserDeleted` listens to `Auth::UserDeleted` and hard-deletes the mapping row.

**Codes are cleaned up eagerly on generation, not by a scheduled job** — when a user requests a new code, all expired codes across all users and all pending (unused) codes for the requesting user are deleted in the same transaction. This avoids needing a separate housekeeping job at the cost of slightly more work per generation request.

**`DiscordConnected` is only emitted on a genuinely new link** — a re-connect of an already-established pair does not re-emit the event, preventing duplicate audit log entries or downstream side-effects.

**The notification `data` payload is passed through verbatim** — `GET /api/discord/notifications/pending` returns each notification's stored `content_data` unchanged, rather than mapping it into Discord-specific fields. The domain stays type-agnostic: adding a notification type anywhere in the app requires no change here, and the bot can rebuild any presentation it needs from `type` + `data`. The trade-off is that payload keys become part of the bot API contract, so renaming a field in a notification type is a breaking change — `docs/notification-types.md` documents the per-type fields and is regenerated with `php artisan notifications:export-types-doc`.

An earlier design instead required every `NotificationContent` class to implement `getUrl()`/`getActorName()`/`getTargetDescription()`. Those methods were never added, yet the controller read `data['url']`, `data['actor']` and `data['target']`, so all three were permanently `null`. Do not reintroduce derived keys here.

## Public API

`DiscordPublicApi` exposes a single method to other domains:

- `isLinked(int $userId): bool` — returns whether the user has a linked Discord account. Used by the Notification domain to show a warning on the preferences page when a user has opted in to Discord delivery without linking.

## Cross-domain delegation

| What | Delegated to | Why |
|------|-------------|-----|
| Resolving user roles to return in API responses | `Auth::AuthPublicApi::getRolesByUserIds()` | Role ownership lives in Auth; Discord must not duplicate that logic |
| User deletion cleanup trigger | `Auth::UserDeleted` event (via EventBus) | Avoids coupling Auth to Discord; Discord listens and self-cleans |
| Event persistence and audit log | `Events::EventBus` | All domain events are routed through the shared event bus |
