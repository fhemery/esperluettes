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

function chapterWithFanarts($test): array
{
    $author = alice($test);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($test, $story, $author, ['title' => 'Chapitre un']);
    $audio = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id, 'kind' => FanartKind::Audio, 'title' => 'Lecture', 'file_path' => 'fanart/1/a.mp3']);
    $image = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id, 'kind' => FanartKind::Image, 'title' => 'Dessin', 'file_path' => 'fanart/1/i.jpg']);

    return [$story, $chapter, $audio, $image];
}

it('shows the audio player under the title for a confirmed reader', function () {
    [$story, $chapter, $audio] = chapterWithFanarts($this);

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();

    expect($html)->toContain('controlsList="nodownload')->toContain('oncontextmenu');
    expect($html)->toContain('data-chapter-audio')
        ->toContain(route('fanart.file', $audio->id));
    expect(strpos($html, 'data-chapter-audio'))->toBeGreaterThan(strpos($html, 'Chapitre un'))
        ->toBeLessThan(strpos($html, 'data-quote-article'));
});

it('shows the image block at the bottom, without delete or review controls', function () {
    [$story, $chapter, , $image] = chapterWithFanarts($this);

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();

    expect($html)->toContain('data-chapter-images')
        ->toContain('data-fanart-id="' . $image->id . '"')
        ->toContain('fanart-viewer-modal')
        ->not->toContain('_method')
        ->not->toContain(route('fanart.accept', $image->id));
    expect(strpos($html, 'data-chapter-images'))->toBeGreaterThan(strpos($html, 'data-quote-article'))
        ->toBeLessThan(strpos($html, 'id="comments"'));
});

it('shows nothing to a guest and a non-confirmed user', function () {
    [$story, $chapter, $audio] = chapterWithFanarts($this);
    $url = route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]);

    auth()->logout();
    $html = $this->get($url)->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio')->not->toContain('data-chapter-images')
        ->not->toContain(route('fanart.file', $audio->id));

    $this->actingAs(bob($this, roles: [Roles::USER]));
    $html = $this->get($url)->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio')->not->toContain('data-chapter-images')
        ->not->toContain(route('fanart.file', $audio->id));
});

it('shows nothing when the chapter has no published fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();
    expect($html)->not->toContain('data-chapter-audio')->not->toContain('data-chapter-images');
});

it('keeps a single [data-quote-article] root in the chapter page', function () {
    [$story, $chapter] = chapterWithFanarts($this);

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();

    expect(substr_count($html, 'data-quote-article'))->toBe(1);
});

it('shows the artist icon next to the chapter audio player', function () {
    [$story, $chapter, $audio] = chapterWithFanarts($this);
    $profile = app(\App\Domains\Shared\Contracts\ProfilePublicApi::class)->getPublicProfile($audio->user_id);

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();

    expect($html)->toContain('data-artist-link')
        ->toContain('href="' . route('profile.show', ['profile' => $profile->slug]) . '"')
        ->toContain('title="Par ' . e($profile->display_name) . '"')
        ->toContain('aria-label="Par ' . e($profile->display_name) . '"');
});

it('omits the artist icon when the artist profile is gone', function () {
    [$story, $chapter, $audio] = chapterWithFanarts($this);
    \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $audio->user_id)->delete();
    \Illuminate\Support\Facades\Cache::flush();

    $this->actingAs(bob($this));
    $html = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
        ->assertOk()->getContent();

    expect($html)->toContain('data-chapter-audio')->not->toContain('data-artist-link');
});
