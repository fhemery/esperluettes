<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_quote_contest_settings', function (Blueprint $table) {
            $table->id();
            // One settings row per contest. The unique index is also the only
            // lookup key: the scheduler scans the whole (tiny) table.
            $table->foreignId('activity_id')->unique()->constrained('calendar_activities')->cascadeOnDelete();
            $table->dateTime('submissions_end_at');
            $table->dateTime('votes_start_at');
            // Idempotence markers for the broadcast notifications.
            $table->dateTime('notified_submissions_open_at')->nullable();
            $table->dateTime('notified_submissions_closing_at')->nullable();
            $table->dateTime('notified_votes_open_at')->nullable();
            $table->dateTime('notified_votes_closing_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_quote_contest_settings');
    }
};
