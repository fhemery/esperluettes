<?php

use App\Domains\Statistics\Private\Support\TimeSeriesResampler;
use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;

beforeEach(function () {
    $this->resampler = new TimeSeriesResampler();
});

function makePoint(string $date, float $value, ?float $cumulative = null): TimeSeriesPoint
{
    return new TimeSeriesPoint(
        periodStart: Carbon::parse($date)->startOfDay(),
        granularity: 'daily',
        value: $value,
        cumulativeValue: $cumulative,
    );
}

describe('TimeSeriesResampler', function () {
    it('returns empty array when from is after to', function () {
        $result = $this->resampler->resample(
            [],
            Carbon::parse('2026-01-10'),
            Carbon::parse('2026-01-01'),
        );

        expect($result)->toBe([]);
    });

    it('caps bucket count to range days when max points exceeds range', function () {
        $from = Carbon::parse('2026-01-01');
        $to = Carbon::parse('2026-01-30');

        $result = $this->resampler->resample([], $from, $to, maxPoints: 48);

        expect($result)->toHaveCount(30);
    });

    it('carry-forwards cumulative values across sparse gaps', function () {
        $points = [
            makePoint('2026-01-01', 1, 1),
            makePoint('2026-01-05', 2, 3),
        ];

        $result = $this->resampler->resample(
            $points,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-05'),
            maxPoints: 5,
            cumulative: true,
        );

        expect($result)->toHaveCount(5);
        expect($result[0]->cumulativeValue)->toBe(1.0);
        expect($result[1]->cumulativeValue)->toBe(1.0);
        expect($result[2]->cumulativeValue)->toBe(1.0);
        expect($result[3]->cumulativeValue)->toBe(1.0);
        expect($result[4]->cumulativeValue)->toBe(3.0);
    });

    it('starts cumulative series at zero before first data point', function () {
        $points = [
            makePoint('2026-01-03', 5, 5),
        ];

        $result = $this->resampler->resample(
            $points,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-03'),
            maxPoints: 3,
            cumulative: true,
        );

        expect($result[0]->cumulativeValue)->toBe(0.0);
        expect($result[1]->cumulativeValue)->toBe(0.0);
        expect($result[2]->cumulativeValue)->toBe(5.0);
    });

    it('sums daily values per bucket in delta mode', function () {
        $points = [
            makePoint('2026-01-01', 1),
            makePoint('2026-01-02', 2),
            makePoint('2026-01-04', 4),
        ];

        $result = $this->resampler->resample(
            $points,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-04'),
            maxPoints: 2,
            cumulative: false,
        );

        expect($result)->toHaveCount(2);
        expect($result[0]->value)->toBe(1.0);
        expect($result[1]->value)->toBe(6.0);
    });

    it('marks resampled points with bucket granularity', function () {
        $result = $this->resampler->resample(
            [makePoint('2026-01-01', 1, 1)],
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-01'),
            maxPoints: 1,
        );

        expect($result[0]->granularity)->toBe('bucket');
    });
});
