# Discord — warn when notifications are on without a linked account — plan

Single phase — this is a small feature.

## Phase 1: Public API + warning callback + view

### Deliverables

1. `DiscordPublicApi` with `isLinked(int $userId): bool`
2. `NotificationChannelDefinition` gains optional `?Closure $warningCallback`
3. Discord's channel registration provides the warning callback
4. Notification preferences view renders warnings generically
5. French translation for the warning text
6. Integration test: notification preferences page shows warning for unlinked user, hides it for linked user

### Test plan

- Feature test: authenticated user with no `discord_users` row → preferences
  page contains the warning string.
- Feature test: authenticated user with a `discord_users` row → preferences
  page does not contain the warning string.

### Acceptance

- `npm run gate` green.

## Visual QA checklist

| # | Check | Role | Expected |
|---|-------|------|----------|
| 1 | Preferences page, unlinked user | user-confirmed | Warning visible under Discord column |
| 2 | Preferences page, linked user | user-confirmed | No warning |
| 3 | Discord feature OFF | user-confirmed | No Discord column at all |

---

Status: `[x] Phase 1`
