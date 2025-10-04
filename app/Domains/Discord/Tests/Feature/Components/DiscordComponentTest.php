<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('DiscordComponent', function () {
    it('renders a greyed out Discord icon with a link label when user is not linked', function () {
        $viewer = alice($this);
        $this->actingAs($viewer);

        $html = Blade::render('<x-discord::discord-component />');

        expect($html)
            ->toContain('data-discord-state="disconnected"')
            ->toContain('link')
            // Trigger uses shared modal via Alpine method
            ->toContain('data-action="open-link"')
            ->toContain('x-on:click="openLinkAndGenerate()"')
            // Modal name is present via shared modal component
            ->toContain('discord-link');
    });

    it('renders Discord username and an unlink icon when user is linked', function () {
        $viewer = alice($this);
        $this->actingAs($viewer);

        $code = givenDiscordConnectCodeForUser($this, $viewer);
        discordConnectWithCode($this, $code, [
            'discordId' => '123456789012345678',
            'discordUsername' => 'DisplayName',
        ])->assertStatus(200);

        $html = Blade::render('<x-discord::discord-component />');

        expect($html)
            ->toContain('DisplayName')
            ->toContain('link_off') // Google Material Symbols name for unlink icon
            // Trigger for unlink modal
            ->toContain('openUnlink')
            // Modal name is present via shared modal component
            ->toContain('discord-unlink');
    });

    it('does not render when DISCORD_RESTRICTED_ACCESS_USER_IDS is set and user is not allowed', function () {
        // Arrange env restriction to another id
        putenv('DISCORD_RESTRICTED_ACCESS_USER_IDS=9999,8888');

        $viewer = alice($this);
        $this->actingAs($viewer);

        $html = Blade::render('<x-discord::discord-component />');

        expect(trim($html))->toBe('');
    });

    it('renders when DISCORD_RESTRICTED_ACCESS_USER_IDS includes the current user id', function () {
        $viewer = alice($this);
        $this->actingAs($viewer);
        putenv('DISCORD_RESTRICTED_ACCESS_USER_IDS=' . $viewer->id . ',9999');

        $html = Blade::render('<x-discord::discord-component />');

        expect($html)
            ->toContain('data-discord-state=');
    });

    it('renders when DISCORD_RESTRICTED_ACCESS_USER_IDS is empty', function () {
        putenv('DISCORD_RESTRICTED_ACCESS_USER_IDS=');
        $viewer = alice($this);
        $this->actingAs($viewer);

        $html = Blade::render('<x-discord::discord-component />');
        expect($html)->toContain('data-discord-state=');
    });
});
