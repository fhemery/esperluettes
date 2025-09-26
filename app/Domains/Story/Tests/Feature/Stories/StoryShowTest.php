<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Story details page', function () {

    it('shows public story details with title, description, authors and last chapter update', function () {
        // Arrange: author and public story
        $author = alice($this);
        $story = publicStory('Public Story', $author->id, [
            'description' => '<p>Some description</p>',
        ]);
        $chapter = createPublishedChapter($this, $story, $author);

        // Act
        $response = $this->get('/stories/' . $story->slug);

        // Assert
        $response->assertOk();
        $response->assertSee('Public Story');
        $response->assertSee('Some description');
        $response->assertSee($author->name);

        $response->assertSee($chapter->last_edited_at->format('Y-m-d'));
    });

    it('returns 404 for private story to non-author', function () {
        $author = alice($this);
        $nonAuthor = bob($this);
        $story = privateStory('Private Story', $author->id);

        $this->actingAs($nonAuthor);
        $this->get('/stories/' . $story->slug)->assertNotFound();
    });

    it('return 404 for guest for community story', function () {
        $author = alice($this);
        $story = communityStory('Community Story', $author->id);

        Auth::logout();

        $this->get('/stories/' . $story->slug)->assertNotFound();
    });

    it('returns 404 for community story to non-confirmed users', function () {
        $author = alice($this);
        $unverified = bob($this, roles: [Roles::USER]);
        $story = communityStory('Community Story', $author->id);

        $this->actingAs($unverified);
        $this->get('/stories/' . $story->slug)->assertNotFound();
    });

    it('shows placeholder when description is empty', function () {
        $author = alice($this);
        $story = publicStory('No Desc Story', $author->id, ['description' => '']);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee('story::show.no_description');
    });

    it('lists multiple authors separated by comma', function () {
        $author1 = alice($this);
        $author2 = bob($this);
        $story = publicStory('Coauthored Story', $author1->id);

        // Attach second author on pivot
        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $author2->id,
            'role' => 'author',
            'invited_by_user_id' => $author1->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    });

    it('should add a link to author profile page', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee('/profile/alice');
    });

    it('shows the story type when available', function () {
        $author = alice($this);
        $type = makeStoryType('Short Story');
        $story = publicStory('Typed Story', $author->id, [
            'story_ref_type_id' => $type->id,
        ]);
        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee(trans('story::shared.type.label'));
        $response->assertSee('Short Story');
    });

    it('shows the audience when available', function () {
        $author = alice($this);
        $aud = makeAudience('Teens');
        $story = publicStory('Audience Story', $author->id, [
            'story_ref_audience_id' => $aud->id,
        ]);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee(trans('story::shared.audience.label'));
        $response->assertSee('Teens');
    });

    it('shows the copyright when available', function () {
        $author = alice($this);
        $cr = makeCopyright('Public Domain');
        $story = publicStory('Copyrighted Story', $author->id, [
            'story_ref_copyright_id' => $cr->id,
        ]);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee(trans('story::shared.copyright.label'));
        $response->assertSee('Public Domain');
    });

    it('shows the feedback when available', function () {
        $author = alice($this);
        $fb = makeFeedback('Open to feedback');
        $story = publicStory('Feedback Visible', $author->id, [
            'story_ref_feedback_id' => $fb->id,
        ]);

        $response = $this->get('/stories/' . $story->slug);
        $response->assertOk();
        $response->assertSee(trans('story::shared.feedback.label'));
        $response->assertSee('Open to feedback');
    });

    describe('trigger warnings', function(){
        it('shows trigger warning badges when disclosure is listed', function () {
            $author = alice($this);
            $violence = makeTriggerWarning('Violence');
            $abuse = makeTriggerWarning('Abuse');

            $story = publicStory('Listed TW Show', $author->id, [
                'description' => '<p>Desc</p>',
                'story_ref_trigger_warning_ids' => [$violence->id, $abuse->id],
            ]);
            // Ensure disclosure is listed
            $story->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_LISTED;
            $story->saveQuietly();

            Auth::logout();
            $resp = $this->get('/stories/' . $story->slug);
            $resp->assertOk();
            // Badge labels should show both TW names
            $resp->assertSee('Violence');
            $resp->assertSee('Abuse');
            // And the section carries the TW label tooltip reference
            $resp->assertSee(trans('story::shared.trigger_warnings.label'));
        });

        describe('Trigger warnings disclosure badges', function () {
            it('shows a No TW badge on the story page when tw_disclosure is no_tw and no TWs are listed', function () {
                $author = alice($this);
                $story = publicStory('No TW Show', $author->id, [
                    'description' => '<p>Desc</p>',
                    'story_ref_trigger_warning_ids' => [],
                ]);
                $story->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_NO_TW;
                $story->saveQuietly();
        
                Auth::logout();
                $resp = $this->get('/stories/' . $story->slug);
                $resp->assertOk();
                // Badge text and tooltip
                $resp->assertSee(trans('story::shared.trigger_warnings.no_tw'));
                $resp->assertSee(trans('story::shared.trigger_warnings.tooltips.no_tw'));
            });
        
            it('shows an Unspoiled badge on the story page when tw_disclosure is unspoiled and no TWs are listed', function () {
                $author = alice($this);
                $story = publicStory('Unspoiled Show', $author->id, [
                    'description' => '<p>Desc</p>',
                    'story_ref_trigger_warning_ids' => [],
                ]);
                $story->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_UNSPOILED;
                $story->saveQuietly();
        
                Auth::logout();
                $resp = $this->get('/stories/' . $story->slug);
                $resp->assertOk();
                // Badge text and tooltip
                $resp->assertSee(trans('story::shared.trigger_warnings.unspoiled'));
                $resp->assertSee(trans('story::shared.trigger_warnings.tooltips.unspoiled'));
            });
        }); 
    });

    describe('chapters', function () {

        it('shows the create chapter button to the story author', function () {
            $author = alice($this);
            $story = publicStory('Author Story', $author->id);

            // Author views their own story
            $this->actingAs($author);
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            // Button text comes from chapters partial using this translation key
            $response->assertSee('story::chapters.sections.add_chapter');
            // And link points to chapters.create for this story
            $response->assertSee(route('chapters.create', ['storySlug' => $story->slug]));
        });

        it('does not show the create chapter button to non-authors', function () {
            $author = alice($this);
            $other = bob($this);
            $story = publicStory('Non Author Story', $author->id);

            // Logged-in non-author
            $this->actingAs($other);
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            $response->assertDontSee('story::chapters.sections.add_chapter');
            $response->assertDontSee(route('chapters.create', ['storySlug' => $story->slug]));
        });

        it('shows edit and delete actions to authors for chapters when they exist', function () {
            $author = alice($this);
            $story = publicStory('Chapters Story', $author->id);
            createPublishedChapter($this, $story, $author, ['title' => 'Temp Chap']);

            $response = $this->actingAs($author)->get('/stories/' . $story->slug);

            $response->assertOk();
            $response->assertSee(route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => Chapter::first()->slug]));
            $response->assertSee(route('chapters.destroy', ['storySlug' => $story->slug, 'chapterSlug' => Chapter::first()->slug]));
        });

        it('does not show edit and delete actions to non-authors for chapters', function () {
            $author = alice($this);
            $story = publicStory('Chapters Story', $author->id);
            createPublishedChapter($this, $story, $author, ['title' => 'Temp Chap']);

            $this->actingAs(bob($this));
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            $response->assertDontSee("data-edit-url");
            $response->assertDontSee("data-delete-url");
        });

        it('lists only published chapters to non authors', function () {
            $author = alice($this);
            $story = publicStory('Chapters Story', $author->id);

            // Create one published and one draft chapter
            createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1 - Published']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'Chapter 2 - Draft']);

            // Guest (no auth)
            Auth::logout();
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            // Sees only published chapter title
            $response->assertSee('Chapter 1 - Published');
            $response->assertDontSee('Chapter 2 - Draft');
            // No draft chip
            $response->assertDontSee(trans('story::chapters.list.draft'));
        });

        it('shows all chapters with draft chip to the author', function () {
            $author = alice($this);
            $story = publicStory('Chapters Story 2', $author->id);

            createPublishedChapter($this, $story, $author, ['title' => 'P Chap']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'D Chap']);

            $this->actingAs($author);
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            // Author sees both
            $response->assertSee('P Chap');
            $response->assertSee('D Chap');
            // Draft chip visible
            $response->assertSee(trans('story::chapters.list.draft'));
        });

        it('appends newly created chapter at the end of the list', function () {
            // Arrange: public story with existing chapters
            $author = alice($this);
            $story = publicStory('Ordered Story', $author->id);

            // Create two published chapters via HTTP (ensures service logic is used)
            createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1']);
            createPublishedChapter($this, $story, $author, ['title' => 'Chapter 2']);

            // Act: create a new chapter which should be appended (higher sort_order)
            createPublishedChapter($this, $story, $author, ['title' => 'Chapter 3']);

            // Assert: on the story page, chapters appear in order with the new one last
            Auth::logout(); // guest view is enough since all are published
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            $response->assertSeeInOrder(['Chapter 1', 'Chapter 2', 'Chapter 3']);
        });

        it('should not show reorder button if there are no chapters', function () {
            $author = alice($this);
            $story = publicStory('No Chapters Story', $author->id);

            $this->actingAs($author);
            $response = $this->get('/stories/' . $story->slug);
            $response->assertOk();
            $response->assertDontSee('story::chapters.actions.reorder');
        });

        it('shows 0 credits left and disables the Add Chapter button after creating 5 chapters', function () {
            $author = alice($this);
            $story = publicStory('Credits Exhaustion', $author->id);

            // Author creates 5 chapters (any status consumes credits)
            createUnpublishedChapter($this, $story, $author, ['title' => 'C1']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C2']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C3']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C4']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C5']);

            $this->actingAs($author);
            $resp = $this->get('/stories/' . $story->slug);

            $resp->assertOk();
            // Expect the UI to show 0 credits left and not link to chapters.create
            $resp->assertSeeInOrder(['0', __('story::chapters.no_chapter_credits_left')]);
            $resp->assertDontSee(route('chapters.create', ['storySlug' => $story->slug]));
        });

        it('does not show credits count to non-authors on the story page', function () {
            $author = alice($this);
            $story = publicStory('Foreign Story', $author->id);

            // Create some chapters so author-view would normally show counts for authors
            createUnpublishedChapter($this, $story, $author, ['title' => 'C1']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C2']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C3']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C4']);
            createUnpublishedChapter($this, $story, $author, ['title' => 'C5']);

            $viewer = bob($this);
            $this->actingAs($viewer);
            $resp = $this->get('/stories/' . $story->slug);

            $resp->assertOk();
            // Non-authors see reader view; ensure no credits text leaks
            $resp->assertDontSee(__('story::chapters.no_chapter_credits_left'));
        });
    });

    describe('Reading statistics', function () {

        it('shows reads counter on story page for readers with non-zero values', function () {
            $author = alice($this);
            $story = publicStory('Reads Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'First Chapter']);

            $reader1 = bob($this);
            $this->actingAs($reader1);
            markAsRead($this, $chapter)->assertNoContent();

            $resp = $this->get('/stories/' . $story->slug);
            $resp->assertOk();

            $resp->assertSee(trans('story::chapters.reads.label'));
            $resp->assertSee(trans('story::chapters.reads.tooltip'));

            $resp->assertSee('First Chapter');
            $resp->assertSee('1');
        });

        it('shows reads counter on story page for authors (value present in initial data and popover)', function () {
            $author = alice($this);
            $story = publicStory('Author Reads', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch']);

            $reader = bob($this);

            $this->actingAs($reader);
            markAsRead($this, $chapter)->assertNoContent();

            $this->actingAs($author);
            $resp = $this->get('/stories/' . $story->slug);
            $resp->assertOk();

            $resp->assertSee(trans('story::chapters.reads.label'));
            $resp->assertSee(trans('story::chapters.reads.tooltip'));

            $resp->assertSeeInOrder(['visibility', '1']);
        });

        it('shows total reads on the story page', function () {
            $author = alice($this);
            $story = publicStory('Total Reads Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Only Chapter']);

            // One reader marks as read -> story total should be 1
            $reader = bob($this);
            $this->actingAs($reader);
            markAsRead($this, $chapter)->assertNoContent();

            // Guest view is fine; number rendered server-side
            Auth::logout();
            app()->setLocale('fr');
            $resp = $this->get('/stories/' . $story->slug);
            $resp->assertOk();
            $resp->assertSee('1');
        });

        it('shows total words on the story page', function () {
            $author = alice($this);
            $story = publicStory('Total Words Story', $author->id);
            // Two chapters with 2 and 3 words respectively => total 5
            createPublishedChapter($this, $story, $author, ['title' => 'Only Chapter', 'content' => '<p>one two</p>']);
            createPublishedChapter($this, $story, $author, ['title' => 'Second', 'content' => '<p>three four five</p>']);

            Auth::logout();
            $resp = $this->get('/stories/' . $story->slug);
            Log::info('Story: ' . $resp->getContent());
            $resp->assertOk();
            // We cannot look for number of signs or characters, they are embedded in the translation
            $resp->assertSee(__('story::shared.metrics.words_and_signs'));
            $resp->assertSee(__('story::shared.metrics.words_and_signs.help')); 
        });
    });

    describe('Chapter list word statistics', function () {
        it('shows words counter on chapter list for readers', function () {
            $author = alice($this);
            $story = publicStory('Words List Story', $author->id);
            createPublishedChapter($this, $story, $author, ['title' => 'C1', 'content' => '<p>alpha beta</p>']); // 2 words

            Auth::logout();
            $resp = $this->get('/stories/' . $story->slug);
            $resp->assertOk();
            $resp->assertSee(trans('story::shared.metrics.words_and_signs'));
        });

        it('shows words counter on chapter list for authors', function () {
            $author = alice($this);
            $story = publicStory('Words Author List', $author->id);
            createPublishedChapter($this, $story, $author, ['title' => 'C1', 'content' => '<p>one two three</p>']); // 3

            $resp = $this->actingAs($author)->get('/stories/' . $story->slug);
            $resp->assertOk();
            $resp->assertSee(trans('story::shared.metrics.words_and_signs'));
        });
    });
});
