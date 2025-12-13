<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_secret_gift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('calendar_activities')->cascadeOnDelete();
            $table->unsignedBigInteger('giver_user_id');
            $table->unsignedBigInteger('recipient_user_id');
            $table->text('gift_text')->nullable();
            $table->string('gift_image_path')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'giver_user_id'], 'sg_assignments_giver_unique');
            $table->unique(['activity_id', 'recipient_user_id'], 'sg_assignments_recipient_unique');
            $table->index('giver_user_id');
            $table->index('recipient_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_secret_gift_assignments');
    }
};
