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

it('shows story audience details to authenticated user', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Audience field (label, placeholder/help, required note)
    $response->assertSee(trans('story::shared.audience.label'));
    $response->assertSee(trans('story::shared.audience.placeholder'));
    $response->assertSee(trans('story::shared.audience.help'));
    $response->assertSee(trans('story::shared.required'));
});

it('shows story copyright details to authenticated user', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Copyright field (label, placeholder/help, required note)
    $response->assertSee(trans('story::shared.copyright.label'));
    $response->assertSee(trans('story::shared.copyright.placeholder'));
    $response->assertSee(trans('story::shared.copyright.help'));
    $response->assertSee(trans('story::shared.required'));
});
