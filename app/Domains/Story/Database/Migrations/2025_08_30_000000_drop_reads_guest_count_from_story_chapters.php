<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            if (Schema::hasColumn('story_chapters', 'reads_guest_count')) {
                $table->dropColumn('reads_guest_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('story_chapters', 'reads_guest_count')) {
                $table->unsignedInteger('reads_guest_count')->default(0);
            }
        });
    }
};
