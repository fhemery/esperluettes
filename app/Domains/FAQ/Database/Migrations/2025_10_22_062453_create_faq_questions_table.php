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
        Schema::create('faq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->constrained('faq_categories')->cascadeOnDelete();
            $table->string('question');
            $table->string('slug')->unique();
            $table->text('answer');
            $table->string('image_path')->nullable();
            $table->string('image_alt_text')->nullable();
            $table->integer('sort_order')->default(999);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('updated_by_user_id');
            $table->timestamps();

            $table->index('slug');
            $table->index(['faq_category_id', 'sort_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_questions');
    }
};
