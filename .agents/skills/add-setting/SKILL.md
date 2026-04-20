---
name: add-setting
description: Step-by-step guide for adding a new user-configurable setting to the app's domain-driven settings system. Use when a domain needs to expose a new user preference (theme, font, interline, etc.) through the shared settings page.
---

# Add a User Setting

The settings system is domain-driven: each domain registers its own tabs, sections, and parameters via `SettingsPublicApi`.

## Overview of the system

- **`SettingsPublicApi`** — the public contract for registering and reading settings
- **`SettingsTabDefinition`** — a top-level tab on the settings page (e.g. "Général")
- **`SettingsSectionDefinition`** — a group of parameters within a tab (e.g. "Apparence")
- **`SettingsParameterDefinition`** — a single configurable value (e.g. font, theme)
- **`ParameterType`** — the input type: `ENUM`, `RANGE`, `BOOLEAN`, etc.
- Settings are stored per-user in the `settings` table; only non-default values are persisted
- Values are cached per user under `user_settings:{user_id}`

---

## Step 1 — Add a constant in your ServiceProvider

```php
// In your domain's ServiceProvider
public const KEY_MY_SETTING = 'my_setting';
```

If the tab and section already exist (e.g. the shared "general / appearance" tab), reuse their constants from `SharedServiceProvider`.

---

## Step 2 — Register the parameter in `boot()`

Call `registerParameter()` inside `$this->app->booted()` to ensure all providers are loaded:

```php
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;

$this->app->booted(function () {
    $settingsApi = app(SettingsPublicApi::class);

    $settingsApi->registerParameter(new SettingsParameterDefinition(
        tabId: SharedServiceProvider::TAB_GENERAL,
        sectionId: SharedServiceProvider::SECTION_APPEARANCE,
        key: self::KEY_MY_SETTING,
        type: ParameterType::ENUM,
        default: 'option_a',
        order: 30,
        nameKey: 'shared::settings.params.my_setting.name',
        descriptionKey: 'shared::settings.params.my_setting.description',
        constraints: [
            'options' => [
                'option_a' => 'shared::settings.params.my_setting.options.option_a',
                'option_b' => 'shared::settings.params.my_setting.options.option_b',
            ],
        ],
    ));
});
```

If you also need a new tab or section, register them first (before the parameter), in order:
1. `registerTab()` → 2. `registerSection()` → 3. `registerParameter()`

Use the idempotency guard to avoid double-registration in tests:
```php
if ($settingsApi->getTab('my_tab') !== null) return;
```

---

## Step 3 — Add translations

In `app/Domains/<YourDomain>/Resources/lang/fr/settings.php`:

```php
return [
    'params' => [
        'my_setting' => [
            'name' => 'Mon réglage',
            'description' => 'Description affichée sous le titre.',
            'options' => [
                'option_a' => 'Option A (défaut)',
                'option_b' => 'Option B',
            ],
        ],
    ],
];
```

The translation namespace (`shared::`, `story::`, etc.) must match what you pass in `nameKey` / `descriptionKey`.

**Important:** setting keys and option values are always English identifiers. Only the translation strings are in French.

---

## Step 4 — Create a Service to read the value

Create a service in `app/Domains/<YourDomain>/Services/MySettingService.php`:

```php
namespace App\Domains\Shared\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Providers\SharedServiceProvider;

class MySettingService
{
    public const OPTION_A = 'option_a';
    public const OPTION_B = 'option_b';
    public const DEFAULT = self::OPTION_A;

    private const VALID_VALUES = [self::OPTION_A, self::OPTION_B];

    public function __construct(
        private SettingsPublicApi $settingsApi,
    ) {}

    public function resolve(?int $userId = null): string
    {
        if ($userId) {
            $pref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_MY_SETTING
            );

            if ($pref && in_array($pref, self::VALID_VALUES, true)) {
                return $pref;
            }
        }

        return self::DEFAULT;
    }

    public function current(): string
    {
        return $this->resolve(auth()->id());
    }
}
```

---

## Step 5 — Expose the value to views (if needed for HTML rendering)

If the setting must affect the rendered HTML (e.g. a `data-*` attribute on `<html>`), add it to `ResolveThemeMiddleware`:

```php
// app/Domains/Shared/Http/Middleware/ResolveThemeMiddleware.php

use App\Domains\Shared\Services\MySettingService;

public function __construct(
    private readonly ThemeService $themeService,
    private readonly FontService $fontService,
    private readonly MySettingService $mySettingService,
) {}

public function handle(Request $request, Closure $next): Response
{
    $theme = $this->themeService->current();
    $font = $this->fontService->current();
    $mySetting = $this->mySettingService->current();

    View::share('theme', $theme);
    View::share('userFont', $font);
    View::share('userMySetting', $mySetting);

    $request->attributes->set('theme', $theme);
    $request->attributes->set('userFont', $font);
    $request->attributes->set('userMySetting', $mySetting);

    return $next($request);
}
```

Then update **both** layout blade files (`app.blade.php` and `guest.blade.php`) to add the `data-*` attribute on `<html>`:

```html
<html ... data-my-setting="{{ $userMySetting ?? 'option_a' }}">
```

---

## Step 6 — Add CSS (if the setting affects styling)

In `app/Domains/Shared/Resources/css/app.scss`, follow the CSS-variable pattern used by `data-font` and `data-interline`:

```scss
:root[data-my-setting="option_a"] {
  --my-setting-value: /* CSS value for option A */;
}

:root[data-my-setting="option_b"] {
  --my-setting-value: /* CSS value for option B */;
}

/* Default if no attribute set */
:root:not([data-my-setting]) {
  --my-setting-value: /* default CSS value */;
}
```

Then use the variable in the targeted class:
```scss
.my-targeted-class {
  some-property: var(--my-setting-value);
}
```

---

## Step 7 — Add tests

Add tests in `app/Domains/<YourDomain>/Tests/Feature/` following the pattern in `ThemePreferenceTest.php`. Cover:

- Default value for guests
- Default value for users with no preference
- Each non-default option
- Fallback for invalid/corrupted stored value
- Settings registration (parameter registered with correct type, default, options)
- Settings page integration (can update via `PUT settings.update`, HTML `data-*` attribute is present)

Use the `setSettingsValue($userId, $tab, $key, $value)` test helper to set values directly.

---

## Reading a setting value in application code

```php
$settingsApi = app(SettingsPublicApi::class);

// For current authenticated user
$value = $settingsApi->getValueForCurrentUser('general', 'my_setting');

// For a specific user
$value = $settingsApi->getValue($userId, 'general', 'my_setting');
```
