<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Support\FanartKind;
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

it('renders a speaker icon only on chapters with a published audio, for confirmed users', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $withAudio = createPublishedChapter($this, $story, $author, ['title' => 'Avec audio']);
    $without = createPublishedChapter($this, $story, $author, ['title' => 'Sans audio']);
    $pendingOnly = createPublishedChapter($this, $story, $author, ['title' => 'Audio en attente']);
    $audio = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $withAudio->id, 'kind' => FanartKind::Audio, 'file_path' => 'fanart/1/a.mp3']);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $pendingOnly->id, 'kind' => FanartKind::Audio, 'status' => \App\Domains\Fanart\Private\Support\FanartStatus::Pending, 'file_path' => 'fanart/1/b.mp3']);

    $this->actingAs(bob($this));
    $html = $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->getContent();

    expect($html)->toContain('data-fanart-player');
    expect($html)->toContain('controlsList="nodownload')->toContain('oncontextmenu');
    expect($html)->toContain(route('fanart.file', $audio->id));
    expect($html)->toContain(route('chapters.read.mark', ['storySlug' => $story->slug, 'chapterSlug' => $withAudio->slug]));
    expect(substr_count($html, 'data-chapter-audio-icon'))->toBe(1);
    expect($html)->toContain('data-chapter-audio-icon="' . $withAudio->id . '"');
});

it('omits audio of unpublished chapters for readers', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $draft = createUnpublishedChapter($this, $story, $author, ['title' => 'Brouillon']);
    $audio = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $draft->id, 'kind' => FanartKind::Audio, 'file_path' => 'fanart/1/a.mp3']);

    $this->actingAs(bob($this));
    $html = $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio-icon');
    expect($html)->not->toContain('data-fanart-player')
        ->not->toContain(route('fanart.file', $audio->id));

    $this->actingAs($author);
    $html = $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->getContent();
    expect($html)->toContain('data-chapter-audio-icon="' . $draft->id . '"');
});

it('renders no icon and no player for a guest', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id, 'kind' => FanartKind::Audio, 'file_path' => 'fanart/1/a.mp3']);

    auth()->logout(); // creating the chapter acted as the author
    $html = $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio-icon')->not->toContain('data-fanart-player');

    $this->actingAs(bob($this, roles: [Roles::USER]));
    $html = $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio-icon')->not->toContain('data-fanart-player');
});
