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
        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key')->index(); // e.g., 'profile', 'story', 'chapter', 'comment'
            $table->unsignedBigInteger('entity_id'); // ID of the reported entity
            $table->unsignedBigInteger('reported_user_id')->nullable(); // content owner (nullable until formatters implemented)
            $table->unsignedBigInteger('reported_by_user_id'); // reporter
            $table->foreignId('reason_id')->constrained('moderation_reasons');
            $table->text('description')->nullable(); // additional details from reporter
            $table->json('content_snapshot')->nullable(); // Domain-specific snapshot as JSON
            $table->string('content_url'); // URL to the reported content
            $table->enum('status', ['pending', 'confirmed', 'dismissed'])->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable(); // moderator
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable(); // moderator's internal notes
            $table->timestamps();

            // Composite indexes for efficient queries
            $table->index(['topic_key', 'entity_id']);
            $table->index('reported_user_id');
            $table->index('reported_by_user_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');
    }
};
