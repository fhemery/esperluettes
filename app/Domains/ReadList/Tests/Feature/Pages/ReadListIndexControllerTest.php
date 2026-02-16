<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use App\Domains\ReadList\Private\ViewModels\ReadListIndexViewModel;
use App\Domains\ReadList\Private\ViewModels\ReadListStoryViewModel;
use Illuminate\Support\Collection;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadListController index', function () {
    it('returns empty list when user has no stories in readlist', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create stories but DON'T add any to reader's readlist
        setUserCredits($author->id, 10);
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($author);
            $story = publicStory("Story {$i}", $author->id);
            createPublishedChapter($this, $story, $author);
        }

        // User has empty readlist
        $response = $this->actingAs($reader)->get(route('readlist.index'));
        $response->assertOk();

        $vm = $response->viewData('vm');
        expect($vm)->toBeInstanceOf(ReadListIndexViewModel::class);

        // Should have 0 stories since readlist is empty
        expect($vm->stories)->toBeInstanceOf(Collection::class);
        expect($vm->stories->count())->toBe(0);
        expect($vm->pagination->total)->toBe(0);
        expect($vm->pagination->last_page)->toBe(1);
    });

    it('loadMore returns empty result when user has no stories in readlist', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create stories but DON'T add any to reader's readlist
        setUserCredits($author->id, 10);
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($author);
            $story = publicStory("Story {$i}", $author->id);
            createPublishedChapter($this, $story, $author);
        }

        // Test loadMore endpoint with empty readlist
        $response = $this->actingAs($reader)
            ->getJson(route('readlist.load-more', [
                'page' => 1,
                'perPage' => 10
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'hasMore',
            'nextPage',
            'total'
        ]);

        $data = $response->json();
        expect($data['html'])->toBe('');
        expect($data['hasMore'])->toBeFalse();
        expect($data['total'])->toBe(0);
    });

    it('provides a view model with my readlist stories and pagination', function () {
        $author1 = alice($this);
        $author2 = alice($this);

        $story1 = publicStory('Story 1', $author1->id);
        $story2 = publicStory('Story 2', $author2->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Seed read list entries
        ReadListEntry::create(['user_id' => $reader->id, 'story_id' => $story1->id]);
        ReadListEntry::create(['user_id' => $reader->id, 'story_id' => $story2->id]);

        $response = $this->actingAs($reader)->get(route('readlist.index'));

        $response->assertOk();

        $vm = $response->viewData('vm');
        expect($vm)->toBeInstanceOf(ReadListIndexViewModel::class);
    });

    describe('Pagination', function () {
        it('shows correct pagination when user has 15 stories in readlist (1 chapter each)', function () {
            $author = alice($this);
            $reader = bob($this);

            // Create 15 public stories, each with a published chapter, and add to reader readlist
            setUserCredits($author->id, 100);
            for ($i = 1; $i <= 15; $i++) {
                $this->actingAs($author);
                $story = publicStory('RL Story #' . $i, $author->id);
                createPublishedChapter($this, $story, $author, ['title' => 'Ch ' . $i]);

                $this->actingAs($reader);
                addToReadList($this, $story->id);
            }

            // Page 1, expect per page default to 10, total=15, last_page=2
            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            $vm = $response->viewData('vm');
            expect($vm)->toBeInstanceOf(ReadListIndexViewModel::class);

            // Stories collection assertions (first page should have 10)
            expect($vm->stories)->toBeInstanceOf(Collection::class);
            expect($vm->stories->count())->toBe(10);

            // Pagination expectations (will fail until VM is implemented)
            expect(isset($vm->pagination))->toBeTrue();
            expect($vm->pagination->current_page)->toBe(1);
            expect($vm->pagination->per_page)->toBe(10);
            expect($vm->pagination->total)->toBe(15);
            expect($vm->pagination->last_page)->toBe(2);
        });
    });

    describe('Mapping', function () {
        it('maps a single story with authors, genres, trigger warnings and disclosure', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            // Create explicit genre and trigger warning
            $genre = makeRefGenre('Fantasy');
            $tw = makeRefTriggerWarning('Violence');
            // Prepare story attributes: description and disclosure, assign created refs
            $attrs = [
                'description' => '<p>Desc</p>',
                'tw_disclosure' => 'listed',
                'story_ref_genre_ids' => [$genre->id],
                'story_ref_trigger_warning_ids' => [$tw->id],
            ];

            // Ensure author has credits to create a published chapter
            setUserCredits($author->id, 5);

            // Create story, publish one chapter, and add to reader readlist
            $this->actingAs($author);
            $story = publicStory('Solo Story', $author->id, $attrs);
            createPublishedChapter($this, $story, $author, ['title' => 'Ch 1']);

            $this->actingAs($reader);
            addToReadList($this, $story->id);

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            expect($vm)->toBeInstanceOf(ReadListIndexViewModel::class);

            // Should have exactly 1 story on page 1 in this setup
            expect($vm->stories)->toBeInstanceOf(Collection::class);
            expect($vm->stories->count())->toBe(1);

            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();
            expect($s)->toBeInstanceOf(ReadListStoryViewModel::class);
            expect($s->id)->toBe((int) $story->id);
            expect($s->title)->toBe('Solo Story');
            expect($s->slug)->toBe((string) $story->slug);
            expect($s->description)->toBe('<p>Desc</p>');
            expect($s->twDisclosure)->toBe('listed');

            // Authors: array of ProfileDto
            expect($s->authors)->toBeArray();
            expect(count($s->authors))->toBeGreaterThan(0);
            $firstAuthor = $s->authors[0];
            expect($firstAuthor)->toBeInstanceOf(ProfileDto::class);
            expect($firstAuthor->display_name)->not->toBe('');

            // Genres and trigger warnings names include our created refs
            expect($s->genreNames)->toContain($genre->name);
            expect($s->triggerWarningNames)->toContain($tw->name);

            // Default cover
            expect($s->coverType)->toBe('default');
            expect($s->coverUrl)->toContain('images/story/default-cover.svg');
        });

        it('computes read/total chapters and progress percentage', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 10);

            // Create story with 4 published chapters
            $this->actingAs($author);
            $story = publicStory('Progress Story', $author->id, ['description' => '']);
            // Use specific contents to get deterministic word counts: 5 + 3 + 2 + 1 = 11
            $ch1 = createPublishedChapter($this, $story, $author, ['title' => 'C1', 'content' => 'one two three four five']);
            $ch2 = createPublishedChapter($this, $story, $author, ['title' => 'C2', 'content' => 'one two three']);
            $ch3 = createPublishedChapter($this, $story, $author, ['title' => 'C3', 'content' => 'one two']);
            $ch4 = createPublishedChapter($this, $story, $author, ['title' => 'C4', 'content' => 'one']);

            // Reader adds to readlist and marks first 3 as read
            $this->actingAs($reader);
            addToReadList($this, $story->id);
            markAsRead($this, $ch1)->assertNoContent();
            markAsRead($this, $ch2)->assertNoContent();
            markAsRead($this, $ch3)->assertNoContent();

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            expect($s->totalChaptersCount)->toBe(4);
            expect($s->readChaptersCount)->toBe(3);
            expect($s->progressPercent)->toBe(75);
            expect($s->totalWordCount)->toBe(11);
        });

        it('provides keep reading URL to next unread chapter', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 10);

            // Create story with 5 chapters
            $this->actingAs($author);
            $story = publicStory('Keep Reading Story', $author->id, ['description' => '']);
            $chapters = [];
            for ($i = 1; $i <= 5; $i++) {
                $chapters[] = createPublishedChapter($this, $story, $author, [
                    'title' => "Chapter {$i}",
                    'content' => "Content {$i}"
                ]);
            }

            // Reader adds to readlist and marks first 2 as read
            $this->actingAs($reader);
            addToReadList($this, $story->id);
            markAsRead($this, $chapters[0])->assertNoContent();
            markAsRead($this, $chapters[1])->assertNoContent();

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should provide URL to chapter 3 (first unread)
            expect($s->keepReadingUrl)->toBe(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapters[2]->slug
            ]));
        });

        it('provides keep reading URL to first chapter when none are read', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 10);

            // Create story with 3 chapters
            $this->actingAs($author);
            $story = publicStory('None Read Story', $author->id, ['description' => '']);
            $chapters = [];
            for ($i = 1; $i <= 3; $i++) {
                $chapters[] = createPublishedChapter($this, $story, $author, [
                    'title' => "Chapter {$i}",
                    'content' => "Content {$i}"
                ]);
            }

            // Reader adds to readlist but doesn't read any
            $this->actingAs($reader);
            addToReadList($this, $story->id);

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should provide URL to chapter 1 (first chapter)
            expect($s->keepReadingUrl)->toBe(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapters[0]->slug
            ]));
        });

        it('has no keep reading URL when all chapters are read', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 10);

            // Create story with 3 chapters
            $this->actingAs($author);
            $story = publicStory('All Read Story', $author->id, ['description' => '']);
            $chapters = [];
            for ($i = 1; $i <= 3; $i++) {
                $chapters[] = createPublishedChapter($this, $story, $author, [
                    'title' => "Chapter {$i}",
                    'content' => "Content {$i}"
                ]);
            }

            // Reader adds to readlist and marks all as read
            $this->actingAs($reader);
            addToReadList($this, $story->id);
            foreach ($chapters as $chapter) {
                markAsRead($this, $chapter)->assertNoContent();
            }

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should have null keepReadingUrl when all chapters are read
            expect($s->keepReadingUrl)->toBeNull();
        });

        it('computes last modified date using last chapter published when available', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 5);

            // Create story first
            $this->actingAs($author);
            $story = publicStory('Modified Story', $author->id, ['description' => '']);

            // Wait a moment to ensure different timestamps
            sleep(1);

            // Then publish a chapter
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1']);

            // Refresh story to get updated last_chapter_published_at
            $story->refresh();

            // Reader adds to readlist
            $this->actingAs($reader);
            addToReadList($this, $story->id);

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should use last_chapter_published_at when available
            expect($s->lastModified)->toBeInstanceOf(\DateTime::class);
            expect($s->lastModified)->toEqual($story->last_chapter_published_at);
            expect($s->lastModified)->not->toEqual($story->created_at);
        });

        it('computes last modified date using created_at when no chapters published', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            // Create story without published chapters
            $story = publicStory('Unmodified Story', $author->id, ['description' => '']);

            // Reader adds to readlist
            $this->actingAs($reader);
            addToReadList($this, $story->id);

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should use created_at when no chapters published
            expect($s->lastModified)->toBeInstanceOf(\DateTime::class);
            expect($s->lastModified)->toEqual($story->created_at);
        });

        it('loads more stories via AJAX endpoint', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            setUserCredits($author->id, 25);

            // Create 15 stories and add to reader's readlist
            for ($i = 1; $i <= 15; $i++) {
                $this->actingAs($author);
                $story = publicStory("Story {$i}", $author->id);
                createPublishedChapter($this, $story, $author);

                $this->actingAs($reader);
                addToReadList($this, $story->id);
            }

            // Test loadMore endpoint
            $response = $this->actingAs($reader)
                ->getJson(route('readlist.load-more', [
                    'page' => 2,
                    'perPage' => 10
                ]));

            $response->assertOk();
            $response->assertJsonStructure([
                'html',
                'hasMore',
                'nextPage',
                'total'
            ]);

            $data = $response->json();

            expect($data['hasMore'])->toBeFalse();
            expect($data['nextPage'])->toBe(3);
            expect($data['total'])->toBe(15);
            $html = $data['html'];
            expect($html)->not->toBeEmpty();
        });

        it('has no keep reading URL when story has no chapters', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

            // Create story with no chapters
            $story = publicStory('Empty Story', $author->id, ['description' => '']);

            // Reader adds to readlist
            $this->actingAs($reader);
            addToReadList($this, $story->id);

            $response = $this->actingAs($reader)->get(route('readlist.index'));
            $response->assertOk();

            /** @var ReadListIndexViewModel $vm */
            $vm = $response->viewData('vm');
            /** @var ReadListStoryViewModel $s */
            $s = $vm->stories->first();

            // Should have null keepReadingUrl when no chapters
            expect($s->keepReadingUrl)->toBeNull();
        });

        describe('Chapters Endpoint', function () {
            it('returns chapters HTML for story in readlist', function () {
                $author = alice($this, roles: [Roles::USER_CONFIRMED]);
                $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

                setUserCredits($author->id, 10);

                // Create story with 3 chapters
                $this->actingAs($author);
                $story = publicStory('Test Story', $author->id, ['description' => '']);
                $chapters = [];
                for ($i = 1; $i <= 3; $i++) {
                    $chapters[] = createPublishedChapter($this, $story, $author, [
                        'title' => "Chapter {$i}",
                        'content' => "Content {$i}"
                    ]);
                }

                // Reader adds to readlist
                $this->actingAs($reader);
                addToReadList($this, $story->id);

                // Request chapters
                $response = $this->actingAs($reader)->get(route('readlist.chapters', $story->id));

                $response->assertOk();
                $response->assertJsonStructure([
                    'html',
                    'count',
                    'isEmpty'
                ]);

                $data = $response->json();
                expect($data['count'])->toBe(3);
                expect($data['isEmpty'])->toBeFalse();
                expect($data['html'])->toContain('Chapter 1');
                expect($data['html'])->toContain('Chapter 2');
                expect($data['html'])->toContain('Chapter 3');
            });

            it('returns 404 for story not in readlist', function () {
                $author = alice($this, roles: [Roles::USER_CONFIRMED]);
                $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

                // Create story
                $this->actingAs($author);
                $story = publicStory('Test Story', $author->id, ['description' => '']);

                // Reader does NOT add to readlist
                $this->actingAs($reader);

                // Request chapters
                $response = $this->actingAs($reader)->get(route('readlist.chapters', $story->id));

                $response->assertNotFound();
            });

            it('requires authentication', function () {
                $story = publicStory('Test Story', alice($this)->id, ['description' => '']);

                // Request chapters without authentication
                $response = $this->get(route('readlist.chapters', $story->id));

                $response->assertRedirectToRoute('login');
            });
        });
    });
});
