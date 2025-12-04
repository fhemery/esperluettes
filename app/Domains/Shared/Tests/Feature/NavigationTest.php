<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class, RefreshDatabase::class);

function extractDivById(string $html, string $id): string
{
    $marker = 'id="' . $id . '"';
    $start = strpos($html, $marker);
    expect($start)->not()->toBeFalse("Start marker not found: {$id}");
    $divOpen = strrpos(substr($html, 0, $start), '<div');
    expect($divOpen)->not()->toBeFalse('Enclosing div not found');

    $offset = $divOpen;
    $depth = 0;
    $len = strlen($html);
    for ($i = $offset; $i < $len; $i++) {
        if (substr($html, $i, 4) === '<div') {
            $depth++;
            $i += 3;
            continue;
        }
        if (substr($html, $i, 6) === '</div>') {
            $depth--;
            if ($depth === 0) {
                $end = $i + 6;
                return substr($html, $offset, $end - $offset);
            }
            $i += 5;
        }
    }
    \PHPUnit\Framework\Assert::fail('Closing </div> not found for section');
}

function extractHrefs(string $html): array
{
    $hrefs = [];
    if (preg_match_all('/<a\s[^>]*href=("|\')(.*?)(\1)/i', $html, $m)) {
        foreach ($m[2] as $href) {
            $hrefs[] = html_entity_decode($href);
        }
    }
    // Deduplicate
    return array_values(array_unique($hrefs));
}

describe('Top navbar', function () {

    describe('Logged users', function () {


        function createUserForScenario($test, string $scenario)
        {
            return match ($scenario) {
                'unverified' => alice($test, [], false),
                'verified' => alice($test),
                'admins' => admin($test),
                default => alice($test),
            };
        }

        it("desktop links are present in the drawer for :scenario", function (string $scenario) {
            $user = createUserForScenario($this, $scenario);
            $this->actingAs($user);

            $routeName = $scenario === 'unverified' ? 'verification.notice' : 'dashboard';

            $html = $this->get(route($routeName))
                ->assertOk()
                ->getContent();

            // Sections
            $desktopLinksSection = extractDivById($html, 'desktop-nav-links');
            $drawerSection = extractDivById($html, 'desktop-nav-drawer');

            $desktopHrefs = extractHrefs($desktopLinksSection);
            $drawerHrefs = extractHrefs($drawerSection);

            // Assert every desktop link exists in the drawer
            $missing = array_values(array_diff($desktopHrefs, $drawerHrefs));
            if (!empty($missing)) {
                $msg = "Drawer is missing some desktop links ({$scenario}).\n" .
                    "Missing:\n- " . implode("\n- ", $missing) . "\n" .
                    "Desktop hrefs:\n- " . implode("\n- ", $desktopHrefs) . "\n" .
                    "Drawer hrefs:\n- " . implode("\n- ", $drawerHrefs);
                \PHPUnit\Framework\Assert::fail($msg);
            }

            expect(true)->toBeTrue();
        })->with([
            'unverified',
            'verified',
            'admins',
        ]);

        it('should show "My stories" link for confirmed users', function () {
            $user = alice($this);
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee(__('shared::navigation.my-stories'));
        });

        it('should not show "My stories" link for unconfirmed users', function () {
            $user = alice($this, roles: [Roles::USER]);
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee(__('shared::navigation.my-stories'));
        });

        it('verified: drawer contains profile, account and logout links', function () {
            $user = alice($this); // verified user
            $this->actingAs($user);

            $html = $this->get(route('dashboard'))
                ->assertOk()
                ->getContent();

            $drawerSection = extractDivById($html, 'desktop-nav-drawer');
            $hrefs = extractHrefs($drawerSection);

            $expected = [
                route('profile.show.own'),
                route('account.edit'),
                route('logout'),
            ];

            $missing = array_values(array_diff($expected, $hrefs));
            if (!empty($missing)) {
                $msg = "Verified drawer missing required links.\n" .
                    "Missing:\n- " . implode("\n- ", $missing) . "\n" .
                    "Drawer hrefs:\n- " . implode("\n- ", $hrefs);
                Assert::fail($msg);
            }

            expect(true)->toBeTrue();
        });

        it('shows notification bell without badge when unread count is zero', function () {
            $user = alice($this);
            $this->actingAs($user);

            $html = $this->get(route('dashboard'))
                ->assertOk()
                ->getContent();

            // Bell link present
            $notificationsUrl = route('notifications.index');
            expect($html)->toContain('href="' . $notificationsUrl . '"');
            expect($html)->toContain('unread-badge');
            expect($html)->toContain('initialCount: 0');
        });

        it('shows notification bell with unread badge when there are notifications', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Create one unread notification using the public API
            makeNotification([$user->id]);

            $html = $this->get(route('dashboard'))
                ->assertOk()
                ->getContent();

            // Bell link present and badge shown
            $notificationsUrl = route('notifications.index');
            expect($html)->toContain('href="' . $notificationsUrl . '"');
            expect($html)->toContain('unread-badge');
        });

        it('should show "Readlist" link for verified users', function () {
            $user = alice($this);
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee(__('shared::navigation.readlist'));
        });

        it('should not show "Readlist" link for guests', function () {
            $this->get(route('stories.index'))
                ->assertOk()
                ->assertDontSee(__('shared::navigation.readlist'));
        });

        describe('Promotion icon', function () {
            it('shows promotion icon for admin when there are pending requests', function () {
                $admin = admin($this);
                
                // Create a pending promotion request
                $user = registerUserThroughForm($this, [
                    'name' => 'NavPromoUser',
                    'email' => 'navpromo@example.com',
                ], true, [Roles::USER]);
                createPromotionRequest($user, commentCount: 5);

                $this->actingAs($admin);

                $html = $this->get(route('dashboard'))
                    ->assertOk()
                    ->getContent();

                expect($html)->toContain('psychiatry');
                expect($html)->toContain(route('auth.admin.promotion-requests.index'));
            });

            it('does not show promotion icon for regular users', function () {
                $user = alice($this);
                
                // Create a pending promotion request
                $otherUser = registerUserThroughForm($this, [
                    'name' => 'NavPromoUser4',
                    'email' => 'navpromo4@example.com',
                ], true, [Roles::USER]);
                createPromotionRequest($otherUser, commentCount: 5);

                $this->actingAs($user);

                $html = $this->get(route('dashboard'))
                    ->assertOk()
                    ->getContent();

                expect($html)->not->toContain('psychiatry');
            });
        });

        describe('Regarding discord', function() {
            it('should show discord link when configured', function () {
                $user = alice($this);
            $this->actingAs($user);
                $this->app->config->set('app.discord_url', 'https://discord.gg/discord');

                $this->get(route('dashboard'))
                    ->assertOk()
                    ->assertSee('Discord');
            });

            it('should not show discord link when not configured', function () {
                $user = alice($this);
            $this->actingAs($user);
                $this->app->config->set('app.discord_url', null);

                $this->get(route('dashboard'))
                    ->assertOk()
                    ->assertDontSee('Discord');
            });

            it('should not show discord link to guests', function () {
                $this->app->config->set('app.discord_url', 'https://discord.gg/discord');
                $this->get(route('home'))
                    ->assertOk()
                    ->assertDontSee('Discord');
            });
        });
    });
});
