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
            // Pop-up markup for link instructions
            ->toContain('data-dialog="discord-link"')
            ->toContain('data-action="open-link"');
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
            ->toContain('data-discord-state="connected"')
            ->toContain('DisplayName')
            ->toContain('link_off') // Google Material Symbols name for unlink icon
            // Pop-up markup for unlink confirmation
            ->toContain('data-dialog="discord-unlink"')
            ->toContain('data-action="open-unlink"');
    });
});
