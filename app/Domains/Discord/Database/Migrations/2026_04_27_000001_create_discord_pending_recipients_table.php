<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discord_pending_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_notification_id')
                  ->constrained('discord_pending_notifications')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string('discord_id', 20);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['pending_notification_id', 'sent_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_pending_recipients');
    }
};
