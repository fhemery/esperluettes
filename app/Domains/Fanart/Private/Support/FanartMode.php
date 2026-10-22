<?php

namespace App\Domains\Fanart\Private\Support;

enum FanartMode: string
{
    case Yes = 'yes';
    case Designated = 'designated';
    case No = 'no';

    private function restriction(): int
    {
        return match ($this) {
            self::Yes => 0,
            self::Designated => 1,
            self::No => 2,
        };
    }

    /** The most restrictive of the given modes (`no` > `designated` > `yes`); `no` when none given. */
    public static function mostRestrictive(self ...$modes): self
    {
        if ($modes === []) {
            return self::No;
        }

        return array_reduce(
            $modes,
            fn (?self $carry, self $mode) => $carry === null || $mode->restriction() > $carry->restriction() ? $mode : $carry,
        );
    }
}
