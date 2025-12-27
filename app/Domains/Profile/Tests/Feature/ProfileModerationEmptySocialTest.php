<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Public\Events\SocialModerated;
use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile moderation - Empty Social', function () {
    beforeEach(function () {
        $this->owner = alice($this);
        $this->profile = Profile::where('user_id', $this->owner->id)->firstOrFail();
        $this->slug = $this->profile->slug;
        $this->targetUrl = "/profile/{$this->slug}/moderation/empty-social";
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
                ->assertSessionHas('success', __('profile::moderation.empty_social.success'));
        });
    });

    describe('Empty social', function () {
        it('empties all social network fields in database and UI', function () {
            // Arrange: seed social links and verify they appear on the profile page
            $this->actingAs($this->owner)
                ->from('/profile/edit')
                ->put('/profile', [
                    'display_name' => $this->profile->display_name,
                    'facebook_url' => 'https://facebook.com/alice',
                    'x_url' => 'https://x.com/alice',
                    'instagram_url' => 'https://instagram.com/alice',
                    'youtube_url' => 'https://youtube.com/@alice',
                ])
                ->assertRedirect('/profile');

            $response = $this->actingAs($this->owner)->get("/profile/{$this->slug}/about");
            $response->assertSee('facebook-link', false);
            $response->assertSee('instagram-link', false);
            $response->assertSee('youtube-link', false);

            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            // Assert: DB and UI
            $response = $this->actingAs($this->owner)->get("/profile/{$this->slug}/about");
            $response->assertDontSee('facebook-link', false);
            $response->assertDontSee('instagram-link', false);
            $response->assertDontSee('youtube-link', false);
        });

        it('emits SocialModerated event when moderator empties the social networks', function () {
            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var SocialModerated $event */
            $event = latestEventOf(SocialModerated::name(), SocialModerated::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($this->owner->id);
        });
    });
});
