<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Shared\Support\WordCounter;
use App\Domains\Story\Events\StoryCreated;
use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Creating a story', function () {



    describe('Page access', function () {
        it('redirects guests from create page to login', function () {
            $response = $this->get('/stories/create');

            $response->assertRedirect('/login');
        });

        it('denies non-confirmed users from accessing the create page', function () {
            $user = alice($this, roles: [Roles::USER]);

            $resp = $this->actingAs($user)->get('/stories/create');

            // CheckRole middleware redirects unauthorized roles to dashboard
            $resp->assertRedirect(route('dashboard'));
        });

        it('allows user-confirmed users to access the story creation page', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)->get('/stories/create');

            $response->assertOk();
            $response->assertSee(__('story::create.title'));
        });
    });

    describe('Page display', function () {
        it('shows basic story form fields to authenticated users', function () {
            $user = alice($this);

            $response = $this->actingAs($user)->get('/stories/create');

            $response->assertOk();
            // Assert by translation keys to be locale-agnostic
            $response->assertSee('story::shared.title.label');
            $response->assertSee('story::shared.description.label');
            $response->assertSee('story::shared.visibility.label');
            $response->assertSee('story::shared.visibility.help', false); // allow HTML
            $response->assertSee('story::shared.visibility.options.public');
            $response->assertSee('story::shared.visibility.options.community');
            $response->assertSee('story::shared.visibility.options.private');
        });

        it('shows story types details to authenticated used', function () {
            $user = alice($this);

            $response = $this->actingAs($user)->get('/stories/create');

            $response->assertOk();

            // Type field (label, placeholder/help, required note)
            $response->assertSee('story::shared.type.label');
            $response->assertSee('story::shared.type.placeholder');
            $response->assertSee('story::shared.type.help');
            $response->assertSee('story::create.actions.continue');
        });

        it('shows story audience details to authenticated user', function () {
            $user = alice($this);

            $response = $this->actingAs($user)->get('/stories/create');

            $response->assertOk();

            // Audience field (label, placeholder/help, required note)
            $response->assertSee('story::shared.audience.label');
            $response->assertSee('story::shared.audience.placeholder');
            $response->assertSee('story::shared.audience.help');
        });

        it('shows story copyright details to authenticated user', function () {
            $user = alice($this);

            $response = $this->actingAs($user)->get('/stories/create');

            $response->assertOk();

            // Copyright field (label, placeholder/help, required note)
            $response->assertSee('story::shared.copyright.label');
            $response->assertSee('story::shared.copyright.placeholder');
            $response->assertSee('story::shared.copyright.help');
        });
    });

    describe('Story creation', function () {
        it('denies non-confirmed users from posting new stories', function () {
            $user = alice($this, roles: [Roles::USER]);
            $this->actingAs($user);

            $payload = validStoryPayload([
                'title' => 'Blocked Title',
            ]);

            $resp = $this->post('/stories', $payload);

            // Redirected to dashboard due to missing user-confirmed role
            $resp->assertRedirect(route('dashboard'));

            // Ensure nothing was created
            expect(Story::query()->count())->toBe(0);
        });

        it('produces unique slugs for duplicate titles', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);

            $payload = validStoryPayload([
                'title' => 'Same Title',
            ]);

            // Act: create two stories with identical titles
            $this->post('/stories', $payload)->assertRedirect();
            $this->post('/stories', $payload)->assertRedirect();

            // Assert: both exist and have unique slug-with-id
            $stories = Story::query()->orderBy('id')->get();
            expect($stories)->toHaveCount(2);

            $first = $stories[0];
            $second = $stories[1];

            // Both slugs start with the same base, and end with their respective ids
            $base = Story::generateSlugBase('Same Title');
            expect($first->slug)->toStartWith($base . '-')
                ->and($first->slug)->toEndWith('-' . $first->id)
                ->and($second->slug)->toStartWith($base . '-')
                ->and($second->slug)->toEndWith('-' . $second->id)
                ->and($first->slug)->not->toEqual($second->slug);

            // Show pages should be reachable via slug-with-id
            $this->get('/stories/' . $first->slug)->assertOk();
            $this->get('/stories/' . $second->slug)->assertOk();
        });

        it('allows a confirmed user to create a story and see it', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);

            // Act
            $payload = validStoryPayload([
                'title' => 'My First Story',
            ]);

            $response = $this->post('/stories', $payload);

            // Assert redirect to story page
            $response->assertRedirect();

            // Load created story
            $story = Story::query()->firstOrFail();

            // URL pattern contains slug-with-id
            expect($story->slug)->toEndWith('-' . $story->id);

            // Visit show page and assert content
            $show = $this->get('/stories/' . $story->slug);
            $show->assertOk();
            $show->assertSee('My First Story');
            $show->assertSee('story::shared.visibility.options.public');
            $show->assertSee('story::show.edit');
            // Type label and name displayed
            $show->assertSee(trans('story::shared.type.label'));
            $show->assertSee(defaultStoryType()->name);
            // Genres label and at least default genre displayed
            $show->assertSee(trans('story::shared.genres.label'));
            $show->assertSee(defaultGenre()->name);
        });

        it('displays multiple selected genres as badges on show page', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);

            $g1 = makeGenre('Fantasy');
            $g2 = makeGenre('Romance');
            $payload = validStoryPayload([
                'title' => 'Genreful',
                'story_ref_genre_ids' => [$g1->id, $g2->id],
            ]);

            // Act
            $resp = $this->post('/stories', $payload);
            $resp->assertRedirect();

            $story = \App\Domains\Story\Models\Story::query()->firstOrFail();
            $show = $this->get('/stories/' . $story->slug);

            // Assert
            $show->assertOk();
            $show->assertSee(trans('story::shared.genres.label'));
            $show->assertSee('Fantasy');
            $show->assertSee('Romance');
        });

        it('allows creating a story with an optional status which is shown on the page', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);
            $status = makeStatus('Ongoing');

            // Act
            $payload = validStoryPayload([
                'title' => 'Status Story',
                'story_ref_status_id' => $status->id,
            ]);
            $resp = $this->post('/stories', $payload);
            $resp->assertRedirect();

            // Assert
            $story = Story::query()->firstOrFail();
            expect($story->story_ref_status_id)->toBe($status->id);

            $show = $this->get('/stories/' . $story->slug);
            $show->assertOk();
            $show->assertSee(trans('story::shared.status.label'));
            $show->assertSee($status->name);
        });

        it('allows creating a story with multiple trigger warnings and displays them on show page', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);

            $tw1 = makeTriggerWarning('Violence');
            $tw2 = makeTriggerWarning('Langage vulgaire');

            // Act
            $payload = validStoryPayload([
                'title' => 'TW Story',
                'tw_disclosure' => Story::TW_LISTED,
                'story_ref_trigger_warning_ids' => [$tw1->id, $tw2->id],
            ]);
            $resp = $this->post('/stories', $payload);
            $resp->assertRedirect();

            // Assert persistence
            $story = \App\Domains\Story\Models\Story::query()->firstOrFail();
            $ids = $story->triggerWarnings()->pluck('story_ref_trigger_warnings.id')->sort()->values()->all();
            expect($ids)->toBe([$tw1->id, $tw2->id]);

            // Assert display on show page
            $show = $this->get('/stories/' . $story->slug);
            $show->assertOk();
            $show->assertSee(trans('story::shared.trigger_warnings.label'));
            $show->assertSee('Violence');
            $show->assertSee('Langage vulgaire');
        });

        it('allows creating a story with an optional feedback which is shown on the page', function () {
            // Arrange
            $user = alice($this);
            $this->actingAs($user);
            $feedback = makeFeedback('Beta Readers Wanted');

            // Act
            $payload = validStoryPayload([
                'title' => 'Feedback Story',
                'story_ref_feedback_id' => $feedback->id,
            ]);
            $resp = $this->post('/stories', $payload);
            $resp->assertRedirect();

            // Assert persistence
            $story = Story::query()->firstOrFail();
            expect($story->story_ref_feedback_id)->toBe($feedback->id);

            // Assert display on show page
            $show = $this->get('/stories/' . $story->slug);
            $show->assertOk();
            $show->assertSee(trans('story::shared.feedback.label'));
            $show->assertSee($feedback->name);
        });
    });

    describe('Events', function () {
        describe('Story.Created event', function () {
            it('is emitted when creating a story and contains full payload', function () {
                $user = alice($this);
                $this->actingAs($user);

                $title = 'A Great Tale';
                $description = '<p>' . str_repeat('word ', 120) . '</p>';
                $payload = validStoryPayload([
                    'title' => $title,
                    'description' => $description,
                    // add one extra genre and one TW to exercise arrays
                    'story_ref_genre_ids' => [defaultGenre()->id],
                    'story_ref_trigger_warning_ids' => [defaultTriggerWarning()->id],
                ]);

                $response = $this->post(route('stories.store'), $payload);
                $response->assertRedirect();

                /** @var StoryCreated|null $event */
                $event = latestEventOf(StoryCreated::name(), StoryCreated::class);
                expect($event)->not->toBeNull();

                $s = $event->story;

                // Basic fields
                expect($s->createdByUserId)->toBe($user->id);
                expect($s->title)->toBe($title);
                expect(in_array($s->visibility, Story::visibilityOptions(), true))->toBeTrue();

                // Slug contains slugged title and ends with -{id}
                $base = Str::slug($title);
                expect(Str::startsWith($s->slug, $base))->toBeTrue();
                expect(Str::endsWith($s->slug, '-' . $s->storyId))->toBeTrue();

                // Summary counts
                $expectedWords = WordCounter::count($description);
                $expectedChars = mb_strlen(strip_tags($description));
                expect($s->summaryWordCount)->toBe($expectedWords);
                expect($s->summaryCharCount)->toBe($expectedChars);

                // Referential IDs
                expect($s->typeId)->toBe((int) $payload['story_ref_type_id']);
                expect($s->audienceId)->toBe((int) $payload['story_ref_audience_id']);
                expect($s->copyrightId)->toBe((int) $payload['story_ref_copyright_id']);
                expect($s->statusId)->toBeNull();
                expect($s->feedbackId)->toBeNull();
                expect($s->genreIds)->toBeArray();
                expect($s->triggerWarningIds)->toBeArray();
                expect($s->genreIds)->not->toBeEmpty();
            });
        });
    });
});
