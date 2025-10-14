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
        Schema::create('moderation_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key')->index(); // e.g., 'profile', 'story', 'chapter', 'comment'
            $table->string('label'); // translatable key or plain text
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true); // when false, hidden from users but preserved for historical reports
            $table->timestamps();

            // Composite indexes for efficient queries
            $table->index(['topic_key', 'is_active']);
            $table->index(['topic_key', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_reasons');
    }
};
