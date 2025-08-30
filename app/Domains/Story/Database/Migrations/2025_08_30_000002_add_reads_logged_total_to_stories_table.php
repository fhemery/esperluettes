<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->unsignedBigInteger('reads_logged_total')->default(0)->after('last_chapter_published_at');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('reads_logged_total');
        });
    }
};
