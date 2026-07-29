# Discord — warn when notifications are on without a linked account — architecture

## Changes

### 1. Discord domain — new public API

Create `app/Domains/Discord/Public/Api/DiscordPublicApi.php` with a single
method:

```php
public function isLinked(int $userId): bool
```

Delegates to `DiscordAuthService::getDiscordByUserId()` (already exists).

### 2. Notification preferences view — warning

In `app/Domains/Notification/Private/Resources/views/settings/settings.blade.php`,
below the Discord channel `<th>`, inject a small warning text when the user is
not linked. The view already iterates `$channels`; identify the Discord channel
by `$channel->id === 'discord'` and call `DiscordPublicApi::isLinked()`.

The Notification domain already depends on no specific channel — injecting a
Discord-aware check would create a coupling. Instead, extend
`NotificationChannelDefinition` with an optional `warningCallback` that the
channel can provide at registration time. The view calls it generically.

### 3. Channel definition — optional warning

Add to `NotificationChannelDefinition`:

```php
public readonly ?Closure $warningCallback = null,
// fn(int $userId): ?string — returns a warning message or null
```

Discord's registration in `DiscordServiceProvider` provides the callback,
calling `DiscordPublicApi::isLinked()` and returning a translated warning when
false.

### 4. Deptrac

No new cross-domain dependency: Discord already depends on Notification
(channel registration). Notification gains no dependency on Discord — the
warning flows through the generic callback. No deptrac change needed.

## Files touched

| File | Change |
|------|--------|
| `app/Domains/Discord/Public/Api/DiscordPublicApi.php` | New |
| `app/Domains/Discord/Public/Providers/DiscordServiceProvider.php` | Add warningCallback |
| `app/Domains/Notification/Public/Contracts/NotificationChannelDefinition.php` | Add warningCallback param |
| `app/Domains/Notification/Private/Resources/views/settings/settings.blade.php` | Render warning |
| Discord AGENTS.md / README.md | Document public API |
| Translation files | Warning text |
