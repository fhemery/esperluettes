<?php

use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

function insertCommandToggleRow(string $name, string $domain, FeatureToggleAccess $access, array $roles = []): void
{
    DB::table('config_feature_toggles')->insert([
        'name'       => $name,
        'domain'     => $domain,
        'access'     => $access->value,
        'roles'      => json_encode($roles),
        'created_at' => '2026-01-02 03:04:05',
        'updated_at' => '2026-01-02 03:04:05',
    ]);
}

/**
 * Seeds: a declared toggle with a row, a declared toggle without a row, an orphan row.
 */
function seedCommandToggles(): void
{
    declareFeatureToggle('cmd-with-row', 'config');
    insertCommandToggleRow('cmd-with-row', 'config', FeatureToggleAccess::ROLE_BASED, ['admin', 'moderator']);
    declareFeatureToggle('cmd-no-row', 'alpha');
    insertCommandToggleRow('cmd-orphan', 'legacy', FeatureToggleAccess::ON);
}

describe('config:toggles command', function () {
    it('prints declared toggles with and without a row and orphan rows as JSON', function () {
        seedCommandToggles();

        $exitCode = Artisan::call('config:toggles', ['--json' => true]);

        expect($exitCode)->toBe(0);
        $report = json_decode(Artisan::output(), true);
        expect($report)->toBe([
            [
                'domain' => 'alpha',
                'name' => 'cmd-no-row',
                'declared' => true,
                'access' => 'off',
                'roles' => [],
                'updated_at' => null,
            ],
            [
                'domain' => 'config',
                'name' => 'cmd-with-row',
                'declared' => true,
                'access' => 'role_based',
                'roles' => ['admin', 'moderator'],
                'updated_at' => '2026-01-02T03:04:05+00:00',
            ],
            [
                'domain' => 'legacy',
                'name' => 'cmd-orphan',
                'declared' => false,
                'access' => 'on',
                'roles' => [],
                'updated_at' => '2026-01-02T03:04:05+00:00',
            ],
        ]);
    });

    it('prints a table without --json', function () {
        seedCommandToggles();

        $exitCode = Artisan::call('config:toggles');

        expect($exitCode)->toBe(0);
        $output = Artisan::output();
        expect($output)->toContain('cmd-with-row')
            ->toContain('cmd-no-row')
            ->toContain('cmd-orphan')
            ->toContain('admin, moderator')
            ->toContain('yes')
            ->toContain('no');
    });

    it('changes nothing', function () {
        seedCommandToggles();
        $before = DB::table('config_feature_toggles')->orderBy('id')->get()->toArray();

        Artisan::call('config:toggles', ['--json' => true]);
        Artisan::call('config:toggles');

        expect(DB::table('config_feature_toggles')->orderBy('id')->get()->toArray())->toEqual($before);
    });

    it('needs no authenticated user', function () {
        seedCommandToggles();
        expect(auth()->check())->toBeFalse();

        $this->artisan('config:toggles')->assertExitCode(0);
    });
});
