<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            $table->timestamp('publish_at')->nullable()->after('first_published_at');
        });
    }

    public function down(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            $table->dropColumn('publish_at');
        });
    }
};
