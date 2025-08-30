<?php

namespace App\Domains\Shared\Support;

class NumberFormatter
{
    /**
     * Compact number formatter with floor rounding and locale-aware decimal separator.
     * Examples:
     *  - 1001 => 1k
     *  - 1151 => 1,1k (fr) or 1.1k (en)
     *  - 1_000_001 => 1M
     */
    public static function compact(int|float $number, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $dec = self::decimalSeparator($locale);

        $abs = (int) abs($number);
        $sign = $number < 0 ? '-' : '';
        $suffix = '';

        if ($abs < 1000) {
            return $sign . (string) $abs;
        }

        // Determine scale
        $divisor = 1;
        if ($abs < 1_000_000) {
            $divisor = 1_000;
            $suffix = 'k';
        } elseif ($abs < 1_000_000_000) {
            $divisor = 1_000_000;
            $suffix = 'M';
        } else {
            $divisor = 1_000_000_000;
            $suffix = 'B';
        }

        // Compute value scaled by 10 using integer arithmetic, with floor rounding
        $times10 = intdiv($abs * 10, $divisor);

        $intPartNum = intdiv($times10, 10);
        $decPartNum = $times10 % 10;

        if ($decPartNum === 0) {
            return $sign . ((string) $intPartNum) . $suffix;
        }

        return $sign . $intPartNum . $dec . $decPartNum . $suffix;
    }

    protected static function decimalSeparator(string $locale): string
    {
        return $locale === 'fr' ? ',' : '.';
    }
}
