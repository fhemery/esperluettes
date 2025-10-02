<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        // announcements.created_by -> users.id
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'created_by')) {
            Schema::table('announcements', function (Blueprint $table) {
                try { $table->dropForeign('announcements_created_by_foreign'); } catch (\Throwable $e) {}
                try { $table->index('created_by', 'idx_ann_created_by'); } catch (\Throwable $e) {}
            });
        }

        // news.created_by -> users.id
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'created_by')) {
            Schema::table('news', function (Blueprint $table) {
                try { $table->dropForeign('news_created_by_foreign'); } catch (\Throwable $e) {}
                try { $table->index('created_by', 'idx_news_created_by'); } catch (\Throwable $e) {}
            });
        }
    }
};
