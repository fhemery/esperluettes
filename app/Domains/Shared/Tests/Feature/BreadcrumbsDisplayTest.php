<?php

declare(strict_types=1);

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
});
