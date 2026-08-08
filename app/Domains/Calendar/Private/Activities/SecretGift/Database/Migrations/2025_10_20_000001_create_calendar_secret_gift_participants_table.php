<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Previously shipped as 2024_12_13_140000_* — already applied in production.
        if (Schema::hasTable('calendar_secret_gift_participants')) {
            return;
        }

        Schema::create('calendar_secret_gift_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('calendar_activities')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->text('preferences')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id'], 'sg_participants_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_secret_gift_participants');
    }
};
