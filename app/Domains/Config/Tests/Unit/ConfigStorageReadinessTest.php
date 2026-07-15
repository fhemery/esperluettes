<?php

use App\Domains\Config\Private\Support\ConfigStorageReadiness;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

describe('ConfigStorageReadiness', function () {
    it('returns false when the database is unreachable', function () {
        Schema::shouldReceive('hasTable')
            ->with('config_feature_toggles')
            ->andThrow(new PDOException('connection refused'));

        expect(ConfigStorageReadiness::isAvailable())->toBeFalse();
    });

    it('returns false when config tables have not been migrated', function () {
        Schema::shouldReceive('hasTable')
            ->with('config_feature_toggles')
            ->andReturn(false);

        expect(ConfigStorageReadiness::isAvailable())->toBeFalse();
    });

    it('returns true when config tables exist', function () {
        Schema::shouldReceive('hasTable')
            ->with('config_feature_toggles')
            ->andReturn(true);

        expect(ConfigStorageReadiness::isAvailable())->toBeTrue();
    });
});
