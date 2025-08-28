<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Denormalized `story_id` for performance and simpler queries.
            // Although `chapter_id` implies the story via chapters.story_id, we keep story_id here to:
            // - support fast lookups like "progress by story" and "stories a user is reading" without a join
            // - make index(['user_id','story_id']) effective for dashboards/filters
            // - allow straightforward cascade-by-story semantics
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'chapter_id']);
            $table->index(['user_id', 'story_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
