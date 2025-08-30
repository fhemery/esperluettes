<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profile_profiles')) {
            // If profiles table doesn't exist yet, nothing to do in this migration.
            return;
        }

        Schema::table('profile_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profile_profiles', 'display_name')) {
                $table->string('display_name')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('profile_profiles')) {
            return;
        }

        Schema::table('profile_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profile_profiles', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
