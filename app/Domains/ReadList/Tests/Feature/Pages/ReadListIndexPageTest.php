<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadList page', function () {
    it('requires authentication', function () {
        $this->get(route('readlist.index'))
            ->assertRedirect(route('login'));
    });

    it('renders the ReadList title for authenticated users', function () {
        $user = alice($this);
        $this->actingAs($user);

        $this->get(route('readlist.index'))
            ->assertOk()
            ->assertSee(__('readlist::page.title'));
    });
});
