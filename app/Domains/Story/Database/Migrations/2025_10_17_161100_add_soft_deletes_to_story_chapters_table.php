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
        Schema::table('story_chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('story_chapters', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            if (Schema::hasColumn('story_chapters', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
