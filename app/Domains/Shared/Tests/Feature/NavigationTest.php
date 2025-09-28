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

        it('should show "My stories" link for confirmed users', function() {
            $user = alice($this);
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee(__('shared::navigation.my-stories'));
        });

        it('should not show "My stories" link for unconfirmed users', function() {
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
    });
});
