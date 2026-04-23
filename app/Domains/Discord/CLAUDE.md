# Discord Domain — Agent Instructions

- README: [app/Domains/Discord/README.md](README.md)

## Public API

This domain has no `Public/Api/` class exposing a programmatic API to other domains. Cross-domain access is achieved through the two public events below.

## Events emitted

- `DiscordConnected` (`Discord.Connected`, auditable) — emitted when a Discord identity is linked to a website user for the first time. Carries `userId` and `discordId`. Not re-emitted if the same pair reconnects.
- `DiscordDisconnected` (`Discord.Disconnected`, auditable) — emitted when a Discord identity is unlinked (bot-initiated `DELETE` or user deletion cleanup). Carries `userId` and `discordId`.

## Listens to

- `Auth::UserDeleted` → `RemoveDiscordAssociationsOnUserDeleted` — hard-deletes all rows in `discord_users` for that user. Does not emit `DiscordDisconnected` (deletion is a hard cleanup, not a user-initiated unlink).

## Non-obvious invariants

**Code generation is destructive.** `generateConnectionCodeForUser()` deletes all expired codes for all users AND all unused pending codes for the requesting user inside the same transaction before inserting the new code. A user can never have two live codes simultaneously.

**`linkDiscordUser` is idempotent for existing pairs but not for partial overlaps.** Same user + same Discord ID = success, no event. Same user + different Discord ID = `user_already_linked` (409). Different user + same Discord ID = `discord_id_taken` (409). The controller surfaces these as HTTP 409 responses.

**`DISCORD_BOT_API_KEY` must be set in production.** The `DiscordApiAuth` middleware denies every request when the env var is empty or missing. There is no fallback.

**`DISCORD_RESTRICTED_ACCESS_USER_IDS` gates the UI component.** When this env var is non-empty, `DiscordComponent` renders as an empty string for any user not in the allowlist. The component still compiles — it simply outputs nothing. This is a dev/staging preview gate, not a production permission system.

**No FK constraint to `users`.** `discord_users.user_id` has no database-level foreign key to `users`. Referential integrity depends on the `Auth::UserDeleted` listener firing correctly. Do not add a FK — it would violate the cross-domain constraint rule.

**User deletion does not emit `DiscordDisconnected`.** `deleteUserId()` is called directly by the listener without going through `unlinkDiscordUserByDiscordId()`, so the event is intentionally skipped during account deletion cleanup.

## Environment variables

- `DISCORD_BOT_API_KEY` — shared secret for bot API authentication (required in production)
- `DISCORD_RESTRICTED_ACCESS_USER_IDS` — comma-separated user IDs; when set, restricts the UI component to those users only
