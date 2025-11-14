<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('story_trigger_warnings', function (Blueprint $table) {
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('story_ref_trigger_warning_id');
            $table->timestamps();

            $table->primary(['story_id', 'story_ref_trigger_warning_id'], 'pk_story_trigger_warnings');
            $table->index('story_id');
            $table->index('story_ref_trigger_warning_id', 'idx_story_trigwarn_ref_id');

            $table->foreign('story_id')
                ->references('id')->on('stories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_trigger_warnings');
    }
};
