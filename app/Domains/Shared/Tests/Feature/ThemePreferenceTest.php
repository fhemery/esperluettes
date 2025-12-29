<?php

use App\Domains\Settings\Private\Services\SettingsRegistryService;
use App\Domains\Shared\Contracts\Theme;
use App\Domains\Shared\Providers\SharedServiceProvider;
use App\Domains\Shared\Services\ThemeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure settings are registered (may have been cleared by other tests)
    $provider = new SharedServiceProvider(app());
    $reflection = new \ReflectionMethod($provider, 'registerSettings');
    $reflection->setAccessible(true);
    $reflection->invoke($provider);
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('ThemeService - user preference', function () {
    it('returns seasonal theme for guests', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $themeService = app(ThemeService::class);

        expect($themeService->resolve(null))->toBe(Theme::WINTER);
    });

    it('returns seasonal theme when user has no preference', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);
        $themeService = app(ThemeService::class);

        expect($themeService->resolve($user->id))->toBe(Theme::WINTER);
    });

    it('returns seasonal theme when user preference is seasonal', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'seasonal'
        );

        $themeService = app(ThemeService::class);

        expect($themeService->resolve($user->id))->toBe(Theme::WINTER);
    });

    it('returns autumn theme when user selects autumn', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'autumn'
        );

        $themeService = app(ThemeService::class);

        expect($themeService->resolve($user->id))->toBe(Theme::AUTUMN);
    });

    it('returns winter theme when user selects winter', function () {
        Carbon::setTestNow(Carbon::create(2024, 9, 25));

        $user = alice($this);
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'winter'
        );

        $themeService = app(ThemeService::class);

        expect($themeService->resolve($user->id))->toBe(Theme::WINTER);
    });

    it('returns seasonal theme for invalid preference value', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);

        // First set a valid value, then corrupt it directly in the database
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'autumn'
        );

        // Now corrupt the value directly
        \Illuminate\Support\Facades\DB::table('settings')
            ->where('user_id', $user->id)
            ->where('domain', SharedServiceProvider::TAB_GENERAL)
            ->where('key', SharedServiceProvider::KEY_THEME)
            ->update(['value' => 'invalid_theme']);

        // Clear cache to force re-read
        \Illuminate\Support\Facades\Cache::forget("user_settings:{$user->id}");

        $themeService = app(ThemeService::class);

        expect($themeService->resolve($user->id))->toBe(Theme::WINTER);
    });

    it('uses current() method for authenticated user', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'autumn'
        );

        $this->actingAs($user);
        $themeService = app(ThemeService::class);

        expect($themeService->current())->toBe(Theme::AUTUMN);
    });

    it('uses current() method returns seasonal for guests', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $themeService = app(ThemeService::class);

        expect($themeService->current())->toBe(Theme::WINTER);
    });
});

describe('ThemeService - settings registration', function () {
    it('registers general tab', function () {
        $api = app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class);

        $tab = $api->getTab(SharedServiceProvider::TAB_GENERAL);

        expect($tab)->not->toBeNull();
        expect($tab->id)->toBe('general');
        expect($tab->nameKey)->toBe('shared::settings.tabs.general');
    });

    it('registers appearance section', function () {
        $api = app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class);

        $sections = $api->getSectionsForTab(SharedServiceProvider::TAB_GENERAL);

        expect($sections)->toHaveCount(1);
        expect($sections[0]->id)->toBe('appearance');
        expect($sections[0]->nameKey)->toBe('shared::settings.sections.appearance.name');
    });

    it('registers theme parameter with correct options', function () {
        $api = app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class);

        $param = $api->getParameter(SharedServiceProvider::TAB_GENERAL, SharedServiceProvider::KEY_THEME);

        expect($param)->not->toBeNull();
        expect($param->key)->toBe('theme');
        expect($param->type)->toBe(\App\Domains\Shared\Contracts\ParameterType::ENUM);
        expect($param->default)->toBe('seasonal');
        expect($param->constraints['options'])->toHaveKeys(['seasonal', 'autumn', 'winter']);
    });
});

describe('ThemeService - settings page integration', function () {
    it('shows theme setting on settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('shared::settings.tabs.general'));
    });

    it('displays theme parameter on settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        // Verify the theme parameter name is displayed
        $response->assertSee(__('shared::settings.params.theme.name'));
    });

    it('can update theme preference via settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', [
                'tab' => SharedServiceProvider::TAB_GENERAL,
                'key' => SharedServiceProvider::KEY_THEME,
            ]), [
                'value' => 'autumn',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $themeService = app(ThemeService::class);
        expect($themeService->resolve($user->id))->toBe(Theme::AUTUMN);
    });

    it('can reset theme preference to default', function () {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $user = alice($this);

        // Set a preference first
        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'autumn'
        );

        // Reset it
        $response = $this->actingAs($user)
            ->deleteJson(route('settings.reset', [
                'tab' => SharedServiceProvider::TAB_GENERAL,
                'key' => SharedServiceProvider::KEY_THEME,
            ]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $themeService = app(ThemeService::class);
        expect($themeService->resolve($user->id))->toBe(Theme::WINTER);
    });
});
