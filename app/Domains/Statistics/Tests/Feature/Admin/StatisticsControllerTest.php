<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Statistics Admin Controller', function () {
    it('displays the statistics page for admins', function () {
        $this->actingAs(admin($this))
            ->get(route('statistics.admin.index'))
            ->assertOk()
            ->assertSee(__('statistics::admin.title'))
            ->assertSee(__('statistics::admin.users'))
            ->assertSee(__('statistics::admin.evolution', ['metric' => __('statistics::admin.users')]));
    });

    it('denies access to non-admins', function () {
        $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

        $this->actingAs($user)
            ->get(route('statistics.admin.index'))
            ->assertRedirect(route('dashboard'));
    });

    it('redirects unauthenticated users to login', function () {
        $this->get(route('statistics.admin.index'))
            ->assertRedirect(route('login'));
    });

    it('registers the statistics page in admin navigation', function () {
        $registry = app(AdminNavigationRegistry::class);

        expect($registry->getPages())->toHaveKey('statistics.admin')
            ->and($registry->getGroups())->toHaveKey('statistics');
    });
});
