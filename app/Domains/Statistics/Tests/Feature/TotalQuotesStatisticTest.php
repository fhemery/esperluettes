<?php

namespace App\Domains\Statistics\Tests\Feature;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
});

function quotesSetup($test): array
{
    $author = alice($test);
    $reader = bob($test);
    $story = publicStory('Story', $author->id);
    $chapter = createPublishedChapter($test, $story, $author);

    return [$reader, $story, $chapter];
}

function saveQuote($test, $reader, $story, $chapter): int
{
    return $test->actingAs($reader)->postJson('/quotes', [
        'chapter_id' => $chapter->id,
        'story_id' => $story->id,
        'highlighted_text' => 'A passage',
    ])->assertStatus(201)->json('id');
}

function seriesByDay(): array
{
    return StatisticTimeSeries::query()
        ->where('statistic_key', 'global.total_quotes')
        ->orderBy('period_start')
        ->get()
        ->mapWithKeys(fn ($r) => [$r->period_start->format('Y-m-d') => [(float) $r->value, (float) $r->cumulative_value]])
        ->all();
}

describe('TotalQuotesStatistic - event-driven updates', function () {
    it('increments total_quotes when a reader saves a quote', function () {
        [$reader, $story, $chapter] = quotesSetup($this);

        saveQuote($this, $reader, $story, $chapter);

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
        expect(getTimeSeriesValue('global.total_quotes', now()->format('Y-m-d')))->toBe(1.0);
    });

    it('decrements total_quotes when the owner deletes a quote', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);
        $id = saveQuote($this, $reader, $story, $chapter);

        expect(getStatisticValue('global.total_quotes'))->toBe(2.0);

        $this->actingAs($reader)->deleteJson("/quotes/{$id}")->assertSuccessful();

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
    });

    it('subtracts a user\'s visible quotes on deactivation and adds them back on reactivation', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        foreach (range(1, 3) as $_) {
            saveQuote($this, $reader, $story, $chapter);
        }

        event(new UserDeactivated($reader->id));
        expect(getStatisticValue('global.total_quotes'))->toBe(0.0);

        event(new UserReactivated($reader->id));
        expect(getStatisticValue('global.total_quotes'))->toBe(3.0);
    });

    it('subtracts a deleted user\'s visible quotes', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);
        saveQuote($this, $reader, $story, $chapter);

        event(new UserDeleted($reader->id));

        expect(getStatisticValue('global.total_quotes'))->toBe(0.0);
    });

    it('does not subtract twice when a deactivated user is deleted', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);
        saveQuote($this, $reader, $story, $chapter);

        event(new UserDeactivated($reader->id));
        expect(getStatisticValue('global.total_quotes'))->toBe(0.0);

        event(new UserDeleted($reader->id));
        expect(getStatisticValue('global.total_quotes'))->toBe(0.0);
    });

    it('keeps quotes counted when their chapter is deleted', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);

        $chapter->delete();

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
    });

    it('records a negative daily delta', function () {
        [$reader, $story, $chapter] = quotesSetup($this);

        $this->travelTo(now()->subDay());
        $id = saveQuote($this, $reader, $story, $chapter);
        $this->travelBack();

        $this->actingAs($reader)->deleteJson("/quotes/{$id}")->assertSuccessful();

        expect(getTimeSeriesValue('global.total_quotes', now()->format('Y-m-d')))->toBe(-1.0);
    });
});

describe('TotalQuotesStatistic - recompute from the quotes table', function () {
    it('rebuilds the same snapshot and daily series as live events', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        $other = carol($this);

        $this->travelTo(now()->subDays(5));
        saveQuote($this, $reader, $story, $chapter);
        saveQuote($this, $other, $story, $chapter);
        $this->travelTo(now()->addDay());
        saveQuote($this, $reader, $story, $chapter);
        $this->travelTo(now()->addDay());
        saveQuote($this, $other, $story, $chapter);
        $this->travelTo(now()->addDay());
        event(new UserDeactivated($other->id));
        $this->travelBack();

        $snapshot = getStatisticValue('global.total_quotes');
        $series = seriesByDay();
        expect($snapshot)->toBe(2.0);

        resetStatistics();
        recomputeStatistic('global.total_quotes');

        expect(getStatisticValue('global.total_quotes'))->toBe($snapshot);
        expect(seriesByDay())->toBe($series);
    });

    it('keeps the snapshot right when a reader-deleted quote vanishes from history', function () {
        [$reader, $story, $chapter] = quotesSetup($this);

        $this->travelTo(now()->subDays(3));
        saveQuote($this, $reader, $story, $chapter);
        $id = saveQuote($this, $reader, $story, $chapter);
        $this->travelTo(now()->addDay());
        $this->actingAs($reader)->deleteJson("/quotes/{$id}")->assertSuccessful();
        $deletionDay = now()->format('Y-m-d');
        $this->travelBack();

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);

        resetStatistics();
        recomputeStatistic('global.total_quotes');

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
        expect(seriesByDay())->not->toHaveKey($deletionDay);
    });

    it('does not count a reader-deleted quote again after reactivation', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);
        $id = saveQuote($this, $reader, $story, $chapter);
        $this->actingAs($reader)->deleteJson("/quotes/{$id}")->assertSuccessful();

        event(new UserDeactivated($reader->id));
        event(new UserReactivated($reader->id));

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);

        resetStatistics();
        recomputeStatistic('global.total_quotes');

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
    });

    it('runs through statistics:compute', function () {
        [$reader, $story, $chapter] = quotesSetup($this);
        saveQuote($this, $reader, $story, $chapter);

        $this->artisan('statistics:compute', ['key' => 'global.total_quotes'])->assertExitCode(0);

        expect(getStatisticValue('global.total_quotes'))->toBe(1.0);
    });

    it('leaves no snapshot when there is no quote', function () {
        recomputeStatistic('global.total_quotes');

        expect(StatisticSnapshot::query()->where('statistic_key', 'global.total_quotes')->count())->toBe(0);
    });
});
