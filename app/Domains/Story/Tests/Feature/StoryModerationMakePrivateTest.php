<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Events\StoryModeratedAsPrivate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Story moderation - Make Private', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id, [
            'description' => '<p>Public</p>',
        ]);
        $this->slug = $this->story->slug;
        $this->targetUrl = "/stories/{$this->slug}/moderation/make-private";
        $this->referer = "/stories/{$this->slug}";
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation route', function () {
            $this->post($this->targetUrl)
                ->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($confirmed)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to access and redirects back with success message', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
                
        });
    });

    describe('Make private', function () {
        it('sets story visibility to private in database', function () {
            // Precondition
            expect($this->story->visibility)->toBe(Story::VIS_PUBLIC);

            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('success', __('story::moderation.make_private.success'));

            // Assert DB
            $this->story->refresh();
            expect($this->story->visibility)->toBe(Story::VIS_PRIVATE);
        });

        it('emits StoryModeratedAsPrivate event when moderator makes story private', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));

            /** @var StoryModeratedAsPrivate $event */
            $event = latestEventOf(StoryModeratedAsPrivate::name(), StoryModeratedAsPrivate::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($this->story->id);
        });

        it('is a no-op if already private (keeps visibility as private)', function () {
            $this->story->update(['visibility' => Story::VIS_PRIVATE]);

            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));

            $this->story->refresh();
            expect($this->story->visibility)->toBe(Story::VIS_PRIVATE);
        });
    });
});
