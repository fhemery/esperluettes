<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Story;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StoryQueryFilterDto;
use App\Domains\Story\Public\Contracts\StoryQueryPaginationDto;
use App\Domains\Story\Public\Contracts\StoryQueryFieldsToReturnDto;
use App\Domains\Story\Public\Contracts\StoryQueryReadStatus;
use App\Domains\Story\Public\Contracts\PaginatedStoryDto;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryPublicApi::listStories', function () {
    beforeEach(function () {
        $this->api = app(StoryPublicApi::class);
    });

    it('returns empty when no story match', function () {
        $filter = new StoryQueryFilterDto(
            onlyStoryIds: [999999],
            readStatus: StoryQueryReadStatus::All,
            filterByGenreIds: []
        );

        $result = $this->api->listStories($filter);

        expect($result)->toBeInstanceOf(PaginatedStoryDto::class);
        expect($result->data)->toBeArray()->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
        expect($result->pagination->last_page)->toBe(1);
    });

    describe('Returning data', function () {
        describe('Basic info', function () {
            it('returns basic info for all stories (basic, no filters)', function () {
                // Create two public stories
                $s1 = publicStory('Alpha', alice($this)->id, ['description' => 'Story Alpha']);
                $s2 = publicStory('Beta', alice($this)->id);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                expect($result->data)->toBeArray();
                // Collect titles and ids
                $titles = array_map(fn($d) => $d->title, $result->data);
                $ids = array_map(fn($d) => $d->id, $result->data);
                $descriptions = array_map(fn($d) => $d->description, $result->data);

                expect($titles)->toContain('Alpha');
                expect($titles)->toContain('Beta');
                expect($ids)->toContain($s1->id);
                expect($ids)->toContain($s2->id);
                expect($descriptions)->toContain($s1->description);
            });
        });

        describe('about genres', function () {
            it('excludes genres by default', function () {
                publicStory('With Meta', alice($this)->id);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                $dto = $result->data[0];
                expect($dto->genres)->toBeNull();
                expect($dto->triggerWarnings)->toBeNull();
            });

            it('returns mapped genres when requested', function () {
                $g1 = makeGenre('Horror');
                $g2 = makeGenre('Romance');
                publicStory('With Genres', alice($this)->id, [
                    'story_ref_genre_ids' => [$g1->id, $g2->id],
                ]);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeGenreIds: true,
                );

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                expect($result->data)->not()->toBeEmpty();
                $dto = $result->data[0];
                expect($dto->genres)->toBeArray();
                $horror = collect($dto->genres)->firstWhere(fn($g) => $g['slug'] === $g1->slug);
                expect($horror)->not()->toBeNull();
                expect($horror['name'])->toBe($g1->name);
                $romance = collect($dto->genres)->firstWhere(fn($g) => $g['slug'] === $g2->slug);
                expect($romance)->not()->toBeNull();
                expect($romance['name'])->toBe($g2->name);
            });
        });

        describe('About trigger warnings', function () {
            it('always return tw disclosure', function () {
                publicStory('With Meta', alice($this)->id, [
                    'tw_disclosure' => Story::TW_UNSPOILED,
                ]);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                $dto = $result->data[0];
                expect($dto->twDisclosure)->toBe(Story::TW_UNSPOILED);
            });

            it('excludes trigger warnings by default', function () {
                publicStory('With Meta', alice($this)->id);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                $dto = $result->data[0];
                expect($dto->triggerWarnings)->toBeNull();
            });


            it('returns mapped trigger warnings when requested and includes tw_disclosure', function () {
                $tw1 = makeTriggerWarning('Blood');
                $tw2 = makeTriggerWarning('Death');
                $story = publicStory('With TWs', alice($this)->id, [
                    'story_ref_trigger_warning_ids' => [$tw1->id, $tw2->id],
                ]);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeTriggerWarningIds: true,
                );

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                expect($result->data)->toHaveCount(1);

                $dto = $result->data[0];
                expect($dto->triggerWarnings)->toBeArray();
                $twSlugs = array_map(fn($tw) => $tw['slug'], $dto->triggerWarnings);
                expect($twSlugs)->toContain($tw1->slug);
                expect($twSlugs)->toContain($tw2->slug);
            });
        });

        describe('About authors', function () {
            it('excludes authors by default', function () {
                publicStory('With Author', alice($this)->id);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                $dto = $result->data[0];
                expect($dto->authors)->toBeNull();
            });

            it('returns mapped authors when requested', function () {
                $author1 = alice($this);
                $story = publicStory('With Authors', alice($this)->id);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeAuthors: true,
                );

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                expect($result->data)->toHaveCount(1);

                $dto = $result->data[0];
                expect($dto->authors)->toHaveCount(1);
                $author = $dto->authors[0];
                expect($author->user_id)->toBe($author1->id);
                expect($author->display_name)->toBe('Alice');
                expect($author->slug)->toBe('alice');
                expect($author->avatar_url)->not()->toBeNull();
            });
        });
    });

    describe('Regarding pagination', function () {
        it('paginates results: page 1 of 2 with pageSize=2 over 3 stories', function () {
            $api = app(StoryPublicApi::class);

            // Create three public stories
            publicStory('S1', alice($this)->id);
            publicStory('S2', alice($this)->id);
            publicStory('S3', alice($this)->id);

            $filter = new StoryQueryFilterDto();
            $pagination = new StoryQueryPaginationDto(page: 1, pageSize: 2);
            $fields = new StoryQueryFieldsToReturnDto();

            $result = $api->listStories($filter, $pagination, $fields);

            expect($result->pagination->current_page)->toBe(1);
            expect($result->pagination->per_page)->toBe(2);
            expect($result->pagination->total)->toBe(3);
            expect($result->pagination->last_page)->toBe(2);
            expect(count($result->data))->toBe(2);
        });

        it('paginates results: page 2 of 2 with pageSize=2 over 3 stories', function () {
            $api = app(StoryPublicApi::class);

            // Create three public stories
            publicStory('T1', alice($this)->id);
            publicStory('T2', alice($this)->id);
            publicStory('T3', alice($this)->id);

            $filter = new StoryQueryFilterDto();
            $pagination = new StoryQueryPaginationDto(page: 2, pageSize: 2);
            $fields = new StoryQueryFieldsToReturnDto();

            $result = $api->listStories($filter, $pagination, $fields);

            expect($result->pagination->current_page)->toBe(2);
            expect($result->pagination->per_page)->toBe(2);
            expect($result->pagination->total)->toBe(3);
            expect($result->pagination->last_page)->toBe(2);
            expect(count($result->data))->toBe(1);
        });
    });
});
