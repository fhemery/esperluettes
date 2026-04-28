<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discord_pending_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')
                  ->constrained('notifications')
                  ->onDelete('cascade');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_pending_notifications');
    }
};
