<?php

namespace App\Domains\Statistics\Private\Support;

use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;
use DateTimeInterface;

class WeeklyAggregator
{
    /**
     * Group sparse daily deltas into calendar weeks (Monday → Sunday), from the
     * week of the earliest point to the week containing $now, with no gaps.
     *
     * @param  TimeSeriesPoint[]  $dailyPoints  sparse daily deltas, any order
     * @return TimeSeriesPoint[]  one point per week, oldest first
     */
    public function aggregate(array $dailyPoints, DateTimeInterface $now): array
    {
        if ($dailyPoints === []) {
            return [];
        }

        $sums = [];
        $firstWeek = null;
        foreach ($dailyPoints as $point) {
            $monday = Carbon::parse($point->periodStart)->startOfWeek(Carbon::MONDAY);
            $key = $monday->format('Y-m-d');
            $sums[$key] = ($sums[$key] ?? 0) + $point->value;

            if ($firstWeek === null || $monday->lt($firstWeek)) {
                $firstWeek = $monday;
            }
        }

        $lastWeek = Carbon::parse($now)->startOfWeek(Carbon::MONDAY);

        $weeks = [];
        for ($week = $firstWeek->copy(); $week->lte($lastWeek); $week->addWeek()) {
            $weeks[] = new TimeSeriesPoint(
                periodStart: $week->copy(),
                granularity: 'weekly',
                value: $sums[$week->format('Y-m-d')] ?? 0,
                cumulativeValue: null,
            );
        }

        return $weeks;
    }
}
