<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_activation_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 18)->unique();
            $table->foreignId('sponsor_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comment')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['code']);
            $table->index(['expires_at']);
            $table->index(['used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activation_codes');
    }
};
