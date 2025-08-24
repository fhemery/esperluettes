<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('story_genres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('story_ref_genre_id');
            $table->timestamps();

            $table->unique(['story_id', 'story_ref_genre_id']);

            $table->foreign('story_id')
                ->references('id')->on('stories')
                ->onDelete('cascade');

            $table->foreign('story_ref_genre_id')
                ->references('id')->on('story_ref_genres')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_genres');
    }
};
