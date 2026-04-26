# Notification Preferences - Feature Planning

## Overview

This feature lets users manage which notifications they receive, on a per-type basis, across multiple delivery channels. The initial channels are **Website** (built-in) and **Discord** (feature-flagged). Future channels (email, push, etc.) slot in without touching the Notification domain.

It introduces three coordinated changes:

1. The **Settings domain** gains a *custom-view tab* capability — a tab that delegates its content rendering to a Blade view provided by another domain.
2. The **Notification domain** gains groups, per-type metadata, and a **channel registry** — an extensible list of delivery channels that other domains register themselves into.
3. A dedicated **Notification Preferences page** (registered as a custom Settings tab) exposes per-type toggles, one column per active channel.

---

## Functional Summary

### Notification Groups

Each notification type is assigned to a group at registration time. Groups provide visual organisation on the preferences page. Each group is registered with an ID, a sort order, and a translation key.

Expected groups for existing types (final labels TBD at implementation):

| Group | Types |
|-------|-------|
| **Commentaires** | comment.root, comment.reply |
| **Histoires** | follow.new_story |
| **Communauté** | follow.new_follower, news.posted, jardino.flowers |
| **Promotions & modération** | user_promotion (forcedOnWebsite) |

### Notification Channels

A **channel** is a delivery mechanism that other domains register with the Notification domain at boot time. The Notification domain owns the `website` channel natively. Any other domain can register additional channels by providing:

- A unique ID, display name key, and column sort order
- A default enabled/disabled state
- An optional feature flag (column hidden when flag is off)
- A **delivery callback** `fn(Notification $notification, array $userIds): void` — called at notification creation time when at least one user is concerned by that channel

Preferences (per user, per type, per channel) are stored in a single generic `notification_preferences` table owned by the Notification domain.

### Preferences Page

Accessible via the **Notifications tab** in the Settings page. Renders a custom Blade view rather than the standard parameter-row layout.

**Layout:**

- Informational message at the top: *"Ces préférences s'appliquent uniquement aux futures notifications."*
- One column per active channel, with **"Tout activer" / "Tout désactiver"** buttons at the top of each column.
- Notification types listed by group, one row per type.
- Each group header carries **"Activer / Désactiver"** buttons, one set per column.
- Types registered with `forcedOnWebsite: true` show a greyed-out, always-checked toggle in the Website column only. Other channel toggles remain interactive.
- Channels whose feature flag is off are **completely hidden** (column absent, no mention).
- Changes apply immediately via AJAX — no Save button required.

**Layout sketch (with Discord channel active):**

```
[i] Ces préférences s'appliquent uniquement aux futures notifications.

                                Website        Discord
  [Tout activer / désactiver]  [ON] [OFF]      [ON] [OFF]

  ── Commentaires ──────────────────────────────────────────
  [ON] [OFF] (groupe)           Website         Discord
  Commentaire sur votre histoire   ✓               ☐
  Réponse à votre commentaire      ✓               ☐

  ── Communauté ────────────────────────────────────────────
  [ON] [OFF] (groupe)
  Nouveau suiveur                  ✓               ☐
  Annonce du site                  ✓               ☐
  Fleurs JardiNo                   ✓               ☐

  ── Promotions & modération ───────────────────────────────
  [-- désactivé --] (groupe website forcé)
  Promotion de compte           ✓ (grisé)          ☐
```

### Opt-Out Behaviour

Preferences apply to **future notifications only** — existing `notification_reads` rows are never deleted retroactively. The informational message on the page makes this explicit.

Filtering happens **at write time**: when `createNotification()` or `createBroadcastNotification()` is called, the Notification domain computes which users are concerned for each channel before dispatching.

### Mandatory Notifications

Types registered with `forcedOnWebsite: true` always reach the user on the website channel regardless of preferences. The website toggle is rendered as disabled in the UI. All other channel toggles remain available for these types.

---

## Technical Architecture

### 1. Settings Domain: Custom-View Tab Capability

#### `SettingsTabDefinition` Extension

```php
final class SettingsTabDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly int $order,
        public readonly string $nameKey,
        public readonly ?string $icon = null,
        public readonly ?string $customViewPath = null, // e.g. 'notification::settings.preferences'
    ) {}
}
```

#### `SettingsController::tab()` Update

```php
public function tab(string $tabId): View
{
    $tab = $this->api->getTab($tabId);
    // ... existing auth/visibility checks ...

    if ($tab->customViewPath !== null) {
        return view('settings::pages.custom-tab', [
            'tabs'       => $this->api->getAllTabs(),
            'activeTab'  => $tab,
            'customView' => $tab->customViewPath,
        ]);
    }

    // existing standard rendering
}
```

#### New `custom-tab.blade.php` Partial

Renders the standard Settings sidebar/tab navigation. Content area is replaced with:

```blade
@include($customView)
```

The included view injects its own services via `@inject`. The Settings domain passes nothing extra — it remains fully decoupled from the notification data model.

---

### 2. Notification Domain: Channel Registry

#### `NotificationChannelDefinition`

```php
// Notification/Public/Contracts/NotificationChannelDefinition.php
final class NotificationChannelDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $nameTranslationKey,
        public readonly bool $defaultEnabled,
        public readonly int $sortOrder,
        public readonly Closure $deliveryCallback, // fn(Notification $notification, array $userIds): void
        public readonly ?string $featureFlag = null,
    ) {}
}
```

#### `NotificationChannelRegistry`

```php
// Notification/Public/Services/NotificationChannelRegistry.php

class NotificationChannelRegistry
{
    /** @var array<string, NotificationChannelDefinition> */
    private array $channels = [];

    /**
     * Register an external delivery channel.
     * Called from other domains' ServiceProvider::boot().
     *
     * @throws \InvalidArgumentException if channel ID already registered
     */
    public function register(NotificationChannelDefinition $channel): void
    {
        if (isset($this->channels[$channel->id])) {
            throw new \InvalidArgumentException("Channel '{$channel->id}' already registered.");
        }
        $this->channels[$channel->id] = $channel;
    }

    public function get(string $id): ?NotificationChannelDefinition
    {
        return $this->channels[$id] ?? null;
    }

    /**
     * Returns channels whose feature flag is active (or have no flag), sorted by sortOrder.
     *
     * @return array<NotificationChannelDefinition>
     */
    public function getActiveChannels(): array
    {
        return collect($this->channels)
            ->filter(fn($c) => $c->featureFlag === null || config($c->featureFlag, false))
            ->sortBy('sortOrder')
            ->values()
            ->all();
    }

    /** @return array<NotificationChannelDefinition> all registered, sorted */
    public function getAllChannels(): array
    {
        return collect($this->channels)->sortBy('sortOrder')->values()->all();
    }
}
```

The `website` channel is **not** registered through this registry — it is handled natively by the Notification domain's dispatch logic. The registry is for external channels only.

---

### 3. Notification Domain: Groups and Type Metadata

#### New Value Objects

```php
// Notification/Public/Contracts/NotificationGroupDefinition.php
final class NotificationGroupDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly int $sortOrder,
        public readonly string $translationKey,
    ) {}
}

// Notification/Public/Contracts/NotificationTypeDefinition.php
final class NotificationTypeDefinition
{
    public function __construct(
        public readonly string $type,
        /** @var class-string<NotificationContent> */
        public readonly string $class,
        public readonly string $groupId,
        public readonly string $nameKey,        // Short label for the preferences UI
        public readonly bool $forcedOnWebsite = false,
    ) {}
}
```

#### `NotificationFactory` Changes

```php
public function registerGroup(string $id, int $sortOrder, string $translationKey): void;

/**
 * @param class-string<NotificationContent> $class
 * @throws \InvalidArgumentException if groupId not registered
 */
public function register(
    string $type,
    string $class,
    string $groupId,
    string $nameKey,
    bool $forcedOnWebsite = false,
): void;

/** @return array<NotificationGroupDefinition> sorted by sortOrder */
public function getGroups(): array;

/** @return array<NotificationTypeDefinition> for the given group */
public function getTypesForGroup(string $groupId): array;

public function getTypeDefinition(string $type): ?NotificationTypeDefinition;

// Existing: resolve(), make(), getRegisteredTypes()
```

Groups must be registered before types (enforced with an exception). All existing `register()` calls must be updated to add `groupId`, `nameKey`, and `forcedOnWebsite`.

---

### 4. Notification Preferences Storage

#### Database Schema

```php
Schema::create('notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->string('type', 100);
    $table->string('channel', 50);
    $table->boolean('enabled');
    $table->timestamps();

    $table->unique(['user_id', 'type', 'channel']);
    $table->index('user_id');
    $table->index(['type', 'channel', 'enabled']); // Bulk lookup at dispatch time
});
```

**Storage rule**: Only non-default values are persisted. If a user sets a preference that matches the channel's `defaultEnabled`, the row is deleted. This keeps the table sparse and makes future default changes safe.

#### `NotificationPreferencesRepository`

```php
/**
 * Returns the subset of $userIds who should receive notifications on $channel.
 * For default-ON channels (website): removes opted-out users.
 * For default-OFF channels (discord): keeps only opted-in users.
 */
public function filterForChannel(array $userIds, string $type, string $channel, bool $defaultEnabled): array;

/**
 * Returns ALL user_ids who have explicitly opted in for this type+channel.
 * Used for broadcast on default-OFF channels — avoids a massive IN clause.
 */
public function getOptedInUserIds(string $type, string $channel): array;

/** Returns all stored prefs for a user, keyed by "type.channel" */
public function getForUser(int $userId): Collection;

public function setPreference(int $userId, string $type, string $channel, bool $enabled): void;
public function deletePreference(int $userId, string $type, string $channel): void;

/** Bulk upsert/delete for global and group toggles */
public function setForUserAndChannel(int $userId, string $channel, bool $enabled, array $types): void;
```

#### `NotificationPreferencesService`

```php
public function getPreferencesForUser(int $userId): array;
// Returns: [type => [channelId => ['enabled' => bool, 'isDefault' => bool, 'forced' => bool]]]

public function set(int $userId, string $type, string $channel, bool $enabled): void;
public function setAll(int $userId, string $channel, bool $enabled): void;
public function setGroup(int $userId, string $groupId, string $channel, bool $enabled): void;
```

Responsibilities:
- Resolves which types belong to a group (from `NotificationFactory`).
- Enforces the `forcedOnWebsite` constraint — rejects website-channel updates for forced types.
- Applies the sparse-storage rule (compares to channel default before insert/delete).

---

### 5. Filtering at Write Time

#### `NotificationPublicApi::createNotification()`

```php
public function createNotification(array $userIds, string $type, ...): void
{
    $definition  = $this->factory->getTypeDefinition($type);
    $notification = $this->service->createNotificationRecord($type, ...);

    // Website channel (built-in)
    $websiteUserIds = ($definition?->forcedOnWebsite)
        ? $userIds
        : $this->prefsRepository->filterForChannel($userIds, $type, 'website', defaultEnabled: true);

    if (!empty($websiteUserIds)) {
        $this->service->createReads($notification->id, $websiteUserIds);
    }

    // Registered external channels
    foreach ($this->channelRegistry->getActiveChannels() as $channel) {
        $channelUserIds = $this->prefsRepository->filterForChannel(
            $userIds, $type, $channel->id, $channel->defaultEnabled
        );
        if (!empty($channelUserIds)) {
            ($channel->deliveryCallback)($notification, $channelUserIds);
        }
    }
}
```

#### `NotificationPublicApi::createBroadcastNotification()`

```php
public function createBroadcastNotification(string $type, ...): void
{
    $allUserIds   = $this->userRepository->getActiveUserIds(); // roles: user, user-confirmed
    $definition   = $this->factory->getTypeDefinition($type);
    $notification = $this->service->createNotificationRecord($type, ...);

    // Website channel
    $websiteUserIds = ($definition?->forcedOnWebsite)
        ? $allUserIds
        : $this->prefsRepository->filterForChannel($allUserIds, $type, 'website', defaultEnabled: true);

    if (!empty($websiteUserIds)) {
        $this->service->createReads($notification->id, $websiteUserIds);
    }

    // Registered external channels
    foreach ($this->channelRegistry->getActiveChannels() as $channel) {
        if (!$channel->defaultEnabled) {
            // Opt-in channel: query opted-in users directly (no massive IN clause)
            $channelUserIds = $this->prefsRepository->getOptedInUserIds($type, $channel->id);
        } else {
            $channelUserIds = $this->prefsRepository->filterForChannel(
                $allUserIds, $type, $channel->id, $channel->defaultEnabled
            );
        }
        if (!empty($channelUserIds)) {
            ($channel->deliveryCallback)($notification, $channelUserIds);
        }
    }
}
```

---

### 6. Preferences Page

#### Tab Registration (in `NotificationServiceProvider::boot()`)

```php
$settingsApi->registerTab(new SettingsTabDefinition(
    id: 'notification',
    order: 30,
    nameKey: 'notification::settings.tab_name',
    icon: 'notifications',
    customViewPath: 'notification::settings.preferences',
));
```

#### Routes

```php
Route::middleware(['auth'])->prefix('notifications/preferences')->name('notification.preferences.')->group(function () {
    Route::put('/{type}', [NotificationPreferencesController::class, 'update'])     ->name('update'); // Single toggle
    Route::put('/',       [NotificationPreferencesController::class, 'bulkUpdate']) ->name('bulk');   // Group / global
});
```

#### `NotificationPreferencesController`

```php
public function update(string $type, Request $request): JsonResponse
// Validates: channel (must be registered + active), enabled (bool)
// Guards: rejects website channel on forcedOnWebsite types (403)
// Guards: rejects channel whose featureFlag is off (403)

public function bulkUpdate(Request $request): JsonResponse
// Validates: channel (same guards), enabled (bool), scope ('all' | groupId)
// Delegates to PreferencesService::setAll() or ::setGroup()
```

#### Blade View (`notification::settings.preferences`)

```blade
@inject('factory',          \App\Domains\Notification\Public\Services\NotificationFactory::class)
@inject('channelRegistry',  \App\Domains\Notification\Public\Services\NotificationChannelRegistry::class)
@inject('prefsService',     \App\Domains\Notification\Private\Services\NotificationPreferencesService::class)

@php
    $groups      = $factory->getGroups();
    $channels    = $channelRegistry->getActiveChannels();   // External channels only (no 'website')
    $preferences = $prefsService->getPreferencesForUser(auth()->id());
@endphp
```

The view renders the website column first (always), then iterates `$channels` for additional columns. All AJAX interactions hit `notification.preferences.update` / `notification.preferences.bulk`.

---

### 7. Updated Domain Structure

**New files in `Notification/`:**

```
Notification/
├── Public/
│   ├── Contracts/
│   │   ├── NotificationChannelDefinition.php  (NEW)
│   │   ├── NotificationGroupDefinition.php    (NEW)
│   │   └── NotificationTypeDefinition.php     (NEW)
│   └── Services/
│       └── NotificationChannelRegistry.php    (NEW)
├── Private/
│   ├── Controllers/
│   │   └── NotificationPreferencesController.php  (NEW)
│   ├── Models/
│   │   └── NotificationPreference.php             (NEW)
│   ├── Repositories/
│   │   └── NotificationPreferencesRepository.php  (NEW)
│   ├── Services/
│   │   └── NotificationPreferencesService.php     (NEW)
│   └── Resources/views/settings/
│       └── preferences.blade.php                  (NEW)
└── Database/Migrations/
    └── xxxx_create_notification_preferences_table.php (NEW)
```

**Modified files in `Notification/`:**
- `Public/Services/NotificationFactory.php` — `registerGroup()`, updated `register()`
- `Public/Api/NotificationPublicApi.php` — channel-aware dispatch in both create methods

**Modified / new files in `Settings/`:**
- `Public/Contracts/SettingsTabDefinition.php` — add `customViewPath`
- `Private/Controllers/SettingsController.php` — detect and delegate custom tabs
- `Private/Resources/views/pages/custom-tab.blade.php` — NEW layout partial

---

## Architectural Decisions

**Channel registry with delivery callbacks**
The Notification domain manages an abstract registry of channels. External domains (Discord, future email, etc.) register themselves at boot with a `Closure` callback. At dispatch time, Notification calls the callback with the targeted users for that channel. No Notification → Discord dependency is ever created. The boot order is deterministic: `NotificationServiceProvider` is first in `bootstrap/providers.php`, so the registry is always ready when other domains call `registerChannel()`.

**Generic `notification_preferences(user_id, type, channel, enabled)` table**
A single table with a `channel` string column replaces the old `website_enabled`/`discord_enabled` fixed columns. Adding a new channel requires no migration, no new column. The sparse-storage rule (only non-defaults persisted) keeps the table lean.

**`getOptedInUserIds()` for default-OFF channels at broadcast time**
For channels with `defaultEnabled: false` (e.g., Discord), broadcast fan-out queries only opted-in users directly (`WHERE channel='discord' AND enabled=1 AND type=?`) rather than filtering the full user list. This avoids a massive IN clause and keeps broadcast dispatch efficient.

**`website` channel is native, not registered**
The `website` channel is handled directly in the dispatch methods (creating `notification_reads` rows). It is intentionally not in the channel registry, which is reserved for externally-delivered channels. This prevents accidental re-registration and keeps the website flow unambiguous.

**`forcedOnWebsite` applies to the website channel only**
The flag means "this type cannot be opted out of on the website." It has no bearing on other channels — those remain fully user-controlled even for forced types. This is enforced in the preferences controller and in the dispatch logic.

**Custom-view tab vs. standalone page**
The Settings system gains a `customViewPath` on `SettingsTabDefinition`. The notification preferences page uses it to appear visually integrated into the Settings navigation without forcing the generic parameter-row rendering model. The Settings domain remains decoupled — it only holds a view name.

---

## Implementation Steps

### Phase 0: Settings Infrastructure
1. Add `customViewPath` to `SettingsTabDefinition`
2. Update `SettingsController::tab()` to detect and render custom tab view
3. Create `Settings/Resources/views/pages/custom-tab.blade.php`

### Phase 1: Notification Channel Registry
4. Create `NotificationChannelDefinition`
5. Create `NotificationChannelRegistry` (register, get, getActiveChannels, getAllChannels)
6. Bind `NotificationChannelRegistry` as singleton in `NotificationServiceProvider`

### Phase 2: Notification Factory Enrichment
7. Create `NotificationGroupDefinition` and `NotificationTypeDefinition`
8. Update `NotificationFactory` — add `registerGroup()`, update `register()` signature
9. Update all existing `register()` calls across domains (add `groupId`, `nameKey`, `forcedOnWebsite`)

### Phase 3: Preferences Storage
10. Create migration `notification_preferences` + `NotificationPreference` model
11. Implement `NotificationPreferencesRepository`
12. Implement `NotificationPreferencesService`

### Phase 4: Filtering at Write Time
13. Update `NotificationPublicApi::createNotification()` with channel-aware dispatch
14. Update `NotificationPublicApi::createBroadcastNotification()` with channel-aware dispatch

### Phase 5: Preferences Page
15. Register the Notification Settings tab with `customViewPath` in `NotificationServiceProvider`
16. Implement `NotificationPreferencesController` (`update`, `bulkUpdate`)
17. Register routes
18. Create `preferences.blade.php` with dynamic channel columns and Alpine.js AJAX toggles

### Phase 6: Testing
19. Settings: custom-view tab renders; standard tabs unaffected
20. Channel registry: register, duplicate ID error, feature flag filtering
21. Factory: group/type registration, unknown-group error, `getTypesForGroup()`
22. Preferences: get/set/bulk across channels; `forcedOnWebsite` enforcement; inactive channel guard
23. Dispatch: `createNotification` calls callbacks for active channels; `createBroadcastNotification` uses `getOptedInUserIds` for default-OFF channels; `forcedOnWebsite` bypasses website filtering only
24. Preferences page: correct column count, toggles update via AJAX, group/global buttons work, inactive channels hidden
