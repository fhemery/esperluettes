<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StoryQueryFilterDto;
use App\Domains\Story\Public\Contracts\StoryQueryPaginationDto;
use App\Domains\Story\Public\Contracts\StoryQueryFieldsToReturnDto;
use App\Domains\Story\Public\Contracts\StoryQueryReadStatus;
use App\Domains\Story\Public\Contracts\PaginatedStoryDto;
use App\Domains\Story\Public\Contracts\StoryVisibility;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryPublicApi::listStories', function () {
    beforeEach(function () {
        $this->api = app(StoryPublicApi::class);
    });

    it('returns empty when no story match', function () {
        $filter = new StoryQueryFilterDto(
            onlyStoryIds: [999999],
            readStatus: StoryQueryReadStatus::All,
            genreIds: []
        );

        $result = $this->api->listStories($filter);

        expect($result)->toBeInstanceOf(PaginatedStoryDto::class);
        expect($result->data)->toBeArray()->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
        expect($result->pagination->last_page)->toBe(1);
    });

    describe('Applying business rules', function () {

        describe('by default', function () {
            it('should filter community stories if user is not confirmed', function () {
                publicStory('Alpha', alice($this)->id);
                communityStory('Beta', alice($this)->id);

                $this->actingAs(bob($this, roles: [Roles::USER]));
                $result = $this->api->listStories();

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(1);
                expect($result->data[0]->title)->toBe('Alpha');
            });

            it('should display community stories if user is confirmed', function () {
                publicStory('Alpha', alice($this)->id);
                communityStory('Beta', alice($this)->id);

                $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
                $result = $this->api->listStories();

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(2);
                expect(collect($result->data)->pluck('title'))->toContain('Alpha');
                expect(collect($result->data)->pluck('title'))->toContain('Beta');
            });

            it('should not display private stories for which user is not collaborator', function () {
                privateStory('Beta', alice($this)->id);

                $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
                $result = $this->api->listStories();

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(0);
            });

            it('should display private stories for which user is collaborator', function () {
                $aliceStory = privateStory('Alpha', alice($this)->id);
                privateStory('Beta', bob($this)->id);

                addCollaborator($aliceStory->id, bob($this)->id, 'betareader');

                $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
                $result = $this->api->listStories();

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(2);
            });
        });

        describe('When filtering explicitly', function () {
            it('should only display required visibilities', function () {
                $alice = alice($this);
                publicStory('Alpha', $alice->id);
                communityStory('Beta', $alice->id);
                privateStory('Gamma', $alice->id);

                $this->actingAs($alice);
                $filter = new StoryQueryFilterDto(visibilities: [StoryVisibility::PUBLIC]);

                $result = $this->api->listStories($filter);

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(1);
                expect(collect($result->data)->pluck('title'))->toContain('Alpha');
            });

            it('should not override the business rules, such as community visibility', function () {
                $alice = alice($this);
                publicStory('Alpha', $alice->id);
                communityStory('Beta', $alice->id);
                privateStory('Gamma', $alice->id);

                // Bob is not confirmed, so he sees nothing if he excludes Public
                $this->actingAs(bob($this, roles: [Roles::USER]));
                $filter = new StoryQueryFilterDto(visibilities: [StoryVisibility::COMMUNITY, StoryVisibility::PRIVATE]);

                $result = $this->api->listStories($filter);

                expect($result->data)->toBeArray();
                expect($result->data)->toHaveCount(0);
            });
        });
    });

    describe('Sorting stories', function () {
        it('should sort stories by descending last modified date', function () {
            $alice = alice($this);
            $story1 = publicStory('Beta', $alice->id);
            Story::where('id', $story1->id)->update(['last_chapter_published_at' => now()->subDays(3)]);
            $story2 = publicStory('Alpha', $alice->id);
            Story::where('id', $story2->id)->update(['last_chapter_published_at' => now()->subDays(2)]);

            /** @var PaginatedStoryDto $result */
            $result = $this->api->listStories();

            expect($result->data)->toHaveCount(2);
            expect($result->data[0]->title)->toBe($story2->title);
            expect($result->data[1]->title)->toBe($story1->title);
        });

        it('should use created_at instead of last_chapter_published_at when last_chapter_published_at is null', function () {
            $alice = alice($this);
            $story1 = publicStory('Beta', $alice->id);
            Story::where('id', $story1->id)->update(['created_at' => now()->subDays(1)]);
            $story2 = publicStory('Alpha', $alice->id);
            Story::where('id', $story2->id)->update(['last_chapter_published_at' => now()->subDays(2)]);

            /** @var PaginatedStoryDto $result */
            $result = $this->api->listStories();

            expect($result->data)->toHaveCount(2);
            expect($result->data[0]->title)->toBe($story1->title);
            expect($result->data[1]->title)->toBe($story2->title);
        });
    });

    describe('Filtering stories', function() {
        describe('Regarding filtering by storyIds', function () {
            it('should return only the stories with the given ids', function () {
                $alice = alice($this);
                $story1 = publicStory('With Chapters', $alice->id);
                $story2 = publicStory('With Chapters', $alice->id);
                $story3 = publicStory('With Chapters', $alice->id);

                $filter = new StoryQueryFilterDto(onlyStoryIds: [$story1->id, $story2->id]);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter);

                expect($result->data)->toHaveCount(2);
                expect(collect($result->data)->pluck('id'))->toContain($story1->id);
                expect(collect($result->data)->pluck('id'))->toContain($story2->id);
            });
        });

        describe('regarding Read status filtering', function () {
            it('should return stories that have an unread chapter when Read Status is UnreadOnly', function () {
                $alice = alice($this);
                $readStory = publicStory('All read by Bob', $alice->id);
                $readChapter = createPublishedChapter($this, $readStory, $alice);
                $emptyStory = publicStory('Empty story', $alice->id);
                $unreadStory = publicStory('Unread story', $alice->id);
                $unreadChapter = createPublishedChapter($this, $unreadStory, $alice);

                $filter = new StoryQueryFilterDto(
                    readStatus: StoryQueryReadStatus::UnreadOnly,
                );

                $this->actingAs(bob($this));
                markAsRead($this, $readChapter);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter);

                expect($result->data)->toHaveCount(1);
                $dto = $result->data[0];
                expect($dto->id)->toBe($unreadStory->id);
            });

            it('should discard unpublished chapters from the count (for unread)', function () {
                $alice = alice($this);
                $readStory = publicStory('All read by Bob', $alice->id);
                $readChapter = createPublishedChapter($this, $readStory, $alice);
                $unreadChapter = createUnpublishedChapter($this, $readStory, $alice);

                $filter = new StoryQueryFilterDto(
                    readStatus: StoryQueryReadStatus::UnreadOnly,
                );

                $this->actingAs(bob($this));
                markAsRead($this, $readChapter);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter);

                expect($result->data)->toHaveCount(0);
            });

            it('should consider all other stories as READ', function () {
                $alice = alice($this);
                $readStory = publicStory('All read by Bob', $alice->id);
                $readChapter = createPublishedChapter($this, $readStory, $alice);
                $emptyStory = publicStory('Empty story', $alice->id);
                $unreadStory = publicStory('Unread story', $alice->id);
                $unreadChapter = createPublishedChapter($this, $unreadStory, $alice);

                $filter = new StoryQueryFilterDto(
                    readStatus: StoryQueryReadStatus::ReadOnly,
                );
                $fields = new StoryQueryFieldsToReturnDto(
                    includeChapters: true,
                    includeReadingProgress: true,
                );

                $this->actingAs(bob($this));
                markAsRead($this, $readChapter);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter, fieldsToReturn: $fields);

                expect($result->data)->toHaveCount(2);
                expect(collect($result->data)->pluck('id'))->toContain($readStory->id);
                expect(collect($result->data)->pluck('id'))->toContain($emptyStory->id);
            });

            it('should discard unpublished chapters from the count (for read)', function () {
                $alice = alice($this);
                $readStory = publicStory('All read by Bob', $alice->id);
                $readChapter = createPublishedChapter($this, $readStory, $alice);
                $unreadChapter = createUnpublishedChapter($this, $readStory, $alice);

                $filter = new StoryQueryFilterDto(
                    readStatus: StoryQueryReadStatus::ReadOnly,
                );

                $this->actingAs(bob($this));
                markAsRead($this, $readChapter);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter);

                // Chapter is not read, story should not be returned
                // But because the chapter is unpublished, it is discarded
                expect($result->data)->toHaveCount(1);
                expect(collect($result->data)->pluck('id'))->toContain($readStory->id);
            });
        });

        describe('filtering by genres', function () {
            it('should return stories that have all the specified genres', function () {
                $alice = alice($this);
                $genre1 = makeGenre('Genre 1');
                $genre2 = makeGenre('Genre 2');
                
                $story1 = publicStory('With Genres', $alice->id, ['story_ref_genre_ids' => [$genre1->id, $genre2->id]]);
                $story2 = publicStory('Without Genres', $alice->id);
                $story3 = publicStory('With One Genre', $alice->id, ['story_ref_genre_ids' => [$genre1->id]]);

                $filter = new StoryQueryFilterDto(
                    genreIds: [$genre1->id, $genre2->id],
                );

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories($filter);

                expect($result->data)->toHaveCount(1);
                expect(collect($result->data)->pluck('id'))->toContain($story1->id);
            });
        });
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
                $slugs = array_map(fn($d) => $d->slug, $result->data);

                expect($titles)->toContain('Alpha');
                expect($titles)->toContain('Beta');
                expect($ids)->toContain($s1->id);
                expect($ids)->toContain($s2->id);
                expect($descriptions)->toContain($s1->description);
                expect($slugs)->toContain($s1->slug);
                expect($slugs)->toContain($s2->slug);
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

        describe('About chapters', function () {
            it('excludes chapters by default', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                createPublishedChapter($this, $story, $alice);

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                $dto = $result->data[0];
                expect($dto->chapters)->toBeNull();
            });

            it('should return chapter, without content, if requested', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                createPublishedChapter($this, $story, $alice, [
                    'title' => 'Chapter 1',
                    'slug' => 'chapter-1',
                    'author_note' => 'Summary of Chapter 1',
                    'content' => 'Description of Chapter 1',
                    'word_count' => 100,
                ]);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeChapters: true,
                );

                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                $dto = $result->data[0];
                expect($dto->chapters)->toHaveCount(1);
                $chapter = $dto->chapters[0];
                expect($chapter->title)->toBe('Chapter 1');
                expect($chapter->slug)->toBe('chapter-1');
                expect($chapter->wordCount)->toBe(4);
                // Additional lightweight chapter metadata
                expect($chapter->status)->toBe(\App\Domains\Story\Private\Models\Chapter::STATUS_PUBLISHED);
                expect($chapter->sortOrder)->toBeInt();
                expect($chapter->firstPublishedAt)->not()->toBeNull();
                expect($chapter->readsLoggedCount)->toBeInt();
                expect($chapter->characterCount)->toBeInt();
            });

            it('should not return unpublished chapters to non-authors', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                createPublishedChapter($this, $story, $alice, [
                    'title' => 'Chapter 1',
                ]);
                createUnpublishedChapter($this, $story, $alice, [
                    'title' => 'Chapter 2',
                ]);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeChapters: true,
                );

                $this->actingAs(bob($this));
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                $dto = $result->data[0];
                expect($dto->chapters)->toHaveCount(1);
                $chapter = $dto->chapters[0];
                expect($chapter->title)->toBe('Chapter 1');
            });
        });

        describe('Regarding reading progress', function () {
            it('should not return reading progress by default', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                $chapter = createPublishedChapter($this, $story, $alice);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeChapters: true,
                );

                $this->actingAs(bob($this));
                markAsRead($this, $chapter);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                $dto = $result->data[0];
                expect($dto->chapters[0]->isRead)->toBeNull();
            });

            it('should return reading progress if specified', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                $chapter1 = createPublishedChapter($this, $story, $alice);
                $chapter2 = createPublishedChapter($this, $story, $alice);

                $fields = new StoryQueryFieldsToReturnDto(
                    includeChapters: true,
                    includeReadingProgress: true
                );

                $this->actingAs(bob($this));
                markAsRead($this, $chapter1);
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories(fieldsToReturn: $fields);

                $dto = $result->data[0];
                $chap1Dto = collect($dto->chapters)->firstWhere(fn($c) => (int)$c->id === (int)$chapter1->id);
                $chap2Dto = collect($dto->chapters)->firstWhere(fn($c) => (int)$c->id === (int)$chapter2->id);
                expect($chap1Dto?->isRead)->toBe(true);
                expect($chap2Dto?->isRead)->toBe(false);
            });
        });
    });

    describe('About date fields', function () {
            it('returns created_at and last_chapter_published_at dates', function () {
                $story = publicStory('With Dates', alice($this)->id);
                
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                expect($result->data)->toHaveCount(1);
                $dto = $result->data[0];
                
                expect($dto->createdAt)->toBeInstanceOf(\DateTime::class);
                expect($dto->createdAt)->toEqual($story->created_at);
                expect($dto->lastChapterPublishedAt)->toBeNull(); // No chapters published yet
            });

            it('returns last_chapter_published_at when story has published chapters', function () {
                $alice = alice($this);
                $story = publicStory('With Chapters', $alice->id);
                $chapter = createPublishedChapter($this, $story, $alice);
                
                // Refresh story to get updated last_chapter_published_at
                $story->refresh();
                
                /** @var PaginatedStoryDto $result */
                $result = $this->api->listStories();

                expect($result->data)->toHaveCount(1);
                $dto = $result->data[0];
                
                expect($dto->createdAt)->toBeInstanceOf(\DateTime::class);
                expect($dto->lastChapterPublishedAt)->toBeInstanceOf(\DateTime::class);
                expect($dto->createdAt)->toEqual($story->created_at);
                expect($dto->lastChapterPublishedAt)->toEqual($story->last_chapter_published_at);
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
