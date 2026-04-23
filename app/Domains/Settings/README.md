# Settings Domain

The Settings domain provides a **generic, extensible system** for user preferences. Any domain can register its own tabs, sections, and parameters during boot. Users manage their preferences on a dedicated settings page at `/settings`. Only non-default values are persisted; defaults are defined in code and returned automatically when no override exists.

## Architecture

```
Settings/
  Database/
    Migrations/
      2024_12_28_172900_create_settings_table.php
  Private/
    Controllers/
      SettingsController.php          # index, tab (partial), update, reset actions
    Models/
      Setting.php                     # Eloquent model for the settings table
    Repositories/
      SettingRepository.php           # CRUD; upsert / deleteByUserDomainAndKey
    Resources/
      lang/fr/settings.php
      views/
        components/
          parameter-row.blade.php     # Renders a single parameter with its input
          section.blade.php           # Renders a section with its parameters
        pages/
          index.blade.php             # Full settings page layout with tab navigation
        partials/
          tab-content.blade.php       # AJAX-loadable tab content partial
    Services/
      SettingsRegistryService.php     # In-memory static registry of tabs/sections/parameters
    routes.php
  Public/
    Api/
      SettingsPublicApi.php           # Facade for other domains: register + read/write values
    Contracts/
      SettingsTabDefinition.php       # Value object for a tab
      SettingsSectionDefinition.php   # Value object for a section
      SettingsParameterDefinition.php # Value object for a parameter (includes validation and casting)
    Providers/
      SettingsServiceProvider.php
    Services/
      SettingsService.php             # Value read/write with two-level caching
  Tests/
    Feature/
      SettingsCachingTest.php
      SettingsPageTest.php
      SettingsRegistrationTest.php
      SettingsValueAccessTest.php
    helpers.php                       # clearSettingsRegistry(), registerTestSettingsStructure(), etc.
```

## Database

### `settings`

| Column    | Type         | Notes                                  |
|-----------|--------------|----------------------------------------|
| `id`      | bigint PK    |                                        |
| `user_id` | bigint       | No FK (cross-domain constraint banned) |
| `domain`  | varchar(50)  | Lowercase tab ID                       |
| `key`     | varchar(100) | Lowercase parameter key                |
| `value`   | text         | Serialized string                      |

Unique constraint on `(user_id, domain, key)`. Index on `user_id` for fast per-user cache loads.

## Public API

### `SettingsPublicApi`

The single entry point for all external interaction.

**Registration** (call from `ServiceProvider::boot()`, wrapped in `$this->app->booted()`):

```php
$api = app(SettingsPublicApi::class);

$api->registerTab(new SettingsTabDefinition(
    id: 'story',
    order: 20,
    nameKey: 'story::settings.tab_name',
    icon: 'menu_book',          // Optional Material Symbols icon
));

$api->registerSection(new SettingsSectionDefinition(
    tabId: 'story',
    id: 'reading',
    order: 10,
    nameKey: 'story::settings.sections.reading.name',
    descriptionKey: 'story::settings.sections.reading.description', // optional
));

$api->registerParameter(new SettingsParameterDefinition(
    tabId: 'story',
    sectionId: 'reading',
    key: 'font_size',
    type: ParameterType::RANGE,
    default: 16,
    order: 10,
    nameKey: 'story::settings.params.font_size.name',
    constraints: ['min' => 12, 'max' => 28, 'step' => 2],
    roles: [],                  // Empty = all authenticated users; or restrict to role slugs
));
```

Registration order matters: tab before section, section before parameter. Duplicate IDs throw `InvalidArgumentException`. Tab/section/key lookups are case-insensitive.

**Reading values:**

```php
// For a specific user (returns default if no override)
$value = $api->getValue($userId, 'story', 'font_size');

// For the currently authenticated user
$value = $api->getValueForCurrentUser('story', 'font_size');
```

**Writing values:**

```php
$api->setValue($userId, 'story', 'font_size', 20);
$api->setValueForCurrentUser('story', 'font_size', 20);

// Setting a value equal to the default removes the stored override
$api->resetToDefault($userId, 'story', 'font_size');
$api->resetToDefaultForCurrentUser('story', 'font_size');
```

## Contracts

### `SettingsTabDefinition`

| Property  | Type      | Required | Description                              |
|-----------|-----------|----------|------------------------------------------|
| `id`      | string    | Yes      | Unique tab identifier (e.g. `'story'`)   |
| `order`   | int       | Yes      | Display order (lower = first)            |
| `nameKey` | string    | Yes      | Full translation key for the tab label   |
| `icon`    | ?string   | No       | Material Symbols icon name               |

### `SettingsSectionDefinition`

| Property         | Type    | Required | Description                              |
|------------------|---------|----------|------------------------------------------|
| `tabId`          | string  | Yes      | Parent tab ID                            |
| `id`             | string  | Yes      | Unique section ID within the tab         |
| `order`          | int     | Yes      | Display order within the tab             |
| `nameKey`        | string  | Yes      | Translation key for the section heading  |
| `descriptionKey` | ?string | No       | Translation key for optional description |

### `SettingsParameterDefinition`

| Property         | Type          | Required | Description                                                         |
|------------------|---------------|----------|---------------------------------------------------------------------|
| `tabId`          | string        | Yes      | Parent tab ID                                                       |
| `sectionId`      | string        | Yes      | Parent section ID                                                   |
| `key`            | string        | Yes      | Unique key within the tab (used for storage)                        |
| `type`           | ParameterType | Yes      | Value type (see below)                                              |
| `default`        | mixed         | Yes      | Default value returned when no override exists                      |
| `order`          | int           | Yes      | Display order within the section                                    |
| `nameKey`        | string        | Yes      | Translation key for the parameter label                             |
| `descriptionKey` | ?string       | No       | Translation key for optional description                            |
| `constraints`    | array         | No       | Type-specific constraints (`min`, `max`, `step`, `options`, etc.)   |
| `roles`          | array         | No       | Role slugs that can see/edit this parameter; empty = all auth users |

`SettingsParameterDefinition` also provides `cast()`, `serialize()`, and `validate()` methods used internally.

### `ParameterType` (from `Shared` domain)

| Value          | Storage    | Constraints                          |
|----------------|------------|--------------------------------------|
| `INT`          | integer    | `min`, `max`                         |
| `RANGE`        | integer    | `min`, `max`, `step`                 |
| `BOOL`         | boolean    | —                                    |
| `STRING`       | string     | `min_length`, `max_length`, `pattern`|
| `TIME`         | integer    | `min`, `max`                         |
| `ENUM`         | string     | `options` (assoc: value => labelKey) |
| `MULTI_SELECT` | array      | `options` (assoc: value => labelKey) |

## Routes

All routes require `web` middleware and the `user` or `user-confirmed` role.

| Method | URI                        | Name              | Description                         |
|--------|----------------------------|-------------------|-------------------------------------|
| GET    | `/settings`                | `settings.index`  | Full settings page, first tab shown |
| GET    | `/settings/{tab}`          | `settings.tab`    | Tab content partial (AJAX)          |
| PUT    | `/settings/{tab}/{key}`    | `settings.update` | Update a parameter value            |
| DELETE | `/settings/{tab}/{key}`    | `settings.reset`  | Reset a parameter to its default    |

The `update` and `reset` endpoints return JSON (`{ "success": true }` or `{ "message": "..." }` with 4xx).

## Caching

- Per-user settings are cached forever under the key `user_settings:{user_id}` using Laravel's cache.
- A request-level in-memory cache (`$requestCache`) avoids repeated cache reads within a single request.
- Both caches are invalidated on every `setValue()` or `resetToDefault()` call.
- Reads for unauthenticated users return the parameter default without touching the cache.

## Role-Based Visibility

- A tab is visible only if it contains at least one parameter accessible to the current user's roles.
- A parameter with an empty `roles` array is visible to all authenticated users.
- A parameter with a non-empty `roles` array is visible only to users holding at least one of the listed role slugs.
- Attempts to `update` or `reset` a parameter the user cannot access return HTTP 403.

## Registered Tabs (as of 2026-04-22)

| Tab       | Domain    | Order | Icon       | Parameters                                                        |
|-----------|-----------|-------|------------|-------------------------------------------------------------------|
| `general` | Shared    | 10    | `settings` | `theme` (ENUM), `font` (ENUM), `interline` (ENUM)                |
| `readlist`| ReadList  | 20    | `book`     | `hide-up-to-date` (BOOL)                                          |
| `profile` | Profile   | 30    | `face`     | `hide-comments-section` (BOOL)                                    |

## Testing Helpers (`Tests/helpers.php`)

| Helper                               | Purpose                                                      |
|--------------------------------------|--------------------------------------------------------------|
| `clearSettingsRegistry()`            | Wipes the static registry (call in `beforeEach`/`afterEach`) |
| `registerSettingsTab($tab)`          | Register a tab via the public API                            |
| `registerSettingsSection($section)`  | Register a section via the public API                        |
| `registerSettingsParameter($param)`  | Register a parameter via the public API                      |
| `registerTestSettingsStructure(...)` | Register a complete tab + section + optional parameter       |
| `getSettingsValue($userId, ...)`     | Read a value via the public API                              |
| `setSettingsValue($userId, ...)`     | Write a value via the public API                             |
| `resetSettingsToDefault($userId, ...)` | Reset a value via the public API                           |
| `clearSettingsCache($userId)`        | Forget the Laravel cache entry for a user                    |
