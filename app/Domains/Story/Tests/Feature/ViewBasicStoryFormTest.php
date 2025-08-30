<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows basic story form fields to authenticated users', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();
    // Assert by translation keys to be locale-agnostic
    $response->assertSee('story::create.form.title.label');
    $response->assertSee('story::shared.description.label');
    $response->assertSee('story::shared.visibility.label');
    $response->assertSee('story::shared.visibility.help', false); // allow HTML
    $response->assertSee('story::shared.visibility.options.public');
    $response->assertSee('story::shared.visibility.options.community');
    $response->assertSee('story::shared.visibility.options.private');
});

it('shows story types details to authenticated used', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Type field (label, placeholder/help, required note)
    $response->assertSee('story::shared.type.label');
    $response->assertSee('story::shared.type.placeholder');
    $response->assertSee('story::shared.type.help');
    $response->assertSee('story::create.actions.continue');
});

it('shows story audience details to authenticated user', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Audience field (label, placeholder/help, required note)
    $response->assertSee('story::shared.audience.label');
    $response->assertSee('story::shared.audience.placeholder');
    $response->assertSee('story::shared.audience.help');
});

it('shows story copyright details to authenticated user', function () {
    $user = alice($this);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();

    // Copyright field (label, placeholder/help, required note)
    $response->assertSee('story::shared.copyright.label');
    $response->assertSee('story::shared.copyright.placeholder');
    $response->assertSee('story::shared.copyright.help');
});
