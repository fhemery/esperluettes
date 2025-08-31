<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows an author to hard delete their story and then show returns 404', function () {
    $author = alice($this);
    $story = publicStory('My Story', $author->id);

    // Act
    $this->actingAs($author)->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(route('stories.index'))
        ->assertSessionHas('status');

    // Assert via controller: show should now 404
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertNotFound();
});

it('returns 404 to non-author attempting to delete; show still works', function () {
    $author = alice($this);
    $intruder = bob($this);
    $story = publicStory('Other Story', $author->id);

    $this->actingAs($intruder)
        ->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertNotFound();

    // Story still visible (public)
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertOk()
        ->assertSee('Other Story');
});

it('redirects guest to login on delete; show still works', function () {
    $author = alice($this);
    $story = publicStory('Login Story', $author->id);

    $this->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(); // Typically to login

    // Story still visible (public)
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertOk()
        ->assertSee('Login Story');
});
