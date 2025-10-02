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
        // static_pages.created_by -> users.id
        if (Schema::hasTable('static_pages') && Schema::hasColumn('static_pages', 'created_by')) {
            Schema::table('static_pages', function (Blueprint $table) {
                try { $table->dropForeign('static_pages_created_by_foreign'); } catch (\Throwable $e) {}
                try { $table->index('created_by', 'idx_static_pages_created_by'); } catch (\Throwable $e) {}
            });
        }
    }
};
