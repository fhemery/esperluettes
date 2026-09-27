<?php

namespace App\Domains\Statistics\Tests\Feature;

use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Services\StatisticQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
    Carbon::setTestNow('2026-01-21');
});

afterEach(function () {
    Carbon::setTestNow();
});

function seedWeeklyDaily(
    string $date,
    float $value,
    string $key = 'global.total_users',
    string $scopeType = 'global',
    ?int $scopeId = null,
    string $granularity = 'daily',
): void {
    StatisticTimeSeries::create([
        'statistic_key' => $key,
        'scope_type' => $scopeType,
        'scope_id' => $scopeId,
        'granularity' => $granularity,
        'period_start' => $date,
        'value' => $value,
        'cumulative_value' => null,
    ]);
}

function weeklySeries(string $key = 'global.total_users'): array
{
    $result = [];
    foreach (app(StatisticQueryService::class)->getWeeklyTimeSeries($key) as $point) {
        $result[$point->periodStart->format('Y-m-d')] = $point->value;
    }

    return $result;
}

describe('StatisticQueryService::getWeeklyTimeSeries', function () {
    it('returns an empty array for a metric without daily rows', function () {
        expect(app(StatisticQueryService::class)->getWeeklyTimeSeries('global.total_users'))->toBe([]);
    });

    it('aggregates stored daily rows into Monday-based weeks up to the current week', function () {
        seedWeeklyDaily('2026-01-11', 2);
        seedWeeklyDaily('2026-01-12', 1);

        $points = app(StatisticQueryService::class)->getWeeklyTimeSeries('global.total_users');

        expect($points)->toHaveCount(3);
        expect($points[0]->granularity)->toBe('weekly');
        expect(weeklySeries())->toEqual([
            '2026-01-05' => 2,
            '2026-01-12' => 1,
            '2026-01-19' => 0,
        ]);
    });

    it('keeps a negative weekly net', function () {
        seedWeeklyDaily('2026-01-19', 3);
        seedWeeklyDaily('2026-01-20', -5);

        expect(weeklySeries())->toEqual(['2026-01-19' => -2]);
    });

    it('ignores rows of another statistic key and of another scope', function () {
        seedWeeklyDaily('2026-01-12', 1);
        seedWeeklyDaily('2026-01-05', 10, key: 'global.total_comments');
        seedWeeklyDaily('2026-01-05', 20, scopeType: 'user', scopeId: 1);

        expect(weeklySeries())->toEqual([
            '2026-01-12' => 1,
            '2026-01-19' => 0,
        ]);
    });

    it('ignores non-daily rows', function () {
        seedWeeklyDaily('2026-01-12', 1);
        seedWeeklyDaily('2026-01-05', 50, granularity: 'monthly');

        expect(weeklySeries())->toEqual([
            '2026-01-12' => 1,
            '2026-01-19' => 0,
        ]);
    });

    it('sums to the metric cumulative total', function () {
        $daily = ['2025-11-03' => 5, '2025-12-24' => 3, '2026-01-02' => -2, '2026-01-20' => 4];
        foreach ($daily as $date => $value) {
            seedWeeklyDaily($date, $value);
        }

        expect(array_sum(weeklySeries()))->toEqual(array_sum($daily));
    });
});
