<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Jardino Save Goal', function () {

    describe('Access', function () {
        it('rejects guests (auth required)', function () {
            $jardino = createActiveJardino($this);

            Auth::logout();
            $resp = $this->post("/calendar/activities/{$jardino->id}/jardino/goal", [
                'story_id' => 1,
                'target_word_count' => 1000,
            ]);
            $resp->assertRedirect('/login');
        });

        it('returns 404 when activity type is not jardino', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $activityId = createActivity($this, overrides: [
                'activity_type' => 'fake',
                'role_restrictions' => [Roles::USER_CONFIRMED],
            ]);
            $activity = Activity::findOrFail($activityId);

            $resp = $this->post("/calendar/activities/{$activity->id}/jardino/goal", [
                'story_id' => 1,
                'target_word_count' => 1500,
            ]);
            $resp->assertNotFound();
        });

        it('returns 404 when activity is not ACTIVE', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            // Create a jardino activity in PREVIEW (active_starts_at in future)
            $activityId = createActivity($this, overrides: [
                'activity_type' => 'jardino',
                'role_restrictions' => [Roles::USER_CONFIRMED],
                'preview_starts_at' => now()->addDay(),
            ]);
            $activity = Activity::findOrFail($activityId);

            $resp = $this->post("/calendar/activities/{$activity->id}/jardino/goal", [
                'story_id' => 1,
                'target_word_count' => 1500,
            ]);
            $resp->assertNotFound();
        });

        it('rejects non-confirmed users for confirmed-only activity', function () {
            $user = alice($this, roles: [Roles::USER]);

            $jardino = createActiveJardino($this, overrides: [
                'role_restrictions' => [Roles::USER_CONFIRMED],
            ]);

            $this->actingAs($user);
            $resp = $this->post("/calendar/activities/{$jardino->id}/jardino/goal", [
                'story_id' => 1,
                'target_word_count' => 1000,
            ]);
            $resp->assertStatus(403);
        });
    });

    describe('Validation', function () {
        it('returns localized errors for invalid payload and displays them in the form', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);

            $jardino = createActiveJardino($this, overrides: [
                'role_restrictions' => [Roles::USER_CONFIRMED],
            ]);

            $this->actingAs($user);
            // Missing story_id and target too small
            $this->from($jardino->url);
            $resp = $this->followingRedirects()->post("/calendar/activities/{$jardino->id}/jardino/goal", [
                'target_word_count' => 999,
            ]);
            $resp->assertSee(__('jardino::validation.story_id.required'));
        });

        it('validates target_word_count >= 1000', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);
            $story = publicStory('Alpha Story', $user->id);

            $jardino = createActiveJardino($this, overrides: [
                'role_restrictions' => [Roles::USER_CONFIRMED],
            ]);

            $this->from($jardino->url);
            $resp = $this->followingRedirects()->post("/calendar/activities/{$jardino->id}/jardino/goal", [
                'story_id' => $story->id,
                'target_word_count' => 999,
            ]);
            $resp->assertOk();
            $resp->assertSee(__('jardino::validation.target_word_count.min'));
        });
    });

    describe('Success', function () {
        it('creates the goal and shows objective with flash when valid', function () {
            $user = alice($this);

            // Create a story owned by user
            $story = publicStory('Alpha Story', $user->id);

            // Create confirmed-only ACTIVE Jardino activity
            $activity = createActiveJardino($this);

            $this->actingAs($user);
            $this->from($activity->url);
            // Post form with >= 1000 target and follow redirect back to the activity page
            $resp = $this->followingRedirects()->post("/calendar/activities/{$activity->id}/jardino/goal", [
                'story_id' => $story->id,
                'target_word_count' => 1500,
            ]);
            $resp->assertOk();
            $resp->assertSee(__('jardino::details.flash.objective_saved'));
            $resp->assertSee('Alpha Story');
            $resp->assertSee('1 500');
        });
    });
});
