<?php

namespace App\Domains\Statistics\Tests\Feature;

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
});

describe('TotalCommentsStatistic - Event-driven updates', function () {
    it('increments total_comments when a root comment is posted', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        expect(getStatisticValue('global.total_comments'))->toBeNull();

        createComment();

        expect(getStatisticValue('global.total_comments'))->toBe(1.0);
    });

    it('increments total_comments when a reply comment is posted', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $rootId = createComment(body: 'Root comment');
        createComment(body: 'Reply comment', parentCommentId: $rootId);

        expect(getStatisticValue('global.total_comments'))->toBe(2.0);
    });

    it('increments total_root_comments only for root comments', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $rootId = createComment(body: 'Root comment');
        createComment(body: 'Reply comment', parentCommentId: $rootId);
        createComment(body: 'Another root');

        expect(getStatisticValue('global.total_root_comments'))->toBe(2.0);
        expect(getStatisticValue('global.total_comments'))->toBe(3.0);
    });

    it('records time-series data for comment events', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $today = now()->format('Y-m-d');

        $rootId = createComment(body: 'Root comment');
        createComment(body: 'Reply comment', parentCommentId: $rootId);

        expect(getTimeSeriesValue('global.total_comments', $today))->toBe(2.0);
        expect(getTimeSeriesValue('global.total_root_comments', $today))->toBe(1.0);
    });
});

describe('TotalCommentsStatistic - Backfill from events', function () {
    it('backfills comment statistics by replaying stored events', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $rootId = createComment(body: 'Root comment');
        createComment(body: 'Reply comment', parentCommentId: $rootId);

        resetStatistics();

        expect(getStatisticValue('global.total_comments'))->toBeNull();
        expect(getStatisticValue('global.total_root_comments'))->toBeNull();

        $processed = backfillStatistic('global.total_comments');

        expect($processed)->toBe(2);
        expect(getStatisticValue('global.total_comments'))->toBe(2.0);
        expect(getStatisticValue('global.total_root_comments'))->toBe(1.0);
    });
});

describe('TotalCommentsStatistic - Full recompute', function () {
    it('recomputes comment statistics from stored events', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $rootId = createComment(body: 'Root comment');
        createComment(body: 'Reply comment', parentCommentId: $rootId);

        resetStatistics();

        $totalResult = recomputeStatistic('global.total_comments');
        $rootResult = recomputeStatistic('global.total_root_comments');

        expect($totalResult->snapshotValue)->toBe(2.0);
        expect($totalResult->eventsProcessed)->toBe(2);
        expect($rootResult->snapshotValue)->toBe(1.0);
        expect($rootResult->eventsProcessed)->toBe(1);
    });
});
