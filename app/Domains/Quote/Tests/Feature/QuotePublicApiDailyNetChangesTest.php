<?php

use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function datedQuote(int $userId, string $createdAt, ?string $deletedAt = null): void
{
    $quote = createQuote($userId, 10, 20);
    $quote->forceFill(['created_at' => $createdAt, 'deleted_at' => $deletedAt])->save();
}

function netChanges(): array
{
    return app(QuotePublicApi::class)->getDailyNetChanges();
}

it('returns an empty array when there is no quote', function () {
    expect(netChanges())->toBe([]);
});

it('counts one per created quote on its creation day', function () {
    datedQuote(1, '2026-03-01 10:00:00');
    datedQuote(2, '2026-03-01 23:00:00');
    datedQuote(1, '2026-03-05 08:00:00');

    expect(netChanges())->toBe(['2026-03-01' => 2, '2026-03-05' => 1]);
});

it('subtracts soft-deleted quotes on their deletion day', function () {
    datedQuote(1, '2026-03-01 10:00:00', '2026-03-09 10:00:00');

    expect(netChanges())->toBe(['2026-03-01' => 1, '2026-03-09' => -1]);
});

it('drops a day whose creations and deletions cancel out', function () {
    datedQuote(1, '2026-03-01 10:00:00', '2026-03-01 18:00:00');

    expect(netChanges())->toBe([]);
});

it('returns dates ascending in Y-m-d format', function () {
    datedQuote(1, '2026-03-10 10:00:00', '2026-03-12 10:00:00');
    datedQuote(2, '2026-03-02 10:00:00');

    $result = netChanges();

    expect(array_keys($result))->toBe(['2026-03-02', '2026-03-10', '2026-03-12']);
    expect(array_sum($result))->toBe(Quote::count());
});
