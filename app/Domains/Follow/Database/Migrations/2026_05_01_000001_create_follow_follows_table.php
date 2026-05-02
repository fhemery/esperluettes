<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('follower_id');
            $table->unsignedBigInteger('followed_id');
            $table->timestamp('created_at');

            $table->unique(['follower_id', 'followed_id']);
            $table->index('followed_id');
            $table->index('follower_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_follows');
    }
};
