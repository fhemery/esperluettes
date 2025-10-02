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
        // profile_profiles.user_id -> users.id (FK + primary key)
        if (Schema::hasTable('profile_profiles') && Schema::hasColumn('profile_profiles', 'user_id')) {
            Schema::table('profile_profiles', function (Blueprint $table) {
                try { $table->dropForeign('profile_profiles_user_id_foreign'); } catch (\Throwable $e) {}
                // Primary key already provides an index; no extra index needed
            });
        }
    }
};
