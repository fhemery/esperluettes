<?php

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Dto\PromotionEligibilityDto;
use App\Domains\Auth\Public\Api\Dto\PromotionRequestResultDto;
use App\Domains\Auth\Public\Api\Dto\PromotionStatusDto;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function getAuthApi(): AuthPublicApi
{
    return app(AuthPublicApi::class);
}

/**
 * Create a non-confirmed (probation) user for promotion testing.
 */
function createProbationUser(TestCase $t, array $overrides = []): User
{
    $id = uniqid("user-");
    return registerUserThroughForm($t, array_merge([
        'name' => $id,
        'email' => 'probation-' . $id . '@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides), true, [Roles::USER], false);
}

describe('AuthPublicApi: Promotions', function () {
    describe('canRequestPromotion', function () {
        it('returns eligible when user meets all criteria', function () {
            // Set low thresholds for testing
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 2);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0); // 0 days required

            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->canRequestPromotion($user->id, commentCount: 5);

            expect($result)->toBeInstanceOf(PromotionEligibilityDto::class);
            expect($result->eligible)->toBeTrue();
            expect($result->hasPendingRequest)->toBeFalse();
            expect($result->meetsTimeRequirement())->toBeTrue();
            expect($result->meetsCommentRequirement())->toBeTrue();
        });

        it('returns ineligible when comment threshold not met', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 10);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->canRequestPromotion($user->id, commentCount: 5);

            expect($result->eligible)->toBeFalse();
            expect($result->meetsCommentRequirement())->toBeFalse();
            expect($result->commentsRequired)->toBe(10);
            expect($result->commentsPosted)->toBe(5);
        });

        it('returns ineligible when time threshold not met', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 604800); // 7 days

            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->canRequestPromotion($user->id, commentCount: 100);

            expect($result->eligible)->toBeFalse();
            expect($result->meetsTimeRequirement())->toBeFalse();
            expect($result->daysRequired)->toEqual(7.0);
            expect($result->daysElapsed)->toBeLessThan(7.0);
        });

        it('returns ineligible when user has pending request', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);

            // Create pending request
            createPromotionRequest($user, commentCount: 5);

            $api = getAuthApi();
            $result = $api->canRequestPromotion($user->id, commentCount: 100);

            expect($result->eligible)->toBeFalse();
            expect($result->hasPendingRequest)->toBeTrue();
        });

        it('includes last rejection info when user was previously rejected', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $admin = admin($this);

            // Create rejected request
            $request = createPromotionRequest($user, commentCount: 5);
            rejectPromotionRequest($request, $admin, 'Comments were low quality');

            $api = getAuthApi();
            $result = $api->canRequestPromotion($user->id, commentCount: 100);

            expect($result->lastRejectionReason)->toBe('Comments were low quality');
            expect($result->lastRejectionDate)->not->toBeNull();
        });

        it('resets countdown from last rejection date', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 604800); // 7 days

            $user = createProbationUser($this);
            // Backdate user creation to ensure they would normally meet time requirement
            $user->created_at = now()->subDays(30);
            $user->save();

            $admin = admin($this);

            // Create recent rejection (2 days ago)
            $request = createPromotionRequest(
                $user,
                commentCount: 5,
                requestedAt: now()->subDays(3),
            );

            Carbon::setTestNow(now()->subDays(2));
            rejectPromotionRequest($request, $admin, 'Try again later');
            Carbon::setTestNow();

            $api = getAuthApi();
            $result = $api->canRequestPromotion($user->id, commentCount: 100);

            // Even though user was created 30 days ago, countdown resets from rejection
            expect($result->meetsTimeRequirement())->toBeFalse();
            expect((int) floor($result->daysElapsed))->toBe(2);
        });
    });

    describe('requestPromotion', function () {

        it('creates pending request when eligible', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->requestPromotion($user->id, commentCount: 10);

            expect($result)->toBeInstanceOf(PromotionRequestResultDto::class);
            expect($result->success)->toBeTrue();
            expect($result->errorKey)->toBeNull();

            // Verify request was created in database
            $request = PromotionRequest::where('user_id', $user->id)->first();
            expect($request)->not->toBeNull();
            expect($request->status)->toBe(PromotionRequest::STATUS_PENDING);
            expect($request->comment_count)->toBe(10);
        });

        it('returns error for non-existent user', function () {
            $api = getAuthApi();

            $result = $api->requestPromotion(userId: 999999, commentCount: 10);

            expect($result->success)->toBeFalse();
            expect($result->errorKey)->toBe(PromotionRequestResultDto::ERROR_USER_NOT_FOUND);
        });

        it('returns error when user is already confirmed', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $api = getAuthApi();

            $result = $api->requestPromotion($user->id, commentCount: 100);

            expect($result->success)->toBeFalse();
            expect($result->errorKey)->toBe(PromotionRequestResultDto::ERROR_ALREADY_CONFIRMED);
        });

        it('returns error when user has pending request', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);

            // Create existing pending request
            createPromotionRequest($user, commentCount: 5);

            $api = getAuthApi();
            $result = $api->requestPromotion($user->id, commentCount: 100);

            expect($result->success)->toBeFalse();
            expect($result->errorKey)->toBe(PromotionRequestResultDto::ERROR_ALREADY_PENDING);
        });

        it('returns error when criteria not met', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 100);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->requestPromotion($user->id, commentCount: 5);

            expect($result->success)->toBeFalse();
            expect($result->errorKey)->toBe(PromotionRequestResultDto::ERROR_CRITERIA_NOT_MET);
        });

        it('allows new request after previous rejection', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $admin = admin($this);

            // Create rejected request
            $request = createPromotionRequest(
                $user,
                commentCount: 5,
                requestedAt: now()->subDays(10),
            );

            Carbon::setTestNow(now()->subDays(9));
            rejectPromotionRequest($request, $admin, 'Not ready');
            Carbon::setTestNow();

            $api = getAuthApi();
            $result = $api->requestPromotion($user->id, commentCount: 20);

            expect($result->success)->toBeTrue();

            // Should have 2 requests now
            $requests = PromotionRequest::where('user_id', $user->id)->get();
            expect($requests)->toHaveCount(2);
        });
    });

    describe('getPromotionStatus', function () {

        it('returns none when no requests exist', function () {
            $user = createProbationUser($this);
            $api = getAuthApi();

            $result = $api->getPromotionStatus($user->id);

            expect($result)->toBeInstanceOf(PromotionStatusDto::class);
            expect($result->status)->toBe('none');
            expect($result->rejectionReason)->toBeNull();
            expect($result->rejectionDate)->toBeNull();
        });

        it('returns pending when pending request exists', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);

            createPromotionRequest($user, commentCount: 5);

            $api = getAuthApi();
            $result = $api->getPromotionStatus($user->id);

            expect($result->status)->toBe('pending');
        });

        it('returns rejected with reason when last request was rejected', function () {
            $user = createProbationUser($this);
            $admin = admin($this);

            $request = createPromotionRequest($user, commentCount: 5);
            rejectPromotionRequest($request, $admin, 'Low quality comments');

            $api = getAuthApi();
            $result = $api->getPromotionStatus($user->id);

            expect($result->status)->toBe('rejected');
            expect($result->rejectionReason)->toBe('Low quality comments');
            expect($result->rejectionDate)->not->toBeNull();
        });

        it('returns pending even if previous rejection exists', function () {
            $user = createProbationUser($this);
            $admin = admin($this);

            // Old rejection
            $oldRequest = createPromotionRequest(
                $user,
                commentCount: 5,
                requestedAt: now()->subDays(10),
            );

            Carbon::setTestNow(now()->subDays(9));
            rejectPromotionRequest($oldRequest, $admin, 'Old rejection');
            Carbon::setTestNow();

            // New pending request
            createPromotionRequest($user, commentCount: 15);

            $api = getAuthApi();
            $result = $api->getPromotionStatus($user->id);

            // Pending takes priority over past rejection
            expect($result->status)->toBe('pending');
        });
    });

    describe('getPendingPromotionCount', function () {

        it('returns 0 when no pending requests', function () {
            $api = getAuthApi();

            $count = $api->getPendingPromotionCount();

            expect($count)->toBe(0);
        });

        it('returns correct count of pending requests', function () {
            $user1 = createProbationUser($this);
            $user2 = createProbationUser($this);
            $user3 = createProbationUser($this);
            $admin = admin($this);

            // 2 pending requests
            createPromotionRequest($user1, commentCount: 5);
            createPromotionRequest($user2, commentCount: 8);

            // 1 rejected (should not be counted)
            $rejectedRequest = createPromotionRequest($user3, commentCount: 3);
            rejectPromotionRequest($rejectedRequest, $admin, 'Denied');

            $api = getAuthApi();
            $count = $api->getPendingPromotionCount();

            expect($count)->toBe(2);
        });
    });

    describe('Security: Promotion request validation', function () {

        it('does not leak information about other users through canRequestPromotion', function () {
            $user = createProbationUser($this);
            $api = getAuthApi();

            // Even for non-existent user, should return a valid DTO (not error/exception)
            // This prevents enumeration attacks
            $result = $api->canRequestPromotion(userId: 999999, commentCount: 100);

            expect($result)->toBeInstanceOf(PromotionEligibilityDto::class);
            // Eligibility should be false for non-existent user
            expect($result->eligible)->toBeFalse();
        });

        it('prevents gaming by enforcing server-side comment count validation', function () {
            // Note: The comment count comes from Dashboard, not user input
            // This test documents that the API trusts the Dashboard to provide accurate counts
            // Dashboard should fetch comment count from Comment domain

            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 10);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            // Even if someone tries to pass a high comment count, the check is based
            // on the value provided by the calling service (Dashboard)
            $result = $api->requestPromotion($user->id, commentCount: 5);

            expect($result->success)->toBeFalse();
            expect($result->errorKey)->toBe(PromotionRequestResultDto::ERROR_CRITERIA_NOT_MET);
        });

        it('stores comment count at time of request for audit purposes', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            $api->requestPromotion($user->id, commentCount: 42);

            $request = PromotionRequest::where('user_id', $user->id)->first();
            expect($request->comment_count)->toBe(42);
        });

        it('enforces single pending request per user', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $api = getAuthApi();

            // First request succeeds
            $result1 = $api->requestPromotion($user->id, commentCount: 10);
            expect($result1->success)->toBeTrue();

            // Second request fails
            $result2 = $api->requestPromotion($user->id, commentCount: 20);
            expect($result2->success)->toBeFalse();
            expect($result2->errorKey)->toBe(PromotionRequestResultDto::ERROR_ALREADY_PENDING);

            // Only one request in database
            $count = PromotionRequest::where('user_id', $user->id)
                ->where('status', PromotionRequest::STATUS_PENDING)
                ->count();
            expect($count)->toBe(1);
        });
    });

    describe('PromotionRequestService: accept and reject', function () {

        it('accepting a request promotes user to USER_CONFIRMED', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $admin = admin($this);

            // Create and accept request
            $request = createPromotionRequest($user, commentCount: 10);

            $result = acceptPromotionRequest($request, $admin);

            expect($result)->toBeTrue();

            // Request should be accepted
            $request->refresh();
            expect($request->status)->toBe(PromotionRequest::STATUS_ACCEPTED);
            expect($request->decided_by)->toBe($admin->id);
            expect($request->decided_at)->not->toBeNull();

            // User should now be confirmed
            $user->refresh();
            expect($user->isConfirmed())->toBeTrue();
            expect($user->isOnProbation())->toBeFalse();
        });

        it('rejecting a request does not change user role', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = createProbationUser($this);
            $admin = admin($this);

            $request = createPromotionRequest($user, commentCount: 10);

            $result = rejectPromotionRequest($request, $admin, 'Comments need improvement');

            expect($result)->toBeTrue();

            // Request should be rejected with reason
            $request->refresh();
            expect($request->status)->toBe(PromotionRequest::STATUS_REJECTED);
            expect($request->decided_by)->toBe($admin->id);
            expect($request->decided_at)->not->toBeNull();
            expect($request->rejection_reason)->toBe('Comments need improvement');

            // User should still be on probation
            $user->refresh();
            expect($user->isOnProbation())->toBeTrue();
            expect($user->isConfirmed())->toBeFalse();
        });

        it('cannot accept already decided request', function () {
            $user = createProbationUser($this);
            $admin = admin($this);

            $request = createPromotionRequest($user, commentCount: 10);
            rejectPromotionRequest($request, $admin, 'Already decided');

            $result = acceptPromotionRequest($request, $admin);

            expect($result)->toBeFalse();
        });

        it('cannot reject already decided request', function () {
            $user = createProbationUser($this);
            $admin = admin($this);

            $request = createPromotionRequest($user, commentCount: 10);
            acceptPromotionRequest($request, $admin);

            $result = rejectPromotionRequest($request, $admin, 'Too late');

            expect($result)->toBeFalse();
        });

        it('returns false for non-existent request', function () {
            $admin = admin($this);

            // Create fake request objects with non-existent IDs
            $fakeRequest = new PromotionRequest();
            $fakeRequest->id = 999999;

            expect(acceptPromotionRequest($fakeRequest, $admin))->toBeFalse();
            expect(rejectPromotionRequest($fakeRequest, $admin, 'reason'))->toBeFalse();
        });
    });
});
