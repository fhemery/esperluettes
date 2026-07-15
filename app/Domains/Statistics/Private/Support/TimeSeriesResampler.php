<?php

namespace App\Domains\Statistics\Private\Support;

use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;
use DateTimeInterface;

class TimeSeriesResampler
{
    /**
     * Resample sparse daily points into evenly spaced buckets over a date range.
     *
     * @param  TimeSeriesPoint[]  $points
     * @return TimeSeriesPoint[]
     */
    public function resample(
        array $points,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $maxPoints = 48,
        bool $cumulative = true,
    ): array {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();

        if ($from->gt($to)) {
            return [];
        }

        $rangeDays = $from->diffInDays($to) + 1;
        $bucketCount = min($maxPoints, $rangeDays);

        if ($bucketCount <= 0) {
            return [];
        }

        $bucketEnds = $this->buildBucketEnds($from, $to, $bucketCount);

        if ($cumulative) {
            return $this->resampleCumulative($points, $bucketEnds);
        }

        return $this->resampleDelta($points, $bucketEnds);
    }

    /**
     * @return Carbon[]
     */
    private function buildBucketEnds(Carbon $from, Carbon $to, int $bucketCount): array
    {
        if ($bucketCount === 1) {
            return [$to->copy()];
        }

        $bucketEnds = [];
        $totalSeconds = $from->diffInSeconds($to);

        for ($i = 0; $i < $bucketCount; $i++) {
            if ($i === $bucketCount - 1) {
                $bucketEnds[] = $to->copy();
            } else {
                $offset = (int) round($totalSeconds * $i / ($bucketCount - 1));
                $bucketEnds[] = $from->copy()->addSeconds($offset);
            }
        }

        return $bucketEnds;
    }

    /**
     * @param  TimeSeriesPoint[]  $points
     * @param  Carbon[]  $bucketEnds
     * @return TimeSeriesPoint[]
     */
    private function resampleCumulative(array $points, array $bucketEnds): array
    {
        $result = [];
        $idx = 0;
        $count = count($points);
        $lastCumulative = 0.0;

        foreach ($bucketEnds as $bucketEnd) {
            while ($idx < $count && Carbon::parse($points[$idx]->periodStart)->startOfDay()->lte($bucketEnd)) {
                $point = $points[$idx];

                if ($point->cumulativeValue !== null) {
                    $lastCumulative = (float) $point->cumulativeValue;
                } else {
                    $lastCumulative += (float) $point->value;
                }

                $idx++;
            }

            $result[] = new TimeSeriesPoint(
                periodStart: $bucketEnd,
                granularity: 'bucket',
                value: 0,
                cumulativeValue: $lastCumulative,
            );
        }

        return $result;
    }

    /**
     * @param  TimeSeriesPoint[]  $points
     * @param  Carbon[]  $bucketEnds
     * @return TimeSeriesPoint[]
     */
    private function resampleDelta(array $points, array $bucketEnds): array
    {
        $result = [];
        $idx = 0;
        $count = count($points);

        foreach ($bucketEnds as $bucketEnd) {
            $bucketSum = 0.0;

            while ($idx < $count && Carbon::parse($points[$idx]->periodStart)->startOfDay()->lte($bucketEnd)) {
                $bucketSum += (float) $points[$idx]->value;
                $idx++;
            }

            $result[] = new TimeSeriesPoint(
                periodStart: $bucketEnd,
                granularity: 'bucket',
                value: $bucketSum,
                cumulativeValue: null,
            );
        }

        return $result;
    }
}
