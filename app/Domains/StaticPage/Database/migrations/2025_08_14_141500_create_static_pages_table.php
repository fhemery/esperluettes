<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('header_image_path')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by');
            $table->index('created_by', 'idx_static_pages_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};
