<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('story_ref_genres', function (Blueprint $table) {
            $table->boolean('has_cover')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('story_ref_genres', function (Blueprint $table) {
            $table->dropColumn('has_cover');
        });
    }
};
