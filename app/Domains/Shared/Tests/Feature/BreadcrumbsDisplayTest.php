<?php

declare(strict_types=1);

use App\Domains\Shared\Contracts\BreadcrumbRegistry;
use App\Domains\Shared\Dto\BreadcrumbTrailDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('breadcrumbs display', function () {

    it('should not render when empty on guest layout', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"');
    });

    it('should not render when empty on logged layout', function () {
        $user = alice($this);
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"');
    });

    it('should render when not empty on guest layout', function () {
        $registry = app(BreadcrumbRegistry::class);
        $registry->for('home', function (BreadcrumbTrailDto $trail) {
            $trail->push('Section');
        });

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__("shared::breadcrumbs.breadcrumb"))
            ->assertSee('Section');
    });

    it('should render when not empty on logged layout', function () {
        $user = alice($this);
        $this->actingAs($user);

        $registry = app(BreadcrumbRegistry::class);
        $registry->for('dashboard', function (BreadcrumbTrailDto $trail) {
            $trail->push('Section');
        });

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('shared::breadcrumbs.breadcrumb'))
            ->assertSee('Section');
    });
});
