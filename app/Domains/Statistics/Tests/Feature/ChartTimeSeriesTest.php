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
    Carbon::setTestNow('2026-01-30');
});

afterEach(function () {
    Carbon::setTestNow();
});

function seedDailyPoint(string $date, float $value, ?float $cumulative = null, string $key = 'global.total_users'): void
{
    StatisticTimeSeries::create([
        'statistic_key' => $key,
        'scope_type' => 'global',
        'scope_id' => null,
        'granularity' => 'daily',
        'period_start' => $date,
        'value' => $value,
        'cumulative_value' => $cumulative,
    ]);
}

describe('StatisticQueryService::getChartTimeSeries', function () {
    it('returns empty array when no data exists', function () {
        $service = app(StatisticQueryService::class);

        expect($service->getChartTimeSeries('global.total_users'))->toBe([]);
    });

    it('defaults to all time from first data point through today', function () {
        seedDailyPoint('2026-01-01', 1, 1);
        seedDailyPoint('2026-01-15', 2, 3);

        $service = app(StatisticQueryService::class);
        $result = $service->getChartTimeSeries('global.total_users', maxPoints: 48);

        expect($result)->not->toBeEmpty();
        expect($result[0]->periodStart->format('Y-m-d'))->toBe('2026-01-01');
        expect($result[array_key_last($result)]->periodStart->format('Y-m-d'))->toBe('2026-01-30');
    });

    it('respects explicit from and to filters', function () {
        seedDailyPoint('2026-01-01', 1, 1);
        seedDailyPoint('2026-01-10', 1, 2);
        seedDailyPoint('2026-01-20', 1, 3);

        $service = app(StatisticQueryService::class);
        $result = $service->getChartTimeSeries(
            'global.total_users',
            from: Carbon::parse('2026-01-10'),
            to: Carbon::parse('2026-01-15'),
            maxPoints: 6,
        );

        expect($result)->toHaveCount(6);
        expect($result[0]->periodStart->format('Y-m-d'))->toBe('2026-01-10');
        expect($result[array_key_last($result)]->periodStart->format('Y-m-d'))->toBe('2026-01-15');
        expect($result[array_key_last($result)]->cumulativeValue)->toBe(2.0);
    });

    it('returns resampled bucket points instead of raw daily rows', function () {
        for ($day = 1; $day <= 10; $day++) {
            seedDailyPoint(sprintf('2026-01-%02d', $day), 1, (float) $day);
        }

        $service = app(StatisticQueryService::class);
        $result = $service->getChartTimeSeries(
            'global.total_users',
            from: Carbon::parse('2026-01-01'),
            to: Carbon::parse('2026-01-10'),
            maxPoints: 5,
        );

        expect($result)->toHaveCount(5);
        expect($result[0]->granularity)->toBe('bucket');
    });

    it('seeds the starting level from the last row before from (cumulative rows)', function () {
        seedDailyPoint('2025-06-01', 5, 5);
        seedDailyPoint('2025-07-01', 5, 10);

        $result = app(StatisticQueryService::class)->getChartTimeSeries(
            'global.total_users',
            from: Carbon::parse('2026-01-10'),
            maxPoints: 5,
        );

        expect($result)->toHaveCount(5);
        foreach ($result as $point) {
            expect($point->cumulativeValue)->toBe(10.0);
        }
    });

    it('seeds the starting level by summing deltas when rows carry no cumulative value', function () {
        seedDailyPoint('2025-06-01', 5);
        seedDailyPoint('2025-07-01', 3);
        seedDailyPoint('2026-01-20', 2);

        $result = app(StatisticQueryService::class)->getChartTimeSeries(
            'global.total_users',
            from: Carbon::parse('2026-01-10'),
            maxPoints: 21,
        );

        expect($result[0]->cumulativeValue)->toBe(8.0)
            ->and($result[array_key_last($result)]->cumulativeValue)->toBe(10.0);
    });

    it('starts at 0 when nothing precedes from', function () {
        seedDailyPoint('2026-01-20', 2, 2);

        $result = app(StatisticQueryService::class)->getChartTimeSeries(
            'global.total_users',
            from: Carbon::parse('2026-01-10'),
            maxPoints: 21,
        );

        expect($result[0]->cumulativeValue)->toBe(0.0)
            ->and($result[array_key_last($result)]->cumulativeValue)->toBe(2.0);
    });
});
