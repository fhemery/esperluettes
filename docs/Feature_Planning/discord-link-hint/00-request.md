# Discord — warn when notifications are enabled without a linked account — request

Migrated from `Discord_Notifications.md` Phase 5, the only phase of that plan
never delivered. The rest of the feature is live — see
[`../discord-notifications/README.md`](../discord-notifications/README.md).

## What I want

On the notification preferences page, warn the user if they have opted in to
Discord delivery but have no Discord account linked.

## Why

Opting in to the Discord channel without a linked account does nothing at all:
the queue skips users with no `discord_id`, silently. The user believes they
have enabled something and receives nothing, with no feedback anywhere.

## Constraints or ideas I already have

- The original plan called for exactly two things:
  1. expose `DiscordPublicApi::isLinked(int $userId): bool` — the Discord
     domain currently has **no public API at all**, only events and a provider;
  2. show a warning in the Discord column header of the notification
     preferences view when the current user is not linked.
- The Discord channel is behind a feature toggle (`DiscordFeatureToggles`) — the
  warning must not appear when the channel is off.

## Explicitly out of scope

- Changing the queue behaviour. Silently skipping unlinked users is correct;
  this is about telling the user, not about queueing for them.
