<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_jardino_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('story_id');
            $table->unsignedInteger('target_word_count');
            $table->timestamps();

            $table->foreign('activity_id')->references('id')->on('calendar_activities')->onDelete('cascade');
            $table->unique(['activity_id', 'user_id'], 'unique_user_per_activity');
            $table->index(['activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_jardino_goals');
    }
};
