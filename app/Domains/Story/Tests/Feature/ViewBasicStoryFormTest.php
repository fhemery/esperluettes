<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows basic story form fields to authenticated users', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();
    // Assert by translation keys to be locale-agnostic
    $response->assertSee(trans('story::create.form.title.label'));
    $response->assertSee(trans('story::shared.description.label'));
    $response->assertSee(trans('story::shared.visibility.label'));
    $response->assertSee(trans('story::shared.visibility.help'), false); // allow HTML
    $response->assertSee(trans('story::shared.visibility.options.public'));
    $response->assertSee(trans('story::shared.visibility.options.community'));
    $response->assertSee(trans('story::shared.visibility.options.private'));
});

it('shows story types details to authenticated used', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Type field (label, placeholder/help, required note)
    $response->assertSee(trans('story::shared.type.label'));
    $response->assertSee(trans('story::shared.type.placeholder'));
    $response->assertSee(trans('story::shared.type.help'));
    $response->assertSee(trans('story::shared.required'));
    $response->assertSee(trans('story::create.actions.continue'));
});
