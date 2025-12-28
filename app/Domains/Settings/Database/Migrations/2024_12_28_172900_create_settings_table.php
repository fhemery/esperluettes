<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // No FK, Auth domain is separate
            $table->string('domain', 50);
            $table->string('key', 100);
            $table->text('value');
            $table->timestamps();

            $table->unique(['user_id', 'domain', 'key']);
            $table->index('user_id');  // Fast lookup for caching all user settings
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
