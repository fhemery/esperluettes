<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('story_collaborators', function (Blueprint $table) {
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->index(); // 'author' for now
            $table->foreignId('invited_by_user_id')->constrained('users');
            $table->timestamp('invited_at');
            $table->timestamp('accepted_at')->nullable();

            $table->unique(['story_id', 'user_id']);
            // Helpful composite indexes for common lookups
            $table->index(['story_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_collaborators');
    }
};
