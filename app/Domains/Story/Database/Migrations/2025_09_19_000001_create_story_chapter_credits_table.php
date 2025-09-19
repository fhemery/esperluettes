<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('story_chapter_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->integer('credits_gained')->default(0);
            $table->integer('credits_spent')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_chapter_credits');
    }
};
