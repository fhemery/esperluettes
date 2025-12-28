# Settings Domain

The Settings domain provides a **generic, extensible system** for user preferences. Each domain can register its own settings (tabs, sections, parameters), and users can customize their experience through a dedicated settings page.

## Architecture

- **Public API**: `SettingsPublicApi` - Registration and value access for other domains
- **Contracts**: `SettingsTabDefinition`, `SettingsSectionDefinition`, `SettingsParameterDefinition`
- **Services**: `SettingsService` (value access with caching), `SettingsRegistryService` (registration)
- **Repository**: `SettingRepository` - CRUD operations on settings table

## Usage

### Registering Settings (in your domain's ServiceProvider)

```php
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;

public function boot(): void
{
    $settingsApi = app(SettingsPublicApi::class);

    // Register tab
    $settingsApi->registerTab(new SettingsTabDefinition(
        id: 'story',
        order: 20,
        nameKey: 'story::settings.tab_name',
        icon: 'menu_book',
    ));

    // Register section
    $settingsApi->registerSection(new SettingsSectionDefinition(
        tabId: 'story',
        id: 'reading',
        order: 10,
        nameKey: 'story::settings.sections.reading.name',
    ));

    // Register parameter
    $settingsApi->registerParameter(new SettingsParameterDefinition(
        tabId: 'story',
        sectionId: 'reading',
        key: 'font_size',
        type: ParameterType::RANGE,
        default: 16,
        order: 10,
        nameKey: 'story::settings.params.font_size.name',
        constraints: ['min' => 12, 'max' => 28, 'step' => 2],
    ));
}
```

### Reading Values

```php
$settingsApi = app(SettingsPublicApi::class);

// For current user
$fontSize = $settingsApi->getValueForCurrentUser('story', 'font_size');

// For specific user
$fontSize = $settingsApi->getValue($userId, 'story', 'font_size');
```

## Caching

- Per-user settings are cached with key `user_settings:{user_id}`
- Cache is invalidated on any `setValue()` or `resetToDefault()` call
- Only non-default values are stored in the database
