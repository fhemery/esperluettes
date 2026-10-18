<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
    app()->setLocale('fr');
});

function storyPage($story)
{
    return test()->get(route('stories.show', ['slug' => $story->slug]));
}

it('renders no tab bar and no fanart button for a guest and a non-confirmed user', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $markers = ['data-fanart-tabs', 'fanart-viewer-modal', 'data-fanart-actions', __('fanart::ui.actions.submit'), __('fanart::ui.role_request.request')];

    $html = storyPage($story)->assertOk()->getContent();
    foreach ($markers as $marker) {
        expect($html)->not->toContain($marker);
    }

    $this->actingAs(bob($this, roles: [Roles::USER]));
    $html = storyPage($story)->assertOk()->getContent();
    foreach ($markers as $marker) {
        expect($html)->not->toContain($marker);
    }
});

it('renders the tab bar and Proposer un fanart for a confirmed reader when allowed', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs(bob($this));
    storyPage($story)->assertOk()
        ->assertSee('data-fanart-tabs', false)
        ->assertSee(route('fanart.tab', $story->slug), false)
        ->assertSee(__('fanart::ui.actions.submit'))
        ->assertSee(route('fanart.create', $story->slug), false)
        ->assertSee('fanart-viewer-modal', false)
        ->assertDontSee(__('fanart::ui.actions.artwork'));
});

it('renders Ajouter un artwork for an author and no Proposer button', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs($author);
    storyPage($story)->assertOk()
        ->assertSee('data-fanart-tabs', false)
        ->assertSee(__('fanart::ui.actions.artwork'))
        ->assertDontSee(__('fanart::ui.actions.submit'));
});

it('renders Demander le rôle de fanartiste when the mode is designated and the reader lacks the role', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'designated');

    $this->actingAs(bob($this));
    storyPage($story)->assertOk()
        ->assertSee(__('fanart::ui.role_request.request'))
        ->assertDontSee(__('fanart::ui.actions.submit'));
});

it('renders no submission button on a private story', function () {
    $author = alice($this);
    $story = privateStory('Secrète', $author->id);
    $beta = bob($this);
    addCollaborator($story->id, $beta->id, 'beta-reader');

    $this->actingAs($beta);
    storyPage($story)->assertOk()
        ->assertDontSee(__('fanart::ui.actions.submit'))
        ->assertDontSee(__('fanart::ui.actions.artwork'));

    $this->actingAs($author);
    storyPage($story)->assertOk()->assertDontSee(__('fanart::ui.actions.artwork'));
});
