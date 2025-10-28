<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_jardino_story_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id');
            $table->unsignedBigInteger('story_id');
            $table->string('story_title');
            $table->unsignedInteger('initial_word_count');
            $table->unsignedInteger('current_word_count');
            $table->unsignedInteger('biggest_word_count');
            $table->timestamp('selected_at');
            $table->timestamp('deselected_at')->nullable();
            $table->timestamps();

            $table->foreign('goal_id')->references('id')->on('calendar_jardino_goals')->onDelete('cascade');
            $table->index(['goal_id']);
            $table->index(['story_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_jardino_story_snapshots');
    }
};
