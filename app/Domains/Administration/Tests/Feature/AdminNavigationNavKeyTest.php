<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Proves the sidebar emits `data-nav-key` for registry pages and hardcoded
 * links. Registrations are fakes owned by this test — never live keys from
 * News, Config, or other domains (that inventory belongs to e2e).
 */
describe('Admin sidebar data-nav-key', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();

        $this->registry->registerGroup('fake', 'Fake Group', 10);
        $this->registry->registerPage(
            'fake.open',
            'fake',
            'Open to staff',
            AdminRegistryTarget::url('http://localhost/admin/fake-open'),
            'page',
            [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
            10,
        );
        $this->registry->registerPage(
            'fake.admin-only',
            'fake',
            'Admin only',
            AdminRegistryTarget::url('http://localhost/admin/fake-admin'),
            'page',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            20,
        );
        $this->registry->registerPage(
            'fake.tech-only',
            'fake',
            'Tech only',
            AdminRegistryTarget::url('http://localhost/admin/fake-tech'),
            'page',
            [Roles::TECH_ADMIN],
            30,
        );
    });

    it('exposes hardcoded keys and registry keys the role can access', function () {
        $this->actingAs(admin($this));

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="dashboard"')
            ->and($html)->toContain('data-nav-key="back-to-site"')
            ->and($html)->toContain('data-nav-key="fake.open"')
            ->and($html)->toContain('data-nav-key="fake.admin-only"')
            ->and($html)->not->toContain('data-nav-key="fake.tech-only"');
    });

    it('omits registry keys the role cannot access', function () {
        $this->actingAs(moderator($this));

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="dashboard"')
            ->and($html)->toContain('data-nav-key="fake.open"')
            ->and($html)->not->toContain('data-nav-key="fake.admin-only"')
            ->and($html)->not->toContain('data-nav-key="fake.tech-only"');
    });

    it('includes tech-only registry keys for a tech-admin', function () {
        $this->actingAs(techAdmin($this));

        $html = $this->get(route('administration.dashboard'))->assertOk()->getContent();

        expect($html)->toContain('data-nav-key="fake.open"')
            ->and($html)->toContain('data-nav-key="fake.admin-only"')
            ->and($html)->toContain('data-nav-key="fake.tech-only"');
    });
});
