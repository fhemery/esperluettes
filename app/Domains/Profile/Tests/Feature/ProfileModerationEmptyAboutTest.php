<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Public\Events\AboutModerated;
use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile moderation - Empty About', function () {
    beforeEach(function () {
        $this->owner = alice($this);
        $this->profile = Profile::where('user_id', $this->owner->id)->firstOrFail();
        $this->slug = $this->profile->slug;
        $this->targetUrl = "/profile/{$this->slug}/moderation/empty-about";
        $this->referer = "/profile/{$this->slug}";
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation route', function () {
            $this->post($this->targetUrl)
                ->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $confirmed = bob($this);
            $this->actingAs($confirmed)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to access and redirects back with success message', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('profile::moderation.empty_about.success'));
        });
    });

    describe('Empty about', function () {
        it('empties the about/description field in database and UI', function () {
            // Arrange: set a description and verify it appears on the profile page
            $bio = 'This is a sample bio that should be removed by moderation.';
            $this->profile->update(['description' => $bio]);

            $response = $this->get("/profile/{$this->slug}");
            $response->assertSee($bio, false);

            // Act: moderator empties the about section
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            // Assert: DB and UI
            $this->profile->refresh();
            expect($this->profile->description)->toBeNull();

            $response = $this->get("/profile/{$this->slug}");
            $response->assertDontSee($bio, false);
        });

        it('emits AboutModerated event when moderator empties the about section', function () {
            // Arrange: set a description
            $bio = 'This is a sample bio that should be removed by moderation.';
            $this->profile->update(['description' => $bio]);

            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var AboutModerated $event */
            $event = latestEventOf(AboutModerated::name(), AboutModerated::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($this->owner->id);
        });
    });
});
