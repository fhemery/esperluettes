<?php

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

it('renders the story tab, viewer and create page without missing translation keys', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'yes');
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Premier']);
    $pending = createPendingAudio(['story_id' => $story->id, 'user_id' => bob($this)->id, 'chapter_id' => $chapter->id]);
    createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => null]);

    $pages = [
        route('fanart.tab', $story->slug),
        route('fanart.show', $pending->id),
        route('fanart.create', [$story->slug, 'image']),
        route('stories.show', $story->slug),
    ];

    foreach ($pages as $url) {
        $html = $this->actingAs($author)->get($url)->assertOk()->getContent();
        expect($html)->not->toContain('fanart::');
    }

    $this->actingAs($author)->get(route('stories.show', $story->slug))
        ->assertSee('role="tablist"', false)
        ->assertSee('role="tabpanel"', false)
        ->assertSee(__('fanart::ui.actions.artwork_image'));
    $this->actingAs(bob($this))->get(route('stories.show', $story->slug))
        ->assertSee(__('fanart::ui.actions.submit_image'));
});
