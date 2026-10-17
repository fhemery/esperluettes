<?php

use App\Domains\Fanart\Private\Support\FanartMode;

it('picks no over designated over yes', function () {
    expect(FanartMode::mostRestrictive(FanartMode::Yes, FanartMode::Designated))->toBe(FanartMode::Designated)
        ->and(FanartMode::mostRestrictive(FanartMode::Designated, FanartMode::No, FanartMode::Yes))->toBe(FanartMode::No)
        ->and(FanartMode::mostRestrictive(FanartMode::Yes, FanartMode::Yes))->toBe(FanartMode::Yes)
        ->and(FanartMode::mostRestrictive())->toBe(FanartMode::No);
});
