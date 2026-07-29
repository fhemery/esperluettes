<?php

declare(strict_types=1);

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Discord\Private\Support\DiscordFeatureToggles;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY'] = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;

    createFeatureToggle($this, new FeatureToggle(
        name:   DiscordFeatureToggles::NOTIFICATIONS,
        domain: DiscordFeatureToggles::DOMAIN,
        access: FeatureToggleAccess::ON,
    ));
});

afterEach(function () {
    clearSettingsRegistry();
});

describe('Discord link hint on notification preferences', function () {
    it('shows a warning when the user has no linked Discord account', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']));

        $response->assertOk();
        $response->assertSee('discord::notifications.not_linked_warning', false);
    });

    it('does not show a warning when the user has a linked Discord account', function () {
        $user = alice($this);
        linkDiscord($this, $user);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']));

        $response->assertOk();
        $response->assertDontSee('discord::notifications.not_linked_warning', false);
    });
});
