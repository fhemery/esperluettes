# Settings Domain - User Preferences System

## Overview

The Settings domain provides a **generic, extensible system** for user preferences. Each domain can register its own settings (tabs, sections, parameters), and users can customize their experience through a dedicated settings page.

This system mirrors the architecture of the **Config domain** (admin-level configuration) but is scoped to individual users.

---

## Glossary

| Term | Description |
|------|-------------|
| **Tab** | Top-level grouping, typically one per domain. Displayed as navigation tabs on the settings page. |
| **Section** | Grouping within a tab. Collapsible. Contains related parameters. |
| **Parameter** | Individual setting with a type, default value, and optional constraints. |

---

## Key Design Decisions

1. **Domain-based registration**: Each domain registers its tabs/sections/parameters in its `ServiceProvider::boot()`.
2. **Cross-domain registration**: A domain can register sections/parameters under another domain's tab (e.g., Discord registers notification settings under Notification tab).
3. **Role-based visibility**: Parameters can be restricted to specific roles. Users only see tabs/sections containing at least one visible parameter.
4. **Storage optimization**: Only non-default values are persisted to the database.
5. **Caching**: Per-user settings are cached and invalidated on update/reset.
6. **Shared components**: Field components (`bool-field`, `int-field`, etc.) are shared with Config domain via Shared domain.

---

## Architecture

### Domain Structure

```
app/Domains/Settings/
├── Database/
│   └── Migrations/
│       └── YYYY_MM_DD_HHiiss_create_user_settings_table.php
├── Private/
│   ├── Controllers/
│   │   └── SettingsController.php
│   ├── Models/
│   │   └── UserSetting.php
│   ├── Repositories/
│   │   └── UserSettingRepository.php
│   ├── Resources/
│   │   ├── lang/fr/
│   │   │   └── settings.php
│   │   └── views/
│   │       ├── components/
│   │       │   ├── settings-section.blade.php
│   │       │   └── settings-parameter-row.blade.php
│   │       └── pages/
│   │           └── index.blade.php
│   ├── Services/
│   │   └── SettingsRegistryService.php
│   └── routes.php
├── Public/
│   ├── Api/
│   │   └── SettingsPublicApi.php
│   ├── Contracts/
│   │   ├── SettingsTabDefinition.php
│   │   ├── SettingsSectionDefinition.php
│   │   └── SettingsParameterDefinition.php
│   ├── Providers/
│   │   └── SettingsServiceProvider.php
│   └── Services/
│       └── UserSettingsService.php
├── Tests/
│   └── Feature/
│       ├── SettingsRegistrationTest.php
│       ├── SettingsPageTest.php
│       ├── SettingsUpdateTest.php
│       └── SettingsResetTest.php
└── README.md
```

### Shared Domain Changes

Move parameter field components from Config to Shared:

```
app/Domains/Shared/Resources/views/components/fields/
├── bool-field.blade.php      (moved from Config)
├── int-field.blade.php       (moved from Config)
├── string-field.blade.php    (moved from Config)
├── time-field.blade.php      (moved from Config)
├── enum-field.blade.php      (NEW)
├── range-field.blade.php     (NEW)
└── multi-select-field.blade.php (NEW, wraps searchable-multi-select)
```

### Parameter Types (Shared)

Create a shared `ParameterType` enum in Shared domain, replacing `ConfigParameterType`:

```php
// app/Domains/Shared/Public/Contracts/ParameterType.php
enum ParameterType: string
{
    case INT = 'int';
    case STRING = 'string';
    case BOOL = 'bool';
    case TIME = 'time';           // Existing
    case ENUM = 'enum';           // NEW: Single selection from options
    case RANGE = 'range';         // NEW: Numeric with slider UI
    case MULTI_SELECT = 'multi';  // NEW: Multiple selection from options
    
    public function cast(mixed $value): mixed;
    public function serialize(mixed $value): string;
}
```

---

## Data Model

### Database Schema

```php
// user_settings table
Schema::create('user_settings', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');  // No FK, Auth domain is separate
    $table->string('domain', 50);
    $table->string('key', 100);
    $table->text('value');
    $table->timestamps();
    
    $table->unique(['user_id', 'domain', 'key']);
    $table->index('user_id');  // Fast lookup for caching all user settings
});
```

### Registration Contracts

#### SettingsTabDefinition

```php
final class SettingsTabDefinition
{
    public function __construct(
        public readonly string $id,           // Unique tab identifier (e.g., 'story', 'notification')
        public readonly int $order,           // Display order (lower = first)
        public readonly string $nameKey,      // Full translation key for tab name
        public readonly ?string $icon = null, // Optional Material Symbols icon name
    ) {}
}
```

#### SettingsSectionDefinition

```php
final class SettingsSectionDefinition
{
    public function __construct(
        public readonly string $tabId,            // Reference to parent tab
        public readonly string $id,               // Unique section identifier within tab
        public readonly int $order,               // Display order within tab
        public readonly string $nameKey,          // Full translation key for section name
        public readonly ?string $descriptionKey = null, // Full translation key for description
    ) {}
}
```

#### SettingsParameterDefinition

```php
final class SettingsParameterDefinition
{
    /**
     * @param string $tabId              Reference to parent tab
     * @param string $sectionId          Reference to parent section
     * @param string $key                Unique key within tab (used for storage)
     * @param ParameterType $type        Value type
     * @param mixed $default             Default value when no override exists
     * @param int $order                 Display order within section
     * @param string $nameKey            Full translation key for parameter name
     * @param string|null $descriptionKey Full translation key for description
     * @param array $constraints         Type-specific constraints (min, max, options, etc.)
     * @param array $roles               Roles required to see this parameter (empty = all authenticated users)
     */
    public function __construct(
        public readonly string $tabId,
        public readonly string $sectionId,
        public readonly string $key,
        public readonly ParameterType $type,
        public readonly mixed $default,
        public readonly int $order,
        public readonly string $nameKey,
        public readonly ?string $descriptionKey = null,
        public readonly array $constraints = [],
        public readonly array $roles = [],
    ) {}
}
```

### Constraints by Type

| Type | Constraint Keys | Example |
|------|-----------------|---------|
| `INT` | `min`, `max` | `['min' => 1, 'max' => 100]` |
| `STRING` | `min_length`, `max_length`, `pattern` | `['max_length' => 255]` |
| `BOOL` | (none) | `[]` |
| `TIME` | `min`, `max` | `['min' => 60, 'max' => 86400]` |
| `ENUM` | `options` (array of `[value => labelKey]`) | `['options' => ['light' => 'settings::theme.light', 'dark' => 'settings::theme.dark']]` |
| `RANGE` | `min`, `max`, `step` | `['min' => 12, 'max' => 28, 'step' => 2]` |
| `MULTI_SELECT` | `options` (array of `[value => labelKey]`) | `['options' => ['email' => '...', 'push' => '...']]` |

---

## Public API

```php
// app/Domains/Settings/Public/Api/SettingsPublicApi.php

class SettingsPublicApi
{
    // =========================================================================
    // Registration (called from ServiceProvider::boot())
    // =========================================================================
    
    /**
     * Register a tab. Tabs must be registered before sections.
     */
    public function registerTab(SettingsTabDefinition $tab): void;
    
    /**
     * Register a section under a tab.
     * @throws \InvalidArgumentException if tab does not exist
     */
    public function registerSection(SettingsSectionDefinition $section): void;
    
    /**
     * Register a parameter under a section.
     * @throws \InvalidArgumentException if tab or section does not exist
     */
    public function registerParameter(SettingsParameterDefinition $param): void;
    
    // =========================================================================
    // Value Access (for other domains)
    // =========================================================================
    
    /**
     * Get current value for a parameter for a specific user.
     * Returns default if no override exists.
     * Returns null if parameter not registered.
     */
    public function getValue(int $userId, string $tabId, string $key): mixed;
    
    /**
     * Convenience: get value for currently authenticated user.
     * Returns default if not authenticated or parameter not registered.
     */
    public function getValueForCurrentUser(string $tabId, string $key): mixed;
    
    /**
     * Set a parameter value for a specific user.
     * If value equals default, the stored override is removed.
     */
    public function setValue(int $userId, string $tabId, string $key, mixed $value): void;
    
    /**
     * Convenience: set value for currently authenticated user.
     */
    public function setValueForCurrentUser(string $tabId, string $key, mixed $value): void;
    
    /**
     * Reset a parameter to its default value (removes stored override).
     */
    public function resetToDefault(int $userId, string $tabId, string $key): void;
    
    /**
     * Convenience: reset for currently authenticated user.
     */
    public function resetToDefaultForCurrentUser(string $tabId, string $key): void;
}
```

---

## Caching Strategy

- **Cache key**: `user_settings:{user_id}`
- **Cache content**: All non-default settings for the user as `["{tabId}.{key}" => serializedValue]`
- **Invalidation**: Clear cache on any `setValue()` or `resetToDefault()` call
- **TTL**: Forever (invalidated explicitly)
- **Eager loading**: On first access per request, load all settings into memory

---

## UI/UX Design

### Settings Page Layout

**Desktop (md+)**:
- Vertical tabs on the left sidebar
- Content area on the right
- Tabs load content via partial view (not full page reload)

**Mobile**:
- Horizontal scrollable tabs at top
- Content below
- Same partial loading mechanism

### Section Display

- Sections are rendered using `<x-shared::collapsible>` component
- First section of each tab is open by default
- Section header shows name and optional description

### Parameter Row

- Similar to Config's `parameter-row.blade.php`
- Shows: parameter name, description, input field, save button, reset button (if overridden)
- Save button enabled only when value differs from saved value
- Reset button visible only when value is overridden

### Navigation Entry

Add "Préférences" link in the profile drawer, after "Mon compte":

```blade
<x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
    {{ __('shared::navigation.settings') }}
</x-responsive-nav-link>
```

---

## Example Registration

```php
// In StoryServiceProvider::boot()

$settingsApi = app(SettingsPublicApi::class);

// Register tab (if not already registered by another domain)
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
    descriptionKey: 'story::settings.sections.reading.description',
));

// Register parameters
$settingsApi->registerParameter(new SettingsParameterDefinition(
    tabId: 'story',
    sectionId: 'reading',
    key: 'font_size',
    type: ParameterType::RANGE,
    default: 16,
    order: 10,
    nameKey: 'story::settings.params.font_size.name',
    descriptionKey: 'story::settings.params.font_size.description',
    constraints: ['min' => 12, 'max' => 28, 'step' => 2],
));

$settingsApi->registerParameter(new SettingsParameterDefinition(
    tabId: 'story',
    sectionId: 'reading',
    key: 'font_family',
    type: ParameterType::ENUM,
    default: 'system',
    order: 20,
    nameKey: 'story::settings.params.font_family.name',
    constraints: [
        'options' => [
            'system' => 'story::settings.fonts.system',
            'serif' => 'story::settings.fonts.serif',
            'sans' => 'story::settings.fonts.sans',
            'mono' => 'story::settings.fonts.mono',
        ],
    ],
));
```

### Cross-Domain Example (Discord → Notification tab)

```php
// In DiscordServiceProvider::boot()

$settingsApi = app(SettingsPublicApi::class);

// Register section under Notification tab (owned by Notification domain)
$settingsApi->registerSection(new SettingsSectionDefinition(
    tabId: 'notification',  // Tab owned by Notification domain
    id: 'discord',
    order: 50,
    nameKey: 'discord::settings.sections.discord.name',
    descriptionKey: 'discord::settings.sections.discord.description',
));

$settingsApi->registerParameter(new SettingsParameterDefinition(
    tabId: 'notification',
    sectionId: 'discord',
    key: 'new_chapter_dm',
    type: ParameterType::BOOL,
    default: false,
    order: 10,
    nameKey: 'discord::settings.params.new_chapter_dm.name',
    descriptionKey: 'discord::settings.params.new_chapter_dm.description',
));
```

---

## Validation Rules

1. **Tab registration**: Tab ID must be unique across all domains.
2. **Section registration**: 
   - Tab must exist (throw `\InvalidArgumentException` if not)
   - Section ID must be unique within the tab
3. **Parameter registration**:
   - Tab must exist (throw `\InvalidArgumentException` if not)
   - Section must exist within the tab (throw `\InvalidArgumentException` if not)
   - Key must be unique within the tab
4. **Value validation**: Same rules as Config (type-based + constraints)

---

## User Stories

### Phase 0: Refactoring (Prerequisites)

| ID | Story | Priority |
|----|-------|----------|
| **SET-001** | Create `ParameterType` enum in Shared domain with all types (INT, STRING, BOOL, TIME, ENUM, RANGE, MULTI_SELECT) | HIGH |
| **SET-002** | Move field components (`bool-field`, `int-field`, `string-field`, `time-field`) from Config to Shared domain | HIGH |
| **SET-003** | Update Config domain to use Shared's `ParameterType` and field components | HIGH |
| **SET-004** | Create new field components in Shared: `enum-field`, `range-field`, `multi-select-field` | HIGH |

### Phase 1: Core Settings Domain

| ID | Story | Priority |
|----|-------|----------|
| **SET-010** | Create Settings domain structure with migration for `user_settings` table | HIGH |
| **SET-011** | Implement `SettingsTabDefinition`, `SettingsSectionDefinition`, `SettingsParameterDefinition` contracts | HIGH |
| **SET-012** | Implement `SettingsRegistryService` with tab/section/parameter registration and validation | HIGH |
| **SET-013** | Implement `UserSettingRepository` for CRUD on user_settings table | HIGH |
| **SET-014** | Implement `UserSettingsService` with caching strategy | HIGH |
| **SET-015** | Implement `SettingsPublicApi` with all registration and value access methods | HIGH |

### Phase 2: Settings Page UI

| ID | Story | Priority |
|----|-------|----------|
| **SET-020** | Create settings page layout with vertical tabs (desktop) and horizontal tabs (mobile) | HIGH |
| **SET-021** | Implement tab content loading via partial views (one tab loaded at a time) | HIGH |
| **SET-022** | Create `settings-section.blade.php` component using `<x-shared::collapsible>` | HIGH |
| **SET-023** | Create `settings-parameter-row.blade.php` component with save/reset functionality | HIGH |
| **SET-024** | Implement role-based visibility filtering for tabs/sections/parameters | HIGH |
| **SET-025** | Add "Préférences" link in navigation drawer | HIGH |

### Phase 3: Integration & Testing

| ID | Story | Priority |
|----|-------|----------|
| **SET-030** | Write tests for registration validation (duplicate IDs, missing parent, etc.) | HIGH |
| **SET-031** | Write tests for value get/set/reset with caching | HIGH |
| **SET-032** | Write tests for role-based visibility | HIGH |
| **SET-033** | Write tests for settings page rendering (authenticated, role filtering) | HIGH |
| **SET-034** | Write tests for parameter update/reset via controller | HIGH |

### Phase 4: Example Settings (Optional, for validation)

| ID | Story | Priority |
|----|-------|----------|
| **SET-040** | Register sample settings in a domain (e.g., Story: font_size, font_family) | LOW |
| **SET-041** | Integrate settings value reading in chapter view (font size/family) | LOW |

---

## Routes

```php
// Settings domain routes (authenticated users only)
Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('/{tab}', [SettingsController::class, 'tab'])->name('tab');  // Partial for AJAX
    Route::put('/{tab}/{key}', [SettingsController::class, 'update'])->name('update');
    Route::delete('/{tab}/{key}', [SettingsController::class, 'reset'])->name('reset');
});
```

---

## Open Considerations

1. **Service provider boot order**: Ensure Settings domain boots before other domains so the API is available for registration. Use `$this->app->booted()` callback if needed.

2. **Translation organization**: Each domain manages its own translations. Cross-domain registrations (e.g., Discord → Notification tab) use the registering domain's translation namespace.

3. **Future enhancements**:
   - Quick access widgets in context (e.g., font size controls in chapter view)
   - Settings search within a tab (if parameter count grows)
   - Bulk reset per section

---

## Related Documents

- [Config Domain README](/app/Domains/Config/README.md)
- [Domain Structure](/docs/Domain_Structure.md)
