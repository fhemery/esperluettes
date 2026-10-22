<?php

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    clearSettingsRegistry();
});

it('shows the Fanart section in Paramètres › Histoires next to Story reading prefs', function () {
    $this->actingAs(alice($this))
        ->get(route('settings.tab', ['tab' => 'stories']))
        ->assertOk()
        ->assertSee('story::settings.params.hide-trigger-warnings.name')
        ->assertSee('story::settings.params.hide-stories-with-tw.name')
        ->assertSee('fanart::settings.sections.fanart.name')
        ->assertSee('fanart::settings.params.fanart-images-mode.name')
        ->assertSee('fanart::settings.params.fanart-audio-mode.name');
});

it('renders the Fanart section expanded, not only the first section of the tab', function () {
    $html = $this->actingAs(alice($this))
        ->get(route('settings.tab', ['tab' => 'stories']))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'x-data="{ open: true }"'))->toBe(2);
});

it('saves fanart-images-mode and rejects an unknown value', function () {
    $user = alice($this);

    $this->actingAs($user)
        ->putJson(route('settings.update', ['tab' => 'stories', 'key' => 'fanart-images-mode']), ['value' => 'designated'])
        ->assertOk();
    expect(app(SettingsPublicApi::class)->getValue($user->id, 'stories', 'fanart-images-mode'))->toBe('designated');

    $this->actingAs($user)
        ->putJson(route('settings.update', ['tab' => 'stories', 'key' => 'fanart-images-mode']), ['value' => 'bogus'])
        ->assertStatus(422);
    expect(app(SettingsPublicApi::class)->getValue($user->id, 'stories', 'fanart-images-mode'))->toBe('designated');
});

it('shows hide-fanart-tab in Paramètres › Profil, default unchecked', function () {
    $user = alice($this);

    $this->actingAs($user)
        ->get(route('settings.tab', ['tab' => 'profile']))
        ->assertOk()
        ->assertSee('fanart::settings.params.hide-fanart-tab.name');

    expect(app(SettingsPublicApi::class)->getValue($user->id, 'profile', 'hide-fanart-tab'))->toBeFalse();
});
