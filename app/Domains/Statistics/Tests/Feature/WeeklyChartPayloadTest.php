<?php

namespace App\Domains\Statistics\Tests\Feature;

use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
    // Wednesday: the current week starts on Monday 2026-01-26.
    Carbon::setTestNow('2026-01-28');
});

afterEach(function () {
    Carbon::setTestNow();
});

function seedPayloadDaily(string $key, string $date, float $value): void
{
    StatisticTimeSeries::create([
        'statistic_key' => $key,
        'scope_type' => 'global',
        'scope_id' => null,
        'granularity' => 'daily',
        'period_start' => $date,
        'value' => $value,
        'cumulative_value' => null,
    ]);
}

/**
 * Decode a single-quoted JSON data attribute from rendered HTML.
 */
function payloadAttribute(string $html, string $attribute): mixed
{
    $found = preg_match("/{$attribute}='([^']*)'/", $html, $matches);
    expect($found)->toBe(1, "Attribute {$attribute} not found");

    return json_decode(html_entity_decode($matches[1]), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<int, array{x: string, value: float}>
 */
function weeklyValues(array $points): array
{
    return array_map(fn (array $point) => [$point['x'], (float) $point['value']], $points);
}

describe('weekly chart payload', function () {
    it('stat-widget ships weekly points next to the cumulative points', function () {
        seedPayloadDaily('global.total_users', '2026-01-13', 2);
        seedPayloadDaily('global.total_users', '2026-01-20', 3);

        $html = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );

        expect(payloadAttribute($html, 'data-points'))->not->toBeEmpty();
        expect(weeklyValues(payloadAttribute($html, 'data-weekly-points')))->toBe([
            ['2026-01-12', 2.0],
            ['2026-01-19', 3.0],
            ['2026-01-26', 0.0],
        ]);
    });

    it('stat-widget weekly points are Monday-based net deltas', function () {
        seedPayloadDaily('global.total_users', '2026-01-18', 2); // Sunday
        seedPayloadDaily('global.total_users', '2026-01-19', 1); // Monday
        seedPayloadDaily('global.total_users', '2026-01-21', -3);

        $html = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );

        expect(weeklyValues(payloadAttribute($html, 'data-weekly-points')))->toBe([
            ['2026-01-12', 2.0],
            ['2026-01-19', -2.0],
            ['2026-01-26', 0.0],
        ]);
    });

    it('line-chart options carry the weekly tooltip and current-week strings', function () {
        seedPayloadDaily('global.total_users', '2026-01-20', 1);

        $html = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );

        $options = payloadAttribute($html, 'data-options');
        expect($options['weekTooltip'])->toBe(__('statistics::admin.week_tooltip'))
            ->and($options['currentWeekLabel'])->toBe(__('statistics::admin.current_week'));
    });

    it('comment-breakdown weekly replies are total minus roots, negatives preserved', function () {
        seedPayloadDaily('global.total_comments', '2026-01-20', -1);
        seedPayloadDaily('global.total_root_comments', '2026-01-20', 1);

        $html = (string) $this->blade(
            '<x-statistics::comment-breakdown-chart root-label="Roots" reply-label="Replies" />'
        );

        [$roots, $replies] = payloadAttribute($html, 'data-series');
        expect(weeklyValues($roots['weeklyPoints']))->toBe([
            ['2026-01-19', 1.0],
            ['2026-01-26', 0.0],
        ]);
        expect(weeklyValues($replies['weeklyPoints']))->toBe([
            ['2026-01-19', -2.0],
            ['2026-01-26', 0.0],
        ]);

        $options = payloadAttribute($html, 'data-options');
        expect($options['weekTooltip'])->toBe(__('statistics::admin.week_tooltip'))
            ->and($options['currentWeekLabel'])->toBe(__('statistics::admin.current_week'));
    });

    it('comment-breakdown aligns weekly roots and replies by week when roots start later', function () {
        seedPayloadDaily('global.total_comments', '2026-01-06', 3);
        seedPayloadDaily('global.total_comments', '2026-01-20', 1);
        seedPayloadDaily('global.total_root_comments', '2026-01-20', 1);

        $html = (string) $this->blade(
            '<x-statistics::comment-breakdown-chart root-label="Roots" reply-label="Replies" />'
        );

        [$roots, $replies] = payloadAttribute($html, 'data-series');
        expect(weeklyValues($roots['weeklyPoints']))->toBe([
            ['2026-01-05', 0.0],
            ['2026-01-12', 0.0],
            ['2026-01-19', 1.0],
            ['2026-01-26', 0.0],
        ]);
        expect(weeklyValues($replies['weeklyPoints']))->toBe([
            ['2026-01-05', 3.0],
            ['2026-01-12', 0.0],
            ['2026-01-19', 0.0],
            ['2026-01-26', 0.0],
        ]);
    });

    it('stat-widget ships recent (12 months) variants next to the full ones', function () {
        seedPayloadDaily('global.total_users', '2024-06-03', 4); // older than 12 months
        seedPayloadDaily('global.total_users', '2026-01-20', 3);

        $html = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );

        $full = payloadAttribute($html, 'data-points');
        $recent = payloadAttribute($html, 'data-recent-points');
        expect($full[0]['x'])->toBe('2024-06-03')
            ->and($recent)->toHaveCount(48)
            ->and($recent[0]['x'])->toBe('2025-01-28')
            ->and((float) $recent[0]['cumulativeValue'])->toBe(4.0)
            ->and((float) $recent[47]['cumulativeValue'])->toBe(7.0);

        $weekly = weeklyValues(payloadAttribute($html, 'data-recent-weekly-points'));
        expect($weekly[0][0])->toBe('2025-01-27')
            ->and(end($weekly))->toBe(['2026-01-26', 0.0])
            ->and(payloadAttribute($html, 'data-weekly-points')[0]['x'])->toBe('2024-06-03');

        $options = payloadAttribute($html, 'data-options');
        expect($options['recentRangeMin'])->toBe('2025-01-28')
            ->and($options['recentRangeMax'])->toBe('2026-01-28')
            ->and($options['rangeMin'])->toBe('2024-06-03');
    });

    it('a metric silent in the last 12 months keeps a flat recent line, not the empty state', function () {
        seedPayloadDaily('global.total_users', '2024-03-04', 6);

        $html = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );

        $recent = payloadAttribute($html, 'data-recent-points');
        expect($html)->not->toContain('stat-line-chart-empty')
            ->and(collect($recent)->pluck('cumulativeValue')->map(fn ($v) => (float) $v)->unique()->all())->toBe([6.0]);
        foreach (payloadAttribute($html, 'data-recent-weekly-points') as $week) {
            expect((float) $week['value'])->toBe(0.0);
        }
    });

    it('comment-breakdown ships recent series with roots aligned to the total by week', function () {
        seedPayloadDaily('global.total_comments', '2024-06-03', 5);
        seedPayloadDaily('global.total_root_comments', '2024-06-03', 2);
        seedPayloadDaily('global.total_comments', '2026-01-20', 1);
        seedPayloadDaily('global.total_root_comments', '2026-01-20', 1);

        $html = (string) $this->blade(
            '<x-statistics::comment-breakdown-chart root-label="Roots" reply-label="Replies" />'
        );

        [$roots, $replies] = payloadAttribute($html, 'data-series');
        expect($roots['points'][0]['x'])->toBe('2024-06-03')
            ->and($roots['recentPoints'])->toHaveCount(48)
            ->and((float) $roots['recentPoints'][0]['cumulativeValue'])->toBe(2.0)
            ->and((float) $replies['recentPoints'][0]['cumulativeValue'])->toBe(3.0)
            ->and((float) $replies['recentPoints'][47]['cumulativeValue'])->toBe(3.0);

        expect(weeklyValues($roots['recentWeeklyPoints'])[0][0])->toBe('2025-01-27')
            ->and(array_slice(weeklyValues($roots['recentWeeklyPoints']), -2))->toBe([
                ['2026-01-19', 1.0],
                ['2026-01-26', 0.0],
            ])
            ->and(array_slice(weeklyValues($replies['recentWeeklyPoints']), -2))->toBe([
                ['2026-01-19', 0.0],
                ['2026-01-26', 0.0],
            ]);
        expect(count($roots['recentWeeklyPoints']))->toBe(count($replies['recentWeeklyPoints']));

        $options = payloadAttribute($html, 'data-options');
        expect($options['recentRangeMin'])->toBe('2025-01-28')
            ->and($options['recentRangeMax'])->toBe('2026-01-28');
    });

    it('renders the empty state when the metric has no data', function () {
        $widget = (string) $this->blade(
            '<x-statistics::stat-widget statistic-key="global.total_users" label="Users" />'
        );
        $breakdown = (string) $this->blade(
            '<x-statistics::comment-breakdown-chart root-label="Roots" reply-label="Replies" />'
        );

        foreach ([$widget, $breakdown] as $html) {
            expect($html)->toContain('stat-line-chart-empty')
                ->and($html)->not->toContain('data-weekly-points')
                ->and($html)->not->toContain('weeklyPoints');
        }
    });

    it('line-chart without a weekly prop still renders with an empty weekly payload', function () {
        $data = [new TimeSeriesPoint(Carbon::parse('2026-01-20'), 'daily', 1, 1)];

        $html = (string) $this->blade('<x-statistics::line-chart :data="$data" />', ['data' => $data]);

        expect(payloadAttribute($html, 'data-points'))->toHaveCount(1);
        expect(payloadAttribute($html, 'data-weekly-points'))->toBe([]);
    });

    it('admin page ships a weekly payload on every line chart', function () {
        seedPayloadDaily('global.total_users', '2026-01-20', 1);

        $html = $this->actingAs(admin($this))
            ->get(route('statistics.admin.index'))
            ->assertOk()
            ->getContent();

        $charts = substr_count($html, 'data-statistics-line-chart');
        expect($charts)->toBeGreaterThan(0)
            ->and(substr_count($html, 'data-weekly-points='))->toBe($charts);
    });
});
