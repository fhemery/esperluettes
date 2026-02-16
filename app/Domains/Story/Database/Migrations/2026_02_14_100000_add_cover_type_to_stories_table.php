<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('cover_type', 20)->default('default')->after('is_excluded_from_events');
            $table->string('cover_data')->nullable()->after('cover_type');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('cover_type');
            $table->dropColumn('cover_data');
        });
    }
};
