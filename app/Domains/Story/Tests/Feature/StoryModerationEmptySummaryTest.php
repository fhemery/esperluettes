<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Public\Events\StorySummaryModerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Story moderation - Empty Summary', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id, [
            'description' => '<p>Some description</p>',
        ]);
        $this->slug = $this->story->slug;
        $this->targetUrl = "/stories/{$this->slug}/moderation/empty-summary";
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

        it('allows moderator to access and redirects to dashboard with success message', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.empty_summary.success'));
        });
    });

    describe('Empty summary', function () {
        it('empties the summary/description field in database and UI', function () {
            // Precondition: description present on page
            $this->get("/stories/{$this->slug}")
                ->assertOk()
                ->assertSee('Some description', false);

            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.empty_summary.success'));

            $response = $this->get("/stories/{$this->slug}");
            $response->assertOk();
            $response->assertDontSee('Some description', false);
            $response->assertSee(__('story::show.no_description'));
        });

        it('emits StorySummaryModerated event when moderator empties the summary', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var StorySummaryModerated $event */
            $event = latestEventOf(StorySummaryModerated::name(), StorySummaryModerated::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($this->story->id);
        });
    });
});
