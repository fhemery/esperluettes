<?php

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function getStoryViaApi(int $storyId): \App\Domains\Story\Public\Contracts\StorySummaryDto
{
    $dto = app(StoryPublicApi::class)->getStory($storyId);
    expect($dto)->not->toBeNull();
    return $dto;
}

describe('Story cover selection', function () {

    describe('Create with cover', function () {

        it('creates a story with default cover when no cover fields provided', function () {
            $user = alice($this);
            $this->actingAs($user);

            $payload = validStoryPayload(['title' => 'Default Cover Story']);
            $this->post('/stories', $payload)->assertRedirect();

            $story = Story::query()->latest('id')->firstOrFail();
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
            expect($dto->cover_url)->toContain('default-cover.svg');
        });

        it('creates a story with explicit default cover type', function () {
            $user = alice($this);
            $this->actingAs($user);

            $payload = validStoryPayload([
                'title' => 'Explicit Default',
                'cover_type' => 'default',
                'cover_data' => '',
            ]);
            $this->post('/stories', $payload)->assertRedirect();

            $story = Story::query()->latest('id')->firstOrFail();
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
            expect($dto->cover_url)->toContain('default-cover.svg');
        });

        it('creates a story with themed cover type', function () {
            $user = alice($this);
            $this->actingAs($user);

            $genre = makeRefGenre('Fantasy');

            $payload = validStoryPayload([
                'title' => 'Themed Cover Story',
                'story_ref_genre_ids' => [$genre->id],
                'cover_type' => 'themed',
                'cover_data' => 'fantasy',
            ]);
            $this->post('/stories', $payload)->assertRedirect();

            $story = Story::query()->latest('id')->firstOrFail();
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('themed');
            expect($dto->cover_url)->toContain('fantasy.jpg');
        });

        it('clears cover_data when cover_type is default even if cover_data is provided', function () {
            $user = alice($this);
            $this->actingAs($user);

            $payload = validStoryPayload([
                'title' => 'Default Clears Data',
                'cover_type' => 'default',
                'cover_data' => 'should-be-cleared',
            ]);
            $this->post('/stories', $payload)->assertRedirect();

            $story = Story::query()->latest('id')->firstOrFail();
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
            expect($dto->cover_url)->toContain('default-cover.svg');
        });
    });

    describe('Update cover', function () {

        it('updates a story from default to themed cover', function () {
            $author = alice($this);
            $this->actingAs($author);

            $genre = makeRefGenre('Romance');
            $story = publicStory('Cover Update Test', $author->id, [
                'story_ref_genre_ids' => [$genre->id],
            ]);

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');

            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Cover Update Test',
                'story_ref_genre_ids' => [$genre->id],
                'cover_type' => 'themed',
                'cover_data' => 'romance',
            ]))->assertRedirect();

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('themed');
            expect($dto->cover_url)->toContain('romance.jpg');
        });

        it('updates a story from themed back to default cover', function () {
            $author = alice($this);
            $this->actingAs($author);

            $genre = makeRefGenre('Sci-Fi');
            $story = publicStory('Revert Cover Test', $author->id, [
                'story_ref_genre_ids' => [$genre->id],
            ]);

            // First set to themed
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Revert Cover Test',
                'story_ref_genre_ids' => [$genre->id],
                'cover_type' => 'themed',
                'cover_data' => 'sci-fi',
            ]))->assertRedirect();

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('themed');

            // Then revert to default
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Revert Cover Test',
                'story_ref_genre_ids' => [$genre->id],
                'cover_type' => 'default',
                'cover_data' => '',
            ]))->assertRedirect();

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
            expect($dto->cover_url)->toContain('default-cover.svg');
        });
    });

    describe('Genre removal resets themed cover', function () {

        it('resets themed cover to default when the themed genre is removed on update', function () {
            $author = alice($this);
            $this->actingAs($author);

            $genreFantasy = makeRefGenre('Fantasy');
            $genreMystery = makeRefGenre('Mystery');

            // Create story with both genres and themed cover on fantasy
            $story = publicStory('Genre Removal Test', $author->id, [
                'story_ref_genre_ids' => [$genreFantasy->id, $genreMystery->id],
            ]);
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Genre Removal Test',
                'story_ref_genre_ids' => [$genreFantasy->id, $genreMystery->id],
                'cover_type' => 'themed',
                'cover_data' => 'fantasy',
            ]))->assertRedirect();

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('themed');
            expect($dto->cover_url)->toContain('fantasy.jpg');

            // Now remove fantasy genre, keeping only mystery, but still sending themed cover with fantasy slug
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Genre Removal Test',
                'story_ref_genre_ids' => [$genreMystery->id],
                'cover_type' => 'themed',
                'cover_data' => 'fantasy',
            ]))->assertRedirect();

            // Should have fallen back to default since fantasy genre was removed
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
            expect($dto->cover_url)->toContain('default-cover.svg');
        });

        it('keeps themed cover when the themed genre is still selected', function () {
            $author = alice($this);
            $this->actingAs($author);

            $genreFantasy = makeRefGenre('Fantasy');
            $genreMystery = makeRefGenre('Mystery');

            $story = publicStory('Genre Keep Test', $author->id, [
                'story_ref_genre_ids' => [$genreFantasy->id, $genreMystery->id],
            ]);

            // Set themed cover
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Genre Keep Test',
                'story_ref_genre_ids' => [$genreFantasy->id, $genreMystery->id],
                'cover_type' => 'themed',
                'cover_data' => 'fantasy',
            ]))->assertRedirect();

            // Update but keep both genres
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Genre Keep Test Updated',
                'story_ref_genre_ids' => [$genreFantasy->id, $genreMystery->id],
                'cover_type' => 'themed',
                'cover_data' => 'fantasy',
            ]))->assertRedirect();

            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('themed');
            expect($dto->cover_url)->toContain('fantasy.jpg');
        });
    });

    describe('Validation', function () {

        it('rejects invalid cover_type', function () {
            $user = alice($this);

            $payload = validStoryPayload([
                'cover_type' => 'invalid_type',
            ]);

            $resp = $this->actingAs($user)
                ->from('/stories/create')
                ->post('/stories', $payload);

            $resp->assertRedirect('/stories/create');
            $resp->assertSessionHasErrors('cover_type');
        });

        it('accepts null cover_type (defaults to default)', function () {
            $user = alice($this);
            $this->actingAs($user);

            $payload = validStoryPayload([
                'title' => 'Null Cover Type',
                'cover_type' => null,
            ]);

            $this->post('/stories', $payload)->assertRedirect();

            $story = Story::query()->latest('id')->firstOrFail();
            $dto = getStoryViaApi($story->id);
            expect($dto->cover_type)->toBe('default');
        });
    });
});
