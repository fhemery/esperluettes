<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function runSeedAnnotationOtherReasonMigration(): void
{
    $migration = require base_path('app/Domains/Moderation/Database/Migrations/2026_11_01_000001_seed_annotation_other_reason.php');
    $migration->up();
}

describe('Seed annotation Autre reason migration', function () {
    beforeEach(function () {
        DB::table('moderation_reasons')->where('topic_key', 'annotation')->delete();
    });

    it('seeds Autre when the annotation topic has no reason', function () {
        runSeedAnnotationOtherReasonMigration();

        $rows = DB::table('moderation_reasons')->where('topic_key', 'annotation')->get();
        expect($rows)->toHaveCount(1);
        expect($rows[0]->label)->toBe('Autre');
        expect((bool) $rows[0]->is_active)->toBeTrue();
        expect((int) $rows[0]->sort_order)->toBe(0);
    });

    it('leaves existing reasons untouched', function () {
        DB::table('moderation_reasons')->insert([
            'topic_key' => 'annotation',
            'label' => 'Spam',
            'sort_order' => 3,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        runSeedAnnotationOtherReasonMigration();

        $rows = DB::table('moderation_reasons')->where('topic_key', 'annotation')->get();
        expect($rows)->toHaveCount(1);
        expect($rows[0]->label)->toBe('Spam');
        expect((bool) $rows[0]->is_active)->toBeFalse();
    });

    it('is idempotent', function () {
        runSeedAnnotationOtherReasonMigration();
        runSeedAnnotationOtherReasonMigration();

        expect(DB::table('moderation_reasons')->where('topic_key', 'annotation')->count())->toBe(1);
    });
});
