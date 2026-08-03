<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A contest entry is a full snapshot of the quote it was submitted from: it
 * survives the source quote's edition or deletion, and no read path ever
 * dereferences `quote_id`.
 *
 * Deliberately *no* unique index on (category_id, user_id): MySQL treats each
 * NULL `withdrawn_at` as distinct, so an index could not express "one
 * non-withdrawn entry per user". The service enforces that rule instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_quote_contest_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('calendar_activities')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('calendar_quote_contest_categories')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            // Provenance only — never dereferenced for display.
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('story_id');
            $table->text('highlighted_text');
            $table->string('story_title');
            $table->string('story_slug');
            $table->unsignedBigInteger('chapter_id');
            $table->string('chapter_title');
            $table->string('chapter_slug');
            $table->json('author_user_ids');
            $table->dateTime('withdrawn_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'withdrawn_at'], 'qc_entries_category_withdrawn_idx');
            $table->index(['activity_id', 'user_id'], 'qc_entries_activity_user_idx');
            $table->index(['story_id', 'withdrawn_at'], 'qc_entries_story_withdrawn_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_quote_contest_entries');
    }
};
