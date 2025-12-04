<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\PromotionAccepted;
use App\Domains\Auth\Public\Events\PromotionRejected;
use App\Domains\Auth\Public\Events\PromotionRequested;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Promotion Events', function () {

    describe('PromotionRequested', function () {

        it('is emitted when a promotion request is submitted', function () {
            // Set low thresholds BEFORE creating user
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            // Create eligible non-confirmed user
            $id = uniqid("user-");
            $user = registerUserThroughForm($this, [
                'name' => $id,
                'email' => 'probation-' . $id . '@example.com',
            ], true, [Roles::USER], false);

            // Submit promotion request via public API
            $api = app(\App\Domains\Auth\Public\Api\AuthPublicApi::class);
            $result = $api->requestPromotion($user->id, 10); // 10 comments

            expect($result->success)->toBeTrue();

            // Check event was persisted using the helper
            $event = latestEventOf(PromotionRequested::name(), PromotionRequested::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
        });

        it('has correct summary', function () {
            $event = new PromotionRequested(userId: 42);
            $summary = $event->summary();
            expect($summary)->toBeString();
            expect($summary)->not->toBeEmpty();
        });

        it('serializes and deserializes correctly', function () {
            $original = new PromotionRequested(userId: 123);
            $payload = $original->toPayload();
            $restored = PromotionRequested::fromPayload($payload);

            expect($restored->userId)->toBe(123);
        });

    });

    describe('PromotionAccepted', function () {

        it('is emitted when a promotion request is accepted', function () {
            $adminUser = admin($this);

            // Create user with pending request
            $user = registerUserThroughForm($this, [
                'name' => 'AcceptUser',
                'email' => 'accept@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            // Accept the request
            $service = app(\App\Domains\Auth\Private\Services\PromotionRequestService::class);
            $service->acceptRequest($request->id, $adminUser->id);

            // Check event was persisted
            $event = latestEventOf(PromotionAccepted::name(), PromotionAccepted::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->decidedBy)->toBe($adminUser->id);
        });

        it('has correct summary', function () {
            $event = new PromotionAccepted(userId: 42, decidedBy: 1);
            $summary = $event->summary();
            expect($summary)->toBeString();
            expect($summary)->not->toBeEmpty();
        });

        it('serializes and deserializes correctly', function () {
            $original = new PromotionAccepted(userId: 123, decidedBy: 456);
            $payload = $original->toPayload();
            $restored = PromotionAccepted::fromPayload($payload);

            expect($restored->userId)->toBe(123);
            expect($restored->decidedBy)->toBe(456);
        });

    });

    describe('PromotionRejected', function () {

        it('is emitted when a promotion request is rejected', function () {
            $adminUser = admin($this);

            // Create user with pending request
            $user = registerUserThroughForm($this, [
                'name' => 'RejectUser',
                'email' => 'reject@example.com',
            ], true, [Roles::USER]);
            $request = createPromotionRequest($user, commentCount: 5);

            // Reject the request
            $service = app(\App\Domains\Auth\Private\Services\PromotionRequestService::class);
            $service->rejectRequest($request->id, $adminUser->id, 'Test rejection reason');

            // Check event was persisted
            $event = latestEventOf(PromotionRejected::name(), PromotionRejected::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->decidedBy)->toBe($adminUser->id);
            expect($event->reason)->toBe('Test rejection reason');
        });

        it('has correct summary', function () {
            $event = new PromotionRejected(userId: 42, decidedBy: 1, reason: 'test');
            $summary = $event->summary();
            expect($summary)->toBeString();
            expect($summary)->not->toBeEmpty();
        });

        it('serializes and deserializes correctly', function () {
            $original = new PromotionRejected(userId: 123, decidedBy: 456, reason: 'test reason');
            $payload = $original->toPayload();
            $restored = PromotionRejected::fromPayload($payload);

            expect($restored->userId)->toBe(123);
            expect($restored->decidedBy)->toBe(456);
            expect($restored->reason)->toBe('test reason');
        });

    });

});
