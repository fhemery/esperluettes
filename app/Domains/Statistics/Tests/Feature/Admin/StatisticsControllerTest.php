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
            ->assertSee(__('statistics::admin.stories'))
            ->assertSee(__('statistics::admin.chapters'))
            ->assertSee(__('statistics::admin.words'))
            ->assertSee(__('statistics::admin.tab_users'))
            ->assertSee(__('statistics::admin.tab_content'))
            ->assertSee(__('statistics::admin.evolution', ['metric' => __('statistics::admin.users')]));
    });

    it('renders the graph mode switch with both options, cumulative selected', function () {
        $response = $this->actingAs(admin($this))
            ->get(route('statistics.admin.index'))
            ->assertOk()
            ->assertSee('role="radiogroup"', false)
            ->assertSee('aria-label="'.__('statistics::admin.graph_mode_label').'"', false)
            ->assertSee('data-name="statistics-graph-mode"', false)
            ->assertSee('data-value="cumulative"', false)
            ->assertSee(__('statistics::admin.graph_mode_cumulative'))
            ->assertSee(__('statistics::admin.graph_mode_weekly'))
            ->assertSee('statistics.admin.graph-mode', false);

        expect($response->getContent())
            ->toMatch('/<button[^>]*aria-checked="true"[^>]*data-value="cumulative"/')
            ->toMatch('/<button[^>]*aria-checked="false"[^>]*data-value="weekly"/');
    });

    it('keeps the summary tiles outside the graph mode switch', function () {
        $html = $this->actingAs(admin($this))
            ->get(route('statistics.admin.index'))
            ->assertOk()
            ->getContent();

        $switchAt = strpos($html, 'data-name="statistics-graph-mode"');
        $tabsAt = strpos($html, 'id="tabs-panel-users"');

        expect($switchAt)->not->toBeFalse()
            ->and(strrpos(substr($html, 0, $switchAt), 'stat-summary'))->not->toBeFalse()
            ->and(strrpos(substr($html, 0, $switchAt), 'comment-summary'))->not->toBeFalse()
            ->and(strpos($html, 'stat-summary', $switchAt))->toBeFalse()
            ->and(strpos($html, 'comment-summary', $switchAt))->toBeFalse()
            ->and($switchAt)->toBeLessThan($tabsAt);
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
