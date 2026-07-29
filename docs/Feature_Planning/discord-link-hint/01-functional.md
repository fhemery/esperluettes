# Discord — warn when notifications are on without a linked account — functional spec

## Problem

A user can opt in to Discord notification delivery without having a linked
Discord account. The queue silently skips unlinked users, so the user believes
they enabled something and receives nothing, with no feedback.

## Solution

Show a small warning below the Discord column header in the notification
preferences table when the authenticated user has no linked Discord account.
The warning is a short text like "Compte Discord non lié" with a link to the
Discord settings component.

## Behaviour

| Condition | Result |
|-----------|--------|
| Discord channel feature is OFF | Column not shown at all (existing behaviour) |
| Feature ON, user is linked | Column header shown normally, no warning |
| Feature ON, user is NOT linked | Column header + warning underneath |

## Scope

- Create `DiscordPublicApi::isLinked(int $userId): bool`.
- Show the warning in the notification preferences Blade view.
- No queue behaviour change. No new routes, tables, or events.

## Out of scope

- Preventing the user from toggling Discord preferences when unlinked (toggles
  still work — the warning is informational).
