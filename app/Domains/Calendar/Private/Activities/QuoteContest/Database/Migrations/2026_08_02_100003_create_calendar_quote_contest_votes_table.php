<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_quote_contest_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('calendar_quote_contest_categories')->cascadeOnDelete();
            // The tally groups on entry_id; the foreign key already indexes it.
            $table->foreignId('entry_id')->constrained('calendar_quote_contest_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // One ballot per reader per category. Changing a vote updates the
            // row rather than adding one, so this rule *is* index-expressible.
            $table->unique(['category_id', 'user_id'], 'qc_votes_category_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_quote_contest_votes');
    }
};
