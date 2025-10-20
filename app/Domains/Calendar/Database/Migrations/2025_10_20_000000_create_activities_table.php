<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('activity_type');
            $table->json('role_restrictions');
            $table->boolean('requires_subscription')->default(false);
            $table->integer('max_participants')->nullable();
            $table->timestamp('preview_starts_at')->nullable();
            $table->timestamp('active_starts_at')->nullable();
            $table->timestamp('active_ends_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            // No foreign key to users (external domain); keep as nullable plain column
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['activity_type', 'active_starts_at', 'active_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_activities');
    }
};
