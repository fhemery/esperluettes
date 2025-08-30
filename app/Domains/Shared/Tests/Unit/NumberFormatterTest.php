<?php

use App\Domains\Shared\Support\NumberFormatter;

it('formats numbers below 1000 as integers', function () {
    expect(NumberFormatter::compact(0, 'en'))->toBe('0');
    expect(NumberFormatter::compact(42, 'en'))->toBe('42');
    expect(NumberFormatter::compact(999, 'en'))->toBe('999');
});

it('formats thousands with k and floors decimals (en)', function () {
    expect(NumberFormatter::compact(1000, 'en'))->toBe('1k');
    expect(NumberFormatter::compact(1001, 'en'))->toBe('1k');
    expect(NumberFormatter::compact(1099, 'en'))->toBe('1k'); // 1.09k floored to 1.0k -> 1k
    expect(NumberFormatter::compact(1151, 'en'))->toBe('1.1k');
    expect(NumberFormatter::compact(1999, 'en'))->toBe('1.9k');
});

it('formats thousands with k and floors decimals (fr)', function () {
    expect(NumberFormatter::compact(1151, 'fr'))->toBe('1,1k');
    expect(NumberFormatter::compact(1999, 'fr'))->toBe('1,9k');
});

it('formats millions with M and floors decimals', function () {
    expect(NumberFormatter::compact(1_000_000, 'en'))->toBe('1M');
    expect(NumberFormatter::compact(1_000_001, 'en'))->toBe('1M');
    expect(NumberFormatter::compact(1_234_567, 'en'))->toBe('1.2M');
});

it('formats billions with B and floors decimals', function () {
    expect(NumberFormatter::compact(1_000_000_000, 'en'))->toBe('1B');
    expect(NumberFormatter::compact(1_500_000_000, 'en'))->toBe('1.5B');
});

it('supports negative numbers and keeps the sign', function () {
    expect(NumberFormatter::compact(-1, 'en'))->toBe('-1');
    expect(NumberFormatter::compact(-1000, 'en'))->toBe('-1k');
    expect(NumberFormatter::compact(-1151, 'en'))->toBe('-1.1k');
    expect(NumberFormatter::compact(-1_234_567, 'en'))->toBe('-1.2M');
});
