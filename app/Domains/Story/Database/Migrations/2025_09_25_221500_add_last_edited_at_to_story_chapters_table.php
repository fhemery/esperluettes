<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            // Non-nullable with default to now so we never crash if not explicitly set
            $table->timestamp('last_edited_at')->useCurrent()->after('first_published_at');
        });
        
        // Backfill: set last_edited_at = updated_at for existing rows
        DB::table('story_chapters')->update(['last_edited_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            $table->dropColumn('last_edited_at');
        });
    }
};
