<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // SQLite does not support dropping foreign keys by name; skip on SQLite.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        // stories.created_by_user_id -> users.id
        if (Schema::hasTable('stories') && Schema::hasColumn('stories', 'created_by_user_id')) {
            Schema::table('stories', function (Blueprint $table) {
                // Default FK name: stories_created_by_user_id_foreign
                try { $table->dropForeign('stories_created_by_user_id_foreign'); } catch (\Throwable $e) {}
                // Ensure index for lookups
                try { $table->index('created_by_user_id', 'idx_stories_created_by_user_id'); } catch (\Throwable $e) {}
            });
        }

        // story_collaborators.user_id -> users.id
        // story_collaborators.invited_by_user_id -> users.id
        if (Schema::hasTable('story_collaborators')) {
            Schema::table('story_collaborators', function (Blueprint $table) {
                try { $table->dropForeign('story_collaborators_user_id_foreign'); } catch (\Throwable $e) {}
                try { $table->dropForeign('story_collaborators_invited_by_user_id_foreign'); } catch (\Throwable $e) {}
                // Indices: user_id already covered by composite indexes, but add standalone if useful
                try { $table->index('user_id', 'idx_story_collab_user_id'); } catch (\Throwable $e) {}
                try { $table->index('invited_by_user_id', 'idx_story_collab_invited_by_user_id'); } catch (\Throwable $e) {}
            });
        }

        // story_reading_progress.user_id -> users.id
        if (Schema::hasTable('story_reading_progress') && Schema::hasColumn('story_reading_progress', 'user_id')) {
            Schema::table('story_reading_progress', function (Blueprint $table) {
                try { $table->dropForeign('story_reading_progress_user_id_foreign'); } catch (\Throwable $e) {}
                // Index already exists via [user_id, story_id], add single-col if needed
                try { $table->index('user_id', 'idx_story_reading_progress_user_id'); } catch (\Throwable $e) {}
            });
        }
    }
};
