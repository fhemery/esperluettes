<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->index();
            $table->text('content');
            // Store sent_by_id without enforcing cross-domain FK
            $table->unsignedBigInteger('sent_by_id')->index();
            $table->timestamp('sent_at')->nullable();
            // Optional: reply to another message (internal FK)
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
