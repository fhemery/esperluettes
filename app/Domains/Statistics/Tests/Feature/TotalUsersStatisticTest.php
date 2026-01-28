<?php

namespace App\Domains\Statistics\Tests\Feature;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
});

describe('TotalUsersStatistic - Event-driven updates', function () {
    it('increments total_users when UserRegistered event is emitted', function () {
        expect(getStatisticValue('global.total_users'))->toBeNull();

        dispatchEvent(new UserRegistered(userId: 1, displayName: 'Test User'));

        expect(getStatisticValue('global.total_users'))->toBe(1.0);

        dispatchEvent(new UserRegistered(userId: 2, displayName: 'Another User'));

        expect(getStatisticValue('global.total_users'))->toBe(2.0);
    });

    it('decrements total_users when UserDeleted event is emitted', function () {
        dispatchEvent(new UserRegistered(userId: 1, displayName: 'Test User'));
        dispatchEvent(new UserRegistered(userId: 2, displayName: 'Another User'));

        expect(getStatisticValue('global.total_users'))->toBe(2.0);

        dispatchEvent(new UserDeleted(userId: 1));

        expect(getStatisticValue('global.total_users'))->toBe(1.0);
    });

    it('records time-series data for events', function () {
        $today = now()->format('Y-m-d');

        dispatchEvent(new UserRegistered(userId: 1, displayName: 'Test User'));
        dispatchEvent(new UserRegistered(userId: 2, displayName: 'Another User'));

        expect(getTimeSeriesValue('global.total_users', $today))->toBe(2.0);

        dispatchEvent(new UserDeleted(userId: 1));

        expect(getTimeSeriesValue('global.total_users', $today))->toBe(1.0);
    });
});

describe('TotalUsersStatistic - Backfill from events', function () {
    it('backfills statistics by replaying stored events', function () {
        dispatchEvent(new UserRegistered(userId: 1, displayName: 'User 1'));
        dispatchEvent(new UserRegistered(userId: 2, displayName: 'User 2'));
        dispatchEvent(new UserDeleted(userId: 1));

        resetStatistics();

        expect(getStatisticValue('global.total_users'))->toBeNull();

        $processed = backfillStatistic('global.total_users');

        expect($processed)->toBe(3);
        expect(getStatisticValue('global.total_users'))->toBe(1.0);
    });

    it('backfills time-series data correctly', function () {
        $today = now()->format('Y-m-d');

        dispatchEvent(new UserRegistered(userId: 1, displayName: 'User 1'));
        dispatchEvent(new UserRegistered(userId: 2, displayName: 'User 2'));

        resetStatistics();

        expect(getTimeSeriesValue('global.total_users', $today))->toBeNull();

        backfillStatistic('global.total_users');

        expect(getTimeSeriesValue('global.total_users', $today))->toBe(2.0);
    });
});

describe('TotalUsersStatistic - Full recompute', function () {
    it('recomputes from events after users are created', function () {
        alice($this);
        bob($this);

        resetStatistics();

        $result = recomputeStatistic('global.total_users');

        expect($result->snapshotValue)->toBe(2.0);
        expect($result->eventsProcessed)->toBe(2);
    });
});
