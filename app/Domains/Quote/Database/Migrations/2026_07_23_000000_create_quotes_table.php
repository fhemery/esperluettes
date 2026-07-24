<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chapter_id');
            $table->unsignedBigInteger('story_id');
            $table->text('highlighted_text');
            $table->string('prefix', 255)->nullable();
            $table->string('suffix', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at']);
            $table->index(['chapter_id', 'user_id', 'deleted_at']);
            $table->index(['story_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
