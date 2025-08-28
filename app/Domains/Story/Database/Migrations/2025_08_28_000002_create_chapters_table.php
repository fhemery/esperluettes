<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique(); // stored with -id suffix
            $table->text('author_note')->nullable();
            $table->longText('content');
            $table->integer('sort_order')->index();
            $table->enum('status', ['not_published', 'published'])->index();
            $table->timestamp('first_published_at')->nullable()->index();
            $table->unsignedInteger('reads_guest_count')->default(0);
            $table->unsignedInteger('reads_logged_count')->default(0);
            $table->timestamps();

            $table->index(['story_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
