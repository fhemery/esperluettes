<?php

declare(strict_types=1);

use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile tab routing', function () {

    describe('Route accessibility', function () {
        it('allows guests to access the default profile route and defaults to stories tab', function () {
            $user = alice($this);
            $profile = Profile::where('user_id', $user->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}")
                ->assertOk()
                ->assertSee(__('profile::show.stories'));
        });

        it('allows guests to access the stories tab route', function () {
            $user = alice($this);
            $profile = Profile::where('user_id', $user->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('profile::show.stories'));
        });

        it('requires authentication for about tab route', function () {
            $user = alice($this);
            $profile = Profile::where('user_id', $user->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}/about")
                ->assertRedirect('/login');
        });

        it('allows authenticated users to access stories tab route', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('profile::show.stories'));
        });

        it('allows authenticated users to access about tab route', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/about")
                ->assertOk()
                ->assertSee(__('profile::show.about'));
        });

        it('defaults to about tab for authenticated users viewing others profile', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}")
                ->assertOk()
                ->assertSee(__('profile::show.about'));
        });
    });

    describe('Tab content rendering', function () {
        it('renders about panel content on about tab', function () {
            $alice = alice($this);
            $bob = bob($this);
            
            // Set a description for Alice
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();
            $profile->update(['description' => 'This is my test bio']);

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/about")
                ->assertOk()
                ->assertSee('This is my test bio');
        });

        it('renders stories component on stories tab', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('story::profile.stories'));
        });
    });

    describe('Tab navigation links', function () {
        it('shows correct tab links for authenticated users', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $response = $this->actingAs($bob)->get("/profile/{$profile->slug}");
            
            $response->assertOk()
                ->assertSee(route('profile.show.about', $profile))
                ->assertSee(route('profile.show.stories', $profile));
        });

        it('highlights the active tab with aria-selected', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            // About tab should be selected by default when viewing someone else's profile
            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}")
                ->assertSee('aria-selected="true"', false);

            // Stories tab should be selected when explicitly navigating to it
            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/stories")
                ->assertSee('aria-selected="true"', false);
        });
    });

    describe('Own profile behavior', function () {
        it('defaults to stories tab when viewing own profile', function () {
            $alice = alice($this);

            $this->actingAs($alice)
                ->get('/profile')
                ->assertOk()
                ->assertSee(__('profile::show.my-stories'));
        });

        it('shows "My stories" label instead of "Stories" for own profile', function () {
            $alice = alice($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($alice)
                ->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('profile::show.my-stories'));
        });
    });
});
