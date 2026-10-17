<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fanarts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('kind', 16);
            $table->string('status', 16);
            $table->boolean('is_artwork')->default(false);
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->boolean('share_on_discord')->default(false);
            $table->timestamp('discord_sent_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['story_id', 'status', 'kind']);
            $table->index(['chapter_id', 'kind', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['share_on_discord', 'status', 'discord_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fanarts');
    }
};
