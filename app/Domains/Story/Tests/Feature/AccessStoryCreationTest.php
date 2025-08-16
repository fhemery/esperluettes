<?php

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests to login when accessing /stories/create', function () {
    $response = $this->get('/stories/create');

    $response->assertRedirect('/login');
});

it('allows authenticated users to access the story creation page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();
    $response->assertSee(__('story::create.title'));
});
