<?php

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows basic story form fields to authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();
    // Assert by translation keys to be locale-agnostic
    $response->assertSee(trans('story::create.form.title.label'));
    $response->assertSee(trans('story::create.form.description.label'));
    $response->assertSee(trans('story::create.actions.continue'));
});
