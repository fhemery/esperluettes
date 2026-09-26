<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('config_feature_toggles', function (Blueprint $table) {
            $table->dropIndex(['admin_visibility']);
            $table->dropColumn('admin_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('config_feature_toggles', function (Blueprint $table) {
            $table->string('admin_visibility')->default('tech_admins_only');
            $table->index('admin_visibility');
        });
    }
};
