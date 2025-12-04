<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Shared\Support\WordCounter;
use App\Domains\Story\Public\Events\StoryUpdated;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use App\Domains\Story\Public\Events\StoryExcludedFromEvents;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Editing story', function () {

    describe('Accessing page', function () {

        it('redirects guests from edit page to login', function () {
            // Arrange: existing public story
            $author = alice($this);
            $story = publicStory('Guest Edit Test', $author->id);

            // Act
            $resp = $this->get('/stories/' . $story->slug . '/edit');

            // Assert
            $resp->assertRedirect('/login');
        });

        it('allows the author to load edit page and update story', function () {
            // Arrange
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Original Title', $author->id, ['description' => '<p>desc</p>']);

            // Load edit page
            $this->get('/stories/' . $story->slug . '/edit')
                ->assertOk();

            // Update
            $payload = validStoryPayload([
                'title' => 'Updated Title',
            ]);

            $resp = $this->put('/stories/' . $story->slug, $payload);
            $resp->assertRedirect();

            // Reload model
            $story->refresh();

            // Slug base regenerated, id suffix preserved
            $newBase = Story::generateSlugBase('Updated Title');
            expect($story->slug)
                ->toStartWith($newBase . '-')
                ->and($story->slug)
                ->toEndWith('-' . $story->id);
        });


        it('allows a co-author with role author to update', function () {
            // Arrange
            $author = alice($this);
            $coauthor = bob($this);
            $story = publicStory('Team Story', $author->id);

            // Add coauthor as author
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $coauthor->id,
                'role' => 'author',
                'invited_by_user_id' => $author->id,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            $this->actingAs($coauthor);

            $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Coauthored Title',
            ]));

            $resp->assertRedirect();
            $story->refresh();
            expect($story->title)->toBe('Coauthored Title');
        });

        it('returns 404 for collaborator without author role', function () {
            // Arrange
            $author = alice($this);
            $other = bob($this);
            $story = publicStory('No Edit Perms', $author->id);

            // Add collaborator with non-author role
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $other->id,
                'role' => 'editor',
                'invited_by_user_id' => $author->id,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            $this->actingAs($other);

            $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Should Fail',
            ]))->assertNotFound();
        });

        it('returns 404 for non-collaborator trying to edit', function () {
            // Arrange
            $author = alice($this);
            $intruder = bob($this);
            $story = publicStory('No Access', $author->id);

            $this->actingAs($intruder);

            $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
            $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Nope',
            ]))->assertNotFound();
        });

        it('301-redirects from old slug base to canonical after title change', function () {
            // Arrange
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Old Title', $author->id);
            $oldSlug = $story->slug; // contains -id

            // Update title
            $this->put('/stories/' . $oldSlug, validStoryPayload([
                'title' => 'New Canonical Title',
            ]))->assertRedirect();

            $story->refresh();

            // Visiting the old slug-with-id should 301 to the new canonical slug
            $resp = $this->get('/stories/' . $oldSlug);
            $resp->assertStatus(301);
            $resp->assertRedirect('/stories/' . $story->slug);
        });

        it('denies edit access to co-authors without user-confirmed role (middleware)', function () {
            // Arrange: author is confirmed, coauthor is NOT (has only user role)
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $coauthor = bob($this, roles: [Roles::USER]);
            $story = publicStory('Needs Confirmed Role', $author->id);

            // Add coauthor as author collaborator at story level
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $coauthor->id,
                'role' => 'author',
                'invited_by_user_id' => $author->id,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            // Act as non-confirmed coauthor
            $this->actingAs($coauthor);

            // Because routes are guarded by role:user-confirmed, middleware should redirect to dashboard
            $this->get('/stories/' . $story->slug . '/edit')->assertRedirect(route('dashboard'));

            // And update attempts should also be blocked by middleware
            $this->put('/stories/' . $story->slug, [
                'title' => 'Should Not Be Applied',
                'visibility' => Story::VIS_PUBLIC,
            ])->assertRedirect(route('dashboard'));

            // Ensure story unchanged
            $story->refresh();
            expect($story->title)->toBe('Needs Confirmed Role');
        });
    });

    describe('Updating story', function () {

        it('syncs genres on update (replaces previous selection)', function () {
            // Arrange: author and story with default genre
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Genres Updatable', $author->id, [
                'story_ref_genre_ids' => [defaultRefGenre()->id],
            ]);

            // New genres to replace existing selection
            $g1 = makeRefGenre('Sci-Fi');
            $g2 = makeRefGenre('Mystery');

            // Act: update with two genres
            $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Genres Updatable',
                'story_ref_genre_ids' => [$g1->id, $g2->id],
            ]));
            $resp->assertRedirect();

            // Show page reflects new genres
            $show = $this->get($resp->headers->get('Location'));
            $show->assertOk();
            $show->assertSee('Sci-Fi');
            $show->assertSee('Mystery');
        });

        it('allows the author to set and change the optional status on update', function () {
            // Arrange
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Status Updatable', $author->id);

            $status = makeRefStatus('Draft');

            $resp1 = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Status Updatable',
                'story_ref_status_id' => $status->id,
            ]));
            $resp1->assertRedirect();

            $this->get($resp1->headers->get('Location'))
                ->assertOk()
                ->assertSee($status->name);
        });

        it('syncs trigger warnings on update (replaces previous selection)', function () {
            // Arrange
            $author = alice($this);
            $this->actingAs($author);
            $twA = makeRefTriggerWarning('Violence');
            $twB = makeRefTriggerWarning('Drogues');
            $twC = makeRefTriggerWarning('Suicide');

            // Story initially with one TW (A)
            $story = publicStory('TW Updatable', $author->id);

            // Initially update with A
            $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
                'tw_disclosure' => Story::TW_LISTED,
                'story_ref_trigger_warning_ids' => [$twA->id],
            ]));
            $resp->assertRedirect();

            // Act: update with [B, C]
            $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'TW Updatable',
                'tw_disclosure' => Story::TW_LISTED,
                'story_ref_trigger_warning_ids' => [$twB->id, $twC->id],
            ]));
            $resp->assertRedirect();

            // Show page displays new TWs
            $show = $this->get($resp->headers->get('Location'));
            $show->assertOk();
            $show->assertSee('Drogues');
            $show->assertSee('Suicide');
        });


        it('allows the author to set and change the optional feedback on update', function () {
            // Arrange
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Feedback Updatable', $author->id);

            $fb = makeRefFeedback('Looking for critique');

            // First set feedback
            $resp1 = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Feedback Updatable',
                'story_ref_feedback_id' => $fb->id,
            ]));
            $resp1->assertRedirect();

            $this->get($resp1->headers->get('Location'))
                ->assertOk()
                ->assertSee($fb->name);

            // Then clear feedback by sending null
            $resp2 = $this->put('/stories/' . $story->slug, validStoryPayload([
                'title' => 'Feedback Updatable',
                'story_ref_feedback_id' => null,
            ]));
            $resp2->assertRedirect();

            $this->get($resp2->headers->get('Location'))
                ->assertOk()
                ->assertDontSee('Looking for critique');
        });
    });

    describe('Events', function () {
        describe('Story.Updated event', function () {
            it('is emitted with before/after snapshots when updating a story', function () {
                $user = alice($this);
                $this->actingAs($user);

                // Create story via HTTP
                $initialDescription = '<p>' . str_repeat('once ', 120) . '</p>';
                $createPayload = validStoryPayload([
                    'title' => 'Original Title',
                    'description' => $initialDescription,
                    'story_ref_genre_ids' => [defaultRefGenre()->id],
                ]);
                $this->post(route('stories.store'), $createPayload)->assertRedirect();

                /** @var Story $story */
                $story = Story::query()->latest('id')->firstOrFail();

                // Update
                $newDescription = '<p>' . str_repeat('twice ', 130) . '</p>';
                $updatePayload = array_merge($createPayload, [
                    'title' => 'Updated Title',
                    'description' => $newDescription,
                ]);
                $this->put('/stories/' . $story->slug, $updatePayload)->assertRedirect();

                /** @var StoryUpdated|null $event */
                $event = latestEventOf(StoryUpdated::name(), StoryUpdated::class);
                expect($event)->not->toBeNull();

                $before = $event->before;
                $after = $event->after;

                // Same story id and creator
                expect($before->storyId)->toBe($after->storyId);
                expect($before->createdByUserId)->toBe($user->id);
                expect($after->createdByUserId)->toBe($user->id);

                // Title changed
                expect($before->title)->toBe('Original Title');
                expect($after->title)->toBe('Updated Title');

                // Slug updated to reflect new title base (ends with -{id} still)
                $baseAfter = Str::slug('Updated Title');
                expect(Str::startsWith($after->slug, $baseAfter))->toBeTrue();
                expect(Str::endsWith($after->slug, '-' . $after->storyId))->toBeTrue();

                // Word/char counts changed according to description changes
                $beforeWords = WordCounter::count($initialDescription);
                $afterWords = WordCounter::count($newDescription);
                expect($before->summaryWordCount)->toBe($beforeWords);
                expect($after->summaryWordCount)->toBe($afterWords);

                $beforeChars = mb_strlen(strip_tags($initialDescription));
                $afterChars = mb_strlen(strip_tags($newDescription));
                expect($before->summaryCharCount)->toBe($beforeChars);
                expect($after->summaryCharCount)->toBe($afterChars);

                // Referential invariants stay coherent
                expect(in_array($after->visibility, Story::visibilityOptions(), true))->toBeTrue();
                expect($after->typeId)->toBe((int) $createPayload['story_ref_type_id']);
                expect($after->audienceId)->toBe((int) $createPayload['story_ref_audience_id']);
                expect($after->copyrightId)->toBe((int) $createPayload['story_ref_copyright_id']);
                expect($after->genreIds)->toBeArray();
            });
        });

        describe('Story.VisibilityChanged event', function () {
            it('is emitted when changing visibility and contains id, title, old/new visibility', function () {
                $user = alice($this);
                $this->actingAs($user);

                // Create story PUBLIC
                $payload = validStoryPayload([
                    'title' => 'Visibility Tale',
                    'visibility' => \App\Domains\Story\Private\Models\Story::VIS_PUBLIC,
                ]);
                $this->post(route('stories.store'), $payload)->assertRedirect();

                /** @var \App\Domains\Story\Private\Models\Story $story */
                $story = \App\Domains\Story\Private\Models\Story::query()->latest('id')->firstOrFail();

                // Update to PRIVATE (only visibility change)
                $update = array_merge($payload, [
                    'visibility' => \App\Domains\Story\Private\Models\Story::VIS_PRIVATE,
                ]);
                $this->put('/stories/' . $story->slug, $update)->assertRedirect();

                /** @var StoryVisibilityChanged|null $event */
                $event = latestEventOf(StoryVisibilityChanged::name(), StoryVisibilityChanged::class);
                expect($event)->not->toBeNull();
                expect($event->storyId)->toBe($story->id);
                expect($event->title)->toBe('Visibility Tale');
                expect($event->oldVisibility)->toBe(\App\Domains\Story\Private\Models\Story::VIS_PUBLIC);
                expect($event->newVisibility)->toBe(\App\Domains\Story\Private\Models\Story::VIS_PRIVATE);
            });
        });

        describe('Story.ExcludedFromEvents event', function () {
            it('is emitted when is_excluded_from_events changes from false to true', function () {
                $user = alice($this);
                $this->actingAs($user);

                // Create story with is_excluded_from_events = false (default)
                $payload = validStoryPayload([
                    'title' => 'Event Exclusion Test',
                    'is_excluded_from_events' => false,
                ]);
                $this->post(route('stories.store'), $payload)->assertRedirect();

                /** @var Story $story */
                $story = Story::query()->latest('id')->firstOrFail();
                expect($story->is_excluded_from_events)->toBeFalse();

                // Update to is_excluded_from_events = true
                $update = array_merge($payload, [
                    'is_excluded_from_events' => true,
                ]);
                $this->put('/stories/' . $story->slug, $update)->assertRedirect();

                /** @var StoryExcludedFromEvents|null $event */
                $event = latestEventOf(StoryExcludedFromEvents::name(), StoryExcludedFromEvents::class);
                expect($event)->not->toBeNull();
                expect($event->storyId)->toBe($story->id);
                expect($event->title)->toBe('Event Exclusion Test');
            });

            it('is not emitted when is_excluded_from_events stays true', function () {
                $user = alice($this);
                $this->actingAs($user);

                // Create story with is_excluded_from_events = true
                $payload = validStoryPayload([
                    'title' => 'Already Excluded',
                    'is_excluded_from_events' => true,
                ]);
                $this->post(route('stories.store'), $payload)->assertRedirect();

                /** @var Story $story */
                $story = Story::query()->latest('id')->firstOrFail();

                // Count events after create (should be 0 since create doesn't emit this event)
                $countAfterCreate = countEvents(StoryExcludedFromEvents::name());

                // Update without changing is_excluded_from_events
                $update = array_merge($payload, [
                    'title' => 'Still Excluded',
                    'is_excluded_from_events' => true,
                ]);
                $this->put('/stories/' . $story->slug, $update)->assertRedirect();

                // Event count should remain the same (already was excluded)
                $countAfterUpdate = countEvents(StoryExcludedFromEvents::name());
                expect($countAfterUpdate)->toBe($countAfterCreate);
            });

            it('is not emitted when is_excluded_from_events changes from true to false', function () {
                $user = alice($this);
                $this->actingAs($user);

                // Create story with is_excluded_from_events = true
                $payload = validStoryPayload([
                    'title' => 'Was Excluded',
                    'is_excluded_from_events' => true,
                ]);
                $this->post(route('stories.store'), $payload)->assertRedirect();

                /** @var Story $story */
                $story = Story::query()->latest('id')->firstOrFail();

                // Count events after create
                $countAfterCreate = countEvents(StoryExcludedFromEvents::name());

                // Update to is_excluded_from_events = false
                $update = array_merge($payload, [
                    'is_excluded_from_events' => false,
                ]);
                $this->put('/stories/' . $story->slug, $update)->assertRedirect();

                // Event count should remain the same (only emitted when going from false to true)
                $countAfterUpdate = countEvents(StoryExcludedFromEvents::name());
                expect($countAfterUpdate)->toBe($countAfterCreate);
            });
        });
    });

    describe('Breadcrumbs', function () {
        it('shows Home/Dashboard icon > story link > edit label on edit page', function () {
            $author = alice($this);
            $this->actingAs($author);
            $story = publicStory('Edit Crumbs Story', $author->id);

            $resp = $this->get('/stories/' . $story->slug . '/edit');
            $resp->assertOk();

            $items = breadcrumb_items($resp);
            // Expect at least 4 items: root, library, story, edit
            expect(count($items))->toBeGreaterThanOrEqual(4);

            $storyUrl = route('stories.show', ['slug' => $story->slug]);

            // Find library crumb (clickable) linking to stories index
            $indexUrl = route('stories.index');
            $foundLibrary = false;
            foreach ($items as $it) {
                if (($it['href'] ?? null) === $indexUrl) {
                    expect($it['text'])->toEqual(__('shared::navigation.stories'));
                    $foundLibrary = true;
                }
            }
            expect($foundLibrary)->toBeTrue();

            // Find story crumb (clickable)
            $storyCrumb = null;
            foreach ($items as $it) {
                if (($it['href'] ?? null) === $storyUrl) { $storyCrumb = $it; break; }
            }
            $this->assertNotNull($storyCrumb, 'Story breadcrumb with expected URL not found');
            $this->assertStringContainsString($story->title, $storyCrumb['text'] ?? '');

            // Last crumb should be the Edit label, non-clickable
            $last = $items[count($items) - 1];
            $this->assertNull($last['href'], 'Edit breadcrumb should be non-clickable');
            $this->assertSame(__('story::edit.breadcrumb'), $last['text']);
        });
    });
});
