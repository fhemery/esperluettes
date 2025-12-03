<?php

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Notifications\PromotionAcceptedNotification;
use App\Domains\Auth\Public\Notifications\PromotionRejectedNotification;
use App\Domains\Notification\Private\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin Promotion Request Controller', function () {

    describe('index', function () {

        it('requires authentication', function () {
            $response = $this->get(route('auth.admin.promotion-requests.index'));

            $response->assertRedirect(route('login'));
        });

        it('requires admin/tech-admin/moderator role', function () {
            $user = registerUserThroughForm($this, [
                'email' => 'regular@example.com',
            ], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('auth.admin.promotion-requests.index'));

            $response->assertStatus(302); // Redirected by middleware
        });

        it('allows admin to access', function () {
            $adminUser = admin($this);

            $response = $this->actingAs($adminUser)
                ->get(route('auth.admin.promotion-requests.index'));

            $response->assertOk();
            $response->assertSee(__('auth::admin.promotion.title'));
        });

        it('allows moderator to access', function () {
            $modUser = moderator($this);

            $response = $this->actingAs($modUser)
                ->get(route('auth.admin.promotion-requests.index'));

            $response->assertOk();
        });

        it('shows pending requests by default', function () {
            $adminUser = admin($this);

            // Create a pending request
            $user = registerUserThroughForm($this, [
                'name' => 'PendingUser',
                'email' => 'pending@example.com',
            ], true, [Roles::USER]);
            createPromotionRequest($user, commentCount: 5);

            // Create an accepted request
            $user2 = registerUserThroughForm($this, [
                'name' => 'AcceptedUser',
                'email' => 'accepted@example.com',
            ], true, [Roles::USER_CONFIRMED]);
            $acceptedRequest = createPromotionRequest($user2, commentCount: 10);
            $acceptedRequest->accept($adminUser->id);

            $response = $this->actingAs($adminUser)
                ->get(route('auth.admin.promotion-requests.index'));

            $response->assertOk();
            $response->assertSee('PendingUser'); // Via profile display name
            $response->assertDontSee('AcceptedUser');
        });

        it('can filter by all statuses', function () {
            $adminUser = admin($this);

            // Create requests with different statuses
            $user1 = registerUserThroughForm($this, [
                'name' => 'FilterPending',
                'email' => 'filter-pending@example.com',
            ], true, [Roles::USER]);
            createPromotionRequest($user1, commentCount: 5);

            $user2 = registerUserThroughForm($this, [
                'name' => 'FilterAccepted',
                'email' => 'filter-accepted@example.com',
            ], true, [Roles::USER_CONFIRMED]);
            $acceptedRequest = createPromotionRequest($user2, commentCount: 10);
            $acceptedRequest->accept($adminUser->id);

            $response = $this->actingAs($adminUser)
                ->get(route('auth.admin.promotion-requests.index', ['status' => 'all']));

            $response->assertOk();
            // Both should appear when showing all
            $response->assertSee(__('auth::admin.promotion.status.pending'));
            $response->assertSee(__('auth::admin.promotion.status.accepted'));
        });

    });

    describe('accept', function () {

        it('accepts a pending promotion request', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'toaccept@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            $response = $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.accept', $request));

            $response->assertRedirect(route('auth.admin.promotion-requests.index'));
            $response->assertSessionHas('success');

            // Request should be accepted
            $request->refresh();
            expect($request->status)->toBe('accepted');
            expect($request->decided_by)->toBe($adminUser->id);

            // User should now have USER_CONFIRMED role
            $user->refresh();
            expect($user->hasRole(Roles::USER_CONFIRMED))->toBeTrue();
            expect($user->hasRole(Roles::USER))->toBeFalse();
        });

        it('sends notification to user on acceptance', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'notify@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.accept', $request));

            // Check notification was created
            $notification = Notification::where('content_key', PromotionAcceptedNotification::type())
                ->first();

            expect($notification)->not->toBeNull();
        });

        it('returns error for non-pending request', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'already@example.com',
            ], true, [Roles::USER_CONFIRMED]);
            $request = createPromotionRequest($user, commentCount: 5);
            $request->accept($adminUser->id); // Already accepted

            $response = $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.accept', $request));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });

    });

    describe('reject', function () {

        it('rejects a pending promotion request with reason', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'toreject@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            $response = $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.reject', $request), [
                    'rejection_reason' => 'Comments need more substance',
                ]);

            $response->assertRedirect(route('auth.admin.promotion-requests.index'));
            $response->assertSessionHas('success');

            // Request should be rejected with reason
            $request->refresh();
            expect($request->status)->toBe('rejected');
            expect($request->rejection_reason)->toBe('Comments need more substance');
            expect($request->decided_by)->toBe($adminUser->id);

            // User should still have USER role
            $user->refresh();
            expect($user->hasRole(Roles::USER))->toBeTrue();
        });

        it('requires rejection reason', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'noreject@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            $response = $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.reject', $request), [
                    'rejection_reason' => '',
                ]);

            $response->assertSessionHasErrors('rejection_reason');
        });

        it('sends notification to user on rejection', function () {
            $adminUser = admin($this);
            $user = registerUserThroughForm($this, [
                'email' => 'rejectnotify@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            $this->actingAs($adminUser)
                ->post(route('auth.admin.promotion-requests.reject', $request), [
                    'rejection_reason' => 'Need more engagement',
                ]);

            // Check notification was created
            $notification = Notification::where('content_key', PromotionRejectedNotification::type())
                ->first();

            expect($notification)->not->toBeNull();
        });

    });

});
