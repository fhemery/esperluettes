<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
