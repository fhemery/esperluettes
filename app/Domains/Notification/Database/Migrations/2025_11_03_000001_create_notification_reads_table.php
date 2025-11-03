<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->integer('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->primary(['notification_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
