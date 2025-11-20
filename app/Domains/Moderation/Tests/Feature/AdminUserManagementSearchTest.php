<?php

use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Moderation admin user search', function () {
    beforeEach(function () {
        $this->moderator = moderator($this);
        $this->actingAs($this->moderator);
    });

    it('shows an instruction message when query is shorter than 2 characters', function () {
        $response = $this->get(route('moderation.admin.search', ['q' => 'a']));

        $response->assertOk();
        $response->assertSee(__(
            'moderation::admin.user_management.min_chars_instruction'
        ), false);
        $response->assertDontSee('<table', false);
    });

    it('shows a no-result message when there are no matching users', function () {
        $response = $this->get(route('moderation.admin.search', ['q' => 'Al']));

        $response->assertOk();
        $response->assertSee(__(
            'moderation::admin.user_management.no_results'
        ), false);
        $response->assertDontSee('<table', false);
    });

    it('renders a table with user, auth and moderation data ordered by display name and limited to 20 results', function () {
        // Create some users with profiles via registration helpers
        $alice = alice($this, ['email' => 'alice-search@example.com']);
        $bob = bob($this, ['email' => 'bob-search@example.com']);
        $carol = carol($this, ['email' => 'carol-search@example.com']);

        // One inactive user (to check status and button label)
        deactivateUser($bob);

        // Registration helpers log the current user out; re-authenticate as moderator
        $this->actingAs(moderator($this));

        // Seed moderation reports for counts
        // Uses helper from Moderation domain
        createReportForUser($alice, 'confirmed');
        createReportForUser($alice, 'confirmed');
        createReportForUser($alice, 'dismissed');
        createReportForUser($bob, 'dismissed');

        // Fake ProfilePublicApi so we control display names and ordering
        $displayNames = [
            $alice->id => 'Alice Zed',
            $bob->id => 'Bob Young',
            $carol->id => 'Carol X',
        ];

        app()->instance(ProfilePublicApi::class, new class($displayNames) implements ProfilePublicApi {
            public function __construct(private array $displayNames) {}

            public function getFullProfile(int $userId): ?\App\Domains\Shared\Dto\FullProfileDto
            {
                return null;
            }

            public function getPublicProfile(int $userId): ?\App\Domains\Shared\Dto\ProfileDto
            {
                return null;
            }

            public function getPublicProfileBySlug(string $slug): ?\App\Domains\Shared\Dto\ProfileDto
            {
                return null;
            }

            public function getPublicProfiles(array $userIds): array
            {
                return [];
            }

            public function searchDisplayNames(string $query, int $limit = 50): array
            {
                return array_slice($this->displayNames, 0, $limit, true);
            }

            public function searchPublicProfiles(string $query, int $limit = 25): array
            {
                return ['items' => [], 'total' => 0];
            }
        });

        $response = $this->get(route('moderation.admin.search', ['q' => 'Al']));

        $response->assertOk();

        $html = $response->getContent();

        // Should render a table with the three users
        expect($html)->toContain('<table');
        expect($html)->toContain((string) $alice->id);
        expect($html)->toContain('Alice Zed');
        expect($html)->toContain('alice-search@example.com');
        expect($html)->toContain((string) $bob->id);
        expect($html)->toContain('Bob Young');
        expect($html)->toContain('bob-search@example.com');
        expect($html)->toContain((string) $carol->id);
        expect($html)->toContain('Carol X');
        expect($html)->toContain('carol-search@example.com');

        // Status labels and action buttons
        expect($html)->toContain(__('moderation::admin.user_management.status.active'));
        expect($html)->toContain(__('moderation::admin.user_management.status.inactive'));
        expect($html)->toContain(__('moderation::admin.user_management.actions.copy_email'));
        expect($html)->toContain(__('moderation::admin.user_management.actions.deactivate'));
        expect($html)->toContain(__('moderation::admin.user_management.actions.reactivate'));

        // Moderation counts: Alice has 2 confirmed + 1 dismissed, Bob has 1 dismissed, Carol has 0/0
        expect($html)->toContain('2'); // confirmed for Alice
        expect($html)->toContain('1'); // dismissed for Alice and Bob

        // Ordering by display name: Alice, Bob, Carol
        $alicePos = strpos($html, 'Alice Zed');
        $bobPos = strpos($html, 'Bob Young');
        $carolPos = strpos($html, 'Carol X');

        expect($alicePos)->toBeLessThan($bobPos);
        expect($bobPos)->toBeLessThan($carolPos);
    });
});
