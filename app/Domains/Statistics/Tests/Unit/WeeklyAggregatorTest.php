<?php

use App\Domains\Statistics\Private\Support\WeeklyAggregator;
use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;

beforeEach(function () {
    $this->aggregator = new WeeklyAggregator();
});

function dailyPoint(string $date, float $value): TimeSeriesPoint
{
    return new TimeSeriesPoint(
        periodStart: Carbon::parse($date)->startOfDay(),
        granularity: 'daily',
        value: $value,
    );
}

/**
 * @param  TimeSeriesPoint[]  $points
 * @return array<string, float|int>
 */
function weeklyByMonday(array $points): array
{
    $result = [];
    foreach ($points as $point) {
        $result[$point->periodStart->format('Y-m-d')] = $point->value;
    }

    return $result;
}

describe('WeeklyAggregator', function () {
    it('returns an empty array when there are no daily points', function () {
        expect($this->aggregator->aggregate([], Carbon::parse('2026-01-21')))->toBe([]);
    });

    it('groups days Monday to Sunday into the week starting on Monday', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-12', 1),
            dailyPoint('2026-01-11', 2),
        ], Carbon::parse('2026-01-12'));

        expect(weeklyByMonday($result))->toEqual([
            '2026-01-05' => 2,
            '2026-01-12' => 1,
        ]);
    });

    it('emits a zero point for weeks without any daily row', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-05', 1),
            dailyPoint('2026-01-21', 4),
        ], Carbon::parse('2026-01-21'));

        expect(weeklyByMonday($result))->toEqual([
            '2026-01-05' => 1,
            '2026-01-12' => 0,
            '2026-01-19' => 4,
        ]);
    });

    it('keeps negative weekly nets', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-06', 3),
            dailyPoint('2026-01-08', -5),
        ], Carbon::parse('2026-01-09'));

        expect(weeklyByMonday($result))->toEqual(['2026-01-05' => -2]);
    });

    it('includes the current partial week as the last point', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-05', 1),
            dailyPoint('2026-01-20', 2),
        ], Carbon::parse('2026-01-21 15:30:00'));

        $last = $result[array_key_last($result)];
        expect($last->periodStart->format('Y-m-d'))->toBe('2026-01-19');
        expect($last->value)->toEqual(2);
    });

    it('extends to the current week even when the last activity is older', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-05', 1),
        ], Carbon::parse('2026-01-28'));

        expect(weeklyByMonday($result))->toEqual([
            '2026-01-05' => 1,
            '2026-01-12' => 0,
            '2026-01-19' => 0,
            '2026-01-26' => 0,
        ]);
    });

    it('handles the year boundary', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2025-12-29', 1),
            dailyPoint('2025-12-31', 2),
            dailyPoint('2026-01-04', 3),
        ], Carbon::parse('2026-01-04'));

        expect(weeklyByMonday($result))->toEqual(['2025-12-29' => 6]);

        $result = $this->aggregator->aggregate([
            dailyPoint('2026-12-28', 1),
            dailyPoint('2027-01-03', 2),
        ], Carbon::parse('2027-01-03'));

        expect(weeklyByMonday($result))->toEqual(['2026-12-28' => 3]);
    });

    it('sums weekly values to the total of the daily deltas', function () {
        $daily = [
            dailyPoint('2026-01-01', 4),
            dailyPoint('2026-01-03', -1),
            dailyPoint('2026-01-10', 7),
            dailyPoint('2026-01-19', 2.5),
            dailyPoint('2026-02-02', -3),
        ];

        $result = $this->aggregator->aggregate($daily, Carbon::parse('2026-02-10'));

        $weeklySum = array_sum(array_map(fn (TimeSeriesPoint $p) => $p->value, $result));
        expect($weeklySum)->toEqual(9.5);
    });

    it('marks every point weekly with a null cumulative value', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-05', 1),
        ], Carbon::parse('2026-01-21'));

        expect($result)->toHaveCount(3);
        foreach ($result as $point) {
            expect($point->granularity)->toBe('weekly');
            expect($point->cumulativeValue)->toBeNull();
            expect($point->periodStart->format('H:i:s'))->toBe('00:00:00');
        }
    });

    it('ignores daily points dated after the current week', function () {
        $result = $this->aggregator->aggregate([
            dailyPoint('2026-01-05', 1),
            dailyPoint('2026-01-26', 9),
        ], Carbon::parse('2026-01-14'));

        expect(weeklyByMonday($result))->toEqual([
            '2026-01-05' => 1,
            '2026-01-12' => 0,
        ]);
    });
});
