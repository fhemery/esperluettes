<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('config_feature_toggles', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('name');
            $table->string('access'); // 'on' | 'off' | 'role_based'
            $table->string('admin_visibility'); // 'tech_admins_only' | 'all_admins'
            $table->json('roles')->nullable(); // array of role slugs
            $table->unsignedBigInteger('updated_by')->nullable(); // no FK per domain boundary rule
            $table->timestamps();

            $table->unique(['domain', 'name']);
            $table->index('domain');
            $table->index('access');
            $table->index('admin_visibility');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_feature_toggles');
    }
};
