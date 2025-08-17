<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('visibility', 20)->index(); // public, community, private
            $table->unsignedBigInteger('story_ref_type_id')->nullable();
            $table->unsignedBigInteger('story_ref_audience_id')->nullable();
            $table->unsignedBigInteger('story_ref_copyright_id')->nullable();
            $table->unsignedBigInteger('story_ref_status_id')->nullable();
            $table->unsignedBigInteger('story_ref_feedback_id')->nullable();
            $table->timestamp('last_chapter_published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
