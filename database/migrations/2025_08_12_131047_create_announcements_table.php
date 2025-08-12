<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->longText('content');
            $table->string('header_image_path')->nullable();
            $table->boolean('is_pinned')->default(false)->index();
            $table->unsignedInteger('display_order')->nullable()->index();
            $table->string('status'); // draft | published
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Additional helpful indexes
            $table->index(['status', 'published_at']);
            $table->index(['is_pinned', 'display_order', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
