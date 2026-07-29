# Discord — warn when notifications are on without a linked account

**Status:** Done — 2026-07-29

## What it does

Shows a warning below the Discord column header on the notification preferences
page when the authenticated user has no linked Discord account. The warning is
purely informational — toggles still work.

## How it works

1. **`DiscordPublicApi::isLinked(int $userId): bool`** — new public API for the
   Discord domain, delegates to the existing `DiscordAuthService`.
2. **`NotificationChannelDefinition::$warningForUser`** — new optional
   `?Closure` (`fn(int $userId): ?string`) on the channel definition value
   object. Any channel can provide a user-contextual warning.
3. **Discord's channel registration** provides the callback: if the user is not
   linked, it returns the translated warning `discord::notifications.not_linked_warning`.
4. **Notification preferences view** renders `$channel->warningForUser`
   generically under each channel header.

## Files changed

| File | Change |
|------|--------|
| `app/Domains/Discord/Public/Api/DiscordPublicApi.php` | New — `isLinked()` |
| `app/Domains/Discord/Public/Providers/DiscordServiceProvider.php` | Added `warningForUser` callback |
| `app/Domains/Discord/Private/Resources/lang/fr/notifications.php` | Added `not_linked_warning` |
| `app/Domains/Notification/Public/Contracts/NotificationChannelDefinition.php` | Added `$warningForUser` param |
| `app/Domains/Notification/Private/Resources/views/settings/settings.blade.php` | Renders channel warnings |
| `app/Domains/Discord/Tests/Feature/DiscordLinkHintTest.php` | New — 2 tests |
| `app/Domains/Discord/README.md` | Documented public API |
| `app/Domains/Discord/AGENTS.md` | Documented public API |

## Assumptions (made in auto mode)

1. Generic `warningForUser` callback on the channel definition rather than a
   Discord-specific check in the Notification view — keeps domains decoupled.
2. Warning is informational only — toggles still work when unlinked.
