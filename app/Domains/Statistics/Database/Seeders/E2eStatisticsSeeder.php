<?php

namespace App\Domains\Statistics\Database\Seeders;

use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Global statistics for the admin statistics page in the E2E environment
 * (see .env.e2e). Mirrored in `e2e/support/fixtures.ts` (`STATISTICS`).
 *
 * The other E2E seeders write rows directly and fire no domain event, so no
 * statistic is ever computed from them: the time series are written here, as
 * net daily deltas, relative to the current week so the fixture never ages.
 *
 * Six weeks per metric, oldest first, the last one being the current week.
 * Week index 2 has no row at all (a zero week the aggregator must fill), and
 * `global.total_words` and `global.total_stories` each have one negative week.
 */
class E2eStatisticsSeeder extends Seeder
{
    /** Weekly net deltas, oldest week first; the last entry is the current week. */
    public const WEEKLY = [
        'global.total_users' => [3, 1, 0, 5, 2, 1],
        'global.total_stories' => [2, 1, 0, -1, 1, 1],
        'global.total_chapters' => [3, 2, 0, 1, 4, 1],
        'global.total_words' => [1200, 800, 0, -300, 500, 150],
        'global.total_comments' => [4, 3, 0, 5, 2, 1],
        'global.total_root_comments' => [2, 1, 0, 3, 1, 1],
    ];

    public function run(): void
    {
        $now = CarbonImmutable::now();
        $currentMonday = $now->startOfWeek(CarbonImmutable::MONDAY);
        $weeks = count(self::WEEKLY['global.total_users']);

        foreach (self::WEEKLY as $key => $deltas) {
            $cumulative = 0;

            foreach ($deltas as $index => $delta) {
                if ($delta === 0) {
                    continue;
                }

                $monday = $currentMonday->subWeeks($weeks - 1 - $index);
                // A past week's delta lands on its Wednesday; the current week's on today.
                $day = $index === $weeks - 1 ? $now->startOfDay() : $monday->addDays(2);
                $cumulative += $delta;

                // Through the model, so `period_start` is stored exactly as the
                // live listeners store it and later events update the same row.
                StatisticTimeSeries::create([
                    'statistic_key' => $key,
                    'scope_type' => 'global',
                    'scope_id' => null,
                    'granularity' => 'daily',
                    'period_start' => $day,
                    'value' => $delta,
                    'cumulative_value' => $cumulative,
                ]);
            }

            StatisticSnapshot::create([
                'statistic_key' => $key,
                'scope_type' => 'global',
                'scope_id' => null,
                'value' => $cumulative,
                'computed_at' => $now,
            ]);
        }
    }
}
