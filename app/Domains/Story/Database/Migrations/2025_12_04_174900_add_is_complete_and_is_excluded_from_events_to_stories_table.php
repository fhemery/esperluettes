<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('is_complete')->default(false)->after('story_ref_feedback_id');
            $table->boolean('is_excluded_from_events')->default(false)->after('is_complete');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['is_complete', 'is_excluded_from_events']);
        });
    }
};
