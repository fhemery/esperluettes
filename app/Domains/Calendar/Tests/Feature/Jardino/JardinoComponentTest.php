<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Jardino Info Component (US-01)', function () {
    beforeEach(function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        // Ensure a fake type exists so we can create an activity
        registerFakeActivityType($registry);
    });

    it('renders a static information page with rules, flower mechanics and garden concept', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $activityId = createActivity($this, [
            'name' => 'JardiNo Test Activity',
            'activity_type' => 'fake',
        ], $admin->id);

        $activity = Activity::findOrFail($activityId);

        // Render the Jardino Blade component class
        $html = Blade::render(
            '<x-jardino::jardino-component :activity="$activity" />',
            compact('activity')
        );

        $title = __('jardino::details.title');
        $description = __('jardino::details.description');
        $readMore = __('jardino::details.read_more');
        $readMoreUrl = route('static.show', 'jardino');

        expect($html)
            ->toContain($title)
            ->and($html)->toContain($description)
            ->and($html)->toContain($readMore)
            ->and($html)->toContain($readMoreUrl);
    });

    describe('When goal is not yet set', function () {
        it('renders a form with stories select and numeric input when no objective exists', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Create stories for the user
            publicStory('Alpha Story', $user->id);
            privateStory('Beta Story', $user->id);

            // Create activity
            $admin = admin($this);
            $this->actingAs($admin);
            $activityId = createActivity($this, [
                'name' => 'Jardino Activity',
                'activity_type' => 'fake',
            ], $admin->id);

            $this->actingAs($user);
            $activity = Activity::findOrFail($activityId);

            $html = Blade::render('<x-jardino::jardino-component :activity="$activity" />', compact('activity'));

            // Select exists with options containing the story titles
            expect($html)->toContain('<select name="story_id"')
                ->and($html)->toContain('Alpha Story')
                ->and($html)->toContain('Beta Story');

            // Numeric input for target words
            expect($html)->toContain('name="target_word_count"')
                ->and($html)->toContain('type="number"');
        });
    });

    describe('When a goal is set', function() {
        it('should display the objective, the progress, the number of flowers earned, planted and available', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Create a story for the user
            $story = publicStory('My JardiNo Story', $user->id);

            // Create activity
            $admin = admin($this);
            $this->actingAs($admin);
            $activity = createActiveJardino($this);

            // Create a goal
            $resp = $this->post("/calendar/activities/{$activity->id}/jardino/goal", [
                'story_id' => $story->id,
                'target_word_count' => 10000,
            ]);
            $resp->assertRedirect();

            // Render the component
            $activity = Activity::findOrFail($activity->id);
            $html = Blade::render('<x-jardino::jardino-component :activity="$activity" />', compact('activity'));

            // Check that the objective is displayed
            expect($html)->toContain('My JardiNo Story')
                ->and($html)->toContain('10 000')

                // Check progress statistics (initially 0 since no snapshots exist yet)
                ->and($html)->toContain(__('jardino::objective.words_written'))
                ->and($html)->toContain('0')

                // Check progress percentage
                ->and($html)->toContain(__('jardino::objective.progress'))
                ->and($html)->toContain('0')

                // Check flower statistics (initially 0 since no snapshots exist)
                ->and($html)->toContain(__('jardino::objective.flowers_earned'))
                ->and($html)->toContain('0')
                ->and($html)->toContain(__('jardino::objective.flowers_planted'))
                ->and($html)->toContain('0')
                ->and($html)->toContain(__('jardino::objective.flowers_available'))
                ->and($html)->toContain('0');
        });
    });
});
