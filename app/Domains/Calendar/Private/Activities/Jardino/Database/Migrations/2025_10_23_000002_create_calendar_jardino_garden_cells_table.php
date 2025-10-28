<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_jardino_garden_cells', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedSmallInteger('x');
            $table->unsignedSmallInteger('y');
            $table->enum('type', ['flower', 'blocked']);
            $table->string('flower_image', 20)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('planted_at')->nullable();
            $table->timestamps();

            $table->foreign('activity_id')->references('id')->on('calendar_activities')->onDelete('cascade');
            $table->unique(['activity_id', 'x', 'y'], 'unique_cell_per_activity');
            $table->index(['activity_id']);
            $table->index(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_jardino_garden_cells');
    }
};
