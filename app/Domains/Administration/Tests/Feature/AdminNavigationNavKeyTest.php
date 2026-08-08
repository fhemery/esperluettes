<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin sidebar data-nav-key', function () {
    it('exposes registry and hardcoded nav keys for an admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="dashboard"')
            ->and($html)->toContain('data-nav-key="back-to-site"')
            ->and($html)->toContain('data-nav-key="news.management"')
            ->and($html)->toContain('data-nav-key="config.parameters"');
    });

    it('hides tech-admin-only keys from a moderator', function () {
        $moderator = moderator($this);
        $this->actingAs($moderator);

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="dashboard"')
            ->and($html)->toContain('data-nav-key="news.management"')
            ->and($html)->not->toContain('data-nav-key="maintenance"')
            ->and($html)->not->toContain('data-nav-key="logs"')
            ->and($html)->not->toContain('data-nav-key="config.parameters"');
    });

    it('shows maintenance and logs for a tech-admin', function () {
        $tech = alice($this, [], true, [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]);
        $this->actingAs($tech);

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="maintenance"')
            ->and($html)->toContain('data-nav-key="logs"');
    });
});
