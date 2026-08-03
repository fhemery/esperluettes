<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_quote_contest_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('calendar_activities')->cascadeOnDelete();
            $table->string('title', 160);
            // Plain text, escaped on render.
            $table->text('description')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['activity_id', 'position'], 'qc_categories_activity_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_quote_contest_categories');
    }
};
