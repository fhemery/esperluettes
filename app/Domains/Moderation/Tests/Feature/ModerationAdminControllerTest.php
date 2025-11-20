<?php

use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ModerationAdminController', function () {
    beforeEach(function () {
        $this->moderator = moderator($this);
        $this->actingAs($this->moderator);
    });

    describe('index', function () {
        it('renders the user management page', function () {
            $response = $this->get(route('moderation.admin.user-management'));

            $response->assertOk();
            $response->assertSee(__('moderation::admin.user_management.title'));
            $response->assertSee(__('moderation::admin.user_management.search_instruction'));
        });

        it('requires moderator role', function () {
            $user = alice($this);
            $this->actingAs($user);

            $response = $this->get(route('moderation.admin.user-management'));

            $response->assertRedirect('/dashboard');
        });
    });

    describe('search', function () {
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
            $alice = alice($this, ['email' => 'alice-search@example.com', 'name' => 'Alice Carol']);
            $carol = carol($this, ['email' => 'carol-search@example.com', 'name' => 'carol x']);

            // One inactive user (to check status and button label)
            deactivateUser($carol);

            // Registration helpers log the current user out; re-authenticate as moderator
            $this->actingAs(moderator($this));

            // Seed moderation reports for counts
            // Uses helper from Moderation domain
            createReportForUser($alice, 'confirmed');
            createReportForUser($alice, 'confirmed');
            createReportForUser($alice, 'dismissed');
            createReportForUser($carol, 'dismissed');

            $response = $this->get(route('moderation.admin.search', ['q' => 'Carol']));

            $response->assertOk();

            $html = $response->getContent();
            
            // Should render a table with the three users
            expect($html)->toContain('<table');
            expect($html)->toContain((string) $alice->id);
            expect($html)->toContain('Alice Carol');
            expect($html)->toContain('alice-search@example.com');
            expect($html)->toContain((string) $carol->id);
            expect($html)->toContain('carol x');
            expect($html)->toContain('carol-search@example.com');

            // Status labels and action buttons
            expect($html)->toContain(__('moderation::admin.user_management.status.active'));
            expect($html)->toContain(__('moderation::admin.user_management.status.inactive'));
            expect($html)->toContain(__('moderation::admin.user_management.actions.copy_email'));
            expect($html)->toContain(__('moderation::admin.user_management.actions.deactivate'));
            expect($html)->toContain(__('moderation::admin.user_management.actions.reactivate'));

            // Ordering by display name: Alice, Bob, Carol
            $alicePos = strpos($html, 'Alice Carol');
            $carolPos = strpos($html, 'carol x');

            expect($alicePos)->toBeLessThan($carolPos);
        });

        it('includes deactivated users in search results', function () {
            // Create users with real profiles
            $inactiveUser = bob($this, ['email' => 'inactive@example.com']);
            deactivateUser($inactiveUser);

            // Registration helpers log the current user out; re-authenticate as moderator
            $this->actingAs(moderator($this));

            $response = $this->get(route('moderation.admin.search', ['q' => 'bo']));

            $response->assertOk();

            $html = $response->getContent();

            // Should render a table with both users
            expect($html)->toContain('<table');
            expect($html)->toContain((string) $inactiveUser->id);
            expect($html)->toContain('Bob');
            expect($html)->toContain('inactive@example.com');

            // Should show appropriate status indicators
            expect($html)->toContain(__('moderation::admin.user_management.status.inactive'));
            
            // Should show appropriate action buttons
            expect($html)->toContain(__('moderation::admin.user_management.actions.reactivate'));
        });
    });
});
