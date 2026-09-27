<?php

use App\Domains\Config\Public\Api\ConfigPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - registration at boot', function () {
    it('declares shared/dark_theme at boot', function () {
        // No clearing here: the declaration comes from SharedServiceProvider::boot().
        expect(app(ConfigPublicApi::class)->isToggleEnabled('dark_theme', 'shared'))->toBeFalse();
    });
});
