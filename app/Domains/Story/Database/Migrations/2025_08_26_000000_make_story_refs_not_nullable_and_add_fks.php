<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Ensure columns are NOT NULL
            DB::statement('ALTER TABLE `stories` MODIFY `story_ref_type_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `stories` MODIFY `story_ref_audience_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `stories` MODIFY `story_ref_copyright_id` BIGINT UNSIGNED NOT NULL');

            // Add FKs if they do not already exist
            // Names are explicit to avoid duplicates on reruns
            DB::statement('ALTER TABLE `stories`
                ADD CONSTRAINT `fk_stories_ref_type`
                FOREIGN KEY (`story_ref_type_id`) REFERENCES `story_ref_types`(`id`) ON DELETE RESTRICT');
            DB::statement('ALTER TABLE `stories`
                ADD CONSTRAINT `fk_stories_ref_audience`
                FOREIGN KEY (`story_ref_audience_id`) REFERENCES `story_ref_audiences`(`id`) ON DELETE RESTRICT');
            DB::statement('ALTER TABLE `stories`
                ADD CONSTRAINT `fk_stories_ref_copyright`
                FOREIGN KEY (`story_ref_copyright_id`) REFERENCES `story_ref_copyrights`(`id`) ON DELETE RESTRICT');
        } else {
            // For SQLite and others, skip NOT NULL alteration to avoid requiring doctrine/dbal.
            // The application-level validation already enforces presence; production DB is MySQL.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // Drop FKs; keep columns as-is (may remain NOT NULL)
            Schema::table('stories', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                try { DB::statement('ALTER TABLE `stories` DROP FOREIGN KEY `fk_stories_ref_type`'); } catch (\Throwable $e) {}
                try { DB::statement('ALTER TABLE `stories` DROP FOREIGN KEY `fk_stories_ref_audience`'); } catch (\Throwable $e) {}
                try { DB::statement('ALTER TABLE `stories` DROP FOREIGN KEY `fk_stories_ref_copyright`'); } catch (\Throwable $e) {}
            });
        }
    }
};
