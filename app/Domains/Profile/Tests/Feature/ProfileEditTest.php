<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);


it('redirects guests from profile edit route to login', function () {
    $this->get('/profile/edit')->assertRedirect('/login');
});


it('allows authenticated user with proper role to access edit page', function () {
    $userConfirmed = alice($this);
    $simpleUser = bob($this, roles: ['user']);

    $this->actingAs($simpleUser)
        ->get('/profile/edit')
        ->assertOk()
        ->assertSee('Bob');
    $this->actingAs($userConfirmed)
        ->get('/profile/edit')
        ->assertOk()
        ->assertSee('Alice');
});
