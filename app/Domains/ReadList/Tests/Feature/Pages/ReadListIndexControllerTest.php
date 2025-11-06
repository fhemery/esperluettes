<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use App\Domains\ReadList\Private\ViewModels\ReadListIndexViewModel;
use App\Domains\ReadList\Private\ViewModels\ReadListStoryViewModel;
use Illuminate\Support\Collection;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\StoryRef\Private\Services\GenreService;
use App\Domains\StoryRef\Private\Services\TriggerWarningService;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadListController index', function () {
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
        /*// Prefer a view model passed to the view
        $response->assertViewHas('vm', function ($vm) use ($story1, $story2) {
            // Expect stories as objects with id property
            $ids = array_map(fn($s) => (int) $s->id, (array) $vm->stories);
            sort($ids);
            expect($ids)->toEqual([min($story1->id, $story2->id), max($story1->id, $story2->id)]);

            // Expect pagination object with totals
            expect($vm->pagination->current_page)->toBe(1);
            expect($vm->pagination->per_page)->toBeGreaterThan(0);
            expect($vm->pagination->total)->toBe(2);
            expect($vm->pagination->last_page)->toBe(1);

            return true;
        });
        */
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
            $genre = makeGenre('Fantasy');
            $tw = makeTriggerWarning('Violence');
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
            expect($s->coverUrl)->toBe('/images/story/default-cover.svg');
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
    });
});
