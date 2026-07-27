<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Public\Providers\ProfileServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Assert which tab the strip marks as selected.
 *
 * Asserting on label text is not enough: every visible tab's label is in the
 * strip whatever the active tab is, so a label assertion passes vacuously.
 */
function assertActiveTab(Illuminate\Testing\TestResponse $response, string $expectedKey): void
{
    $selected = [];

    foreach ($response->getElements('nav[role="tablist"] a[role="tab"]') as $tab) {
        if ($tab->getAttribute('aria-selected') === 'true') {
            $selected[] = basename((string) parse_url($tab->getAttribute('href'), PHP_URL_PATH));
        }
    }

    expect($selected)->toBe([$expectedKey]);
}

describe('Profile tab routing', function () {

    describe('Route accessibility', function () {
        it('allows guests to access the default profile route and defaults to stories tab', function () {
            $user = alice($this);
            $profile = Profile::where('user_id', $user->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}")
                ->assertOk()
                ->assertSee(__('story::profile.stories'));
        });

        it('allows guests to access the stories tab route', function () {
            $user = alice($this);
            $profile = Profile::where('user_id', $user->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('story::profile.stories'));
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
                ->assertSee(__('story::profile.stories'));
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

        it('defaults to the stories tab for authenticated users viewing another profile', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $response = $this->actingAs($bob)
                ->get("/profile/{$profile->slug}")
                ->assertOk();

            assertActiveTab($response, 'stories');
        });

        it('redirects an unknown tab to the default tab', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)
                ->get("/profile/{$profile->slug}/banana")
                ->assertRedirect(route('profile.show', $profile->slug));
        });

        it('sends a guest asking for an unknown tab to the default tab, not to login', function () {
            $alice = alice($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->get("/profile/{$profile->slug}/banana")
                ->assertRedirect(route('profile.show', $profile->slug));
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
                ->assertSee(route('profile.show.tab', [$profile, 'about']))
                ->assertSee(route('profile.show.tab', [$profile, 'stories']));
        });

        it('marks only the tab being viewed as selected', function () {
            $alice = alice($this);
            $bob = bob($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $response = $this->actingAs($bob)->get("/profile/{$profile->slug}/about");
            assertActiveTab($response, 'about');

            $response = $this->actingAs($bob)->get("/profile/{$profile->slug}/stories");
            assertActiveTab($response, 'stories');
        });
    });

    describe('Own profile behavior', function () {
        it('defaults to stories tab when viewing own profile', function () {
            $alice = alice($this);

            $this->actingAs($alice)
                ->get('/profile')
                ->assertOk()
                ->assertSee(__('story::profile.my-stories'));
        });

        it('shows "My stories" label instead of "Stories" for own profile', function () {
            $alice = alice($this);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($alice)
                ->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertSee(__('story::profile.my-stories'));
        });
    });

    describe('Owner visibility indicator', function () {
        it('tells the owner their tab is hidden from others', function () {
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            setSettingsValue(
                $alice->id,
                ProfileServiceProvider::TAB_PROFILE,
                ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
                true,
            );

            $this->actingAs($alice)->get("/profile/{$profile->slug}/comments")
                ->assertOk()
                ->assertSee('visibility_off')
                ->assertSee(__('profile::show.tab_visibility.hidden'));
        });

        it('tells the owner their tab is visible when it is not hidden', function () {
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $html = $this->actingAs($alice)->get("/profile/{$profile->slug}/comments")
                ->assertOk()
                ->assertSee(__('profile::show.tab_visibility.visible'))
                ->getContent();

            expect($html)->not->toContain('visibility_off');
        });

        it('shows no indicator on a tab whose visibility is not settings-driven', function () {
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($alice)->get("/profile/{$profile->slug}/stories")
                ->assertOk()
                ->assertDontSee('data-test-id="profile-tab-visibility"', false);
        });

        it('never shows the indicator to other people', function () {
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            $this->actingAs($bob)->get("/profile/{$profile->slug}/comments")
                ->assertOk()
                ->assertDontSee('data-test-id="profile-tab-visibility"', false);
        });

        it('renders exactly one indicator on a tab', function () {
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $profile = Profile::where('user_id', $alice->id)->firstOrFail();

            // Story used to render its own copy inside the comments tab, so the
            // owner saw two eye icons on the same page.
            foreach (['comments', 'following', 'quotes'] as $tab) {
                $html = $this->actingAs($alice)->get("/profile/{$profile->slug}/{$tab}")
                    ->assertOk()
                    ->getContent();

                expect(substr_count($html, 'data-test-id="profile-tab-visibility"'))->toBe(1)
                    ->and(preg_match_all('/>\s*visibility(_off)?\s*</', $html))->toBe(1);
            }
        });
    });
});
