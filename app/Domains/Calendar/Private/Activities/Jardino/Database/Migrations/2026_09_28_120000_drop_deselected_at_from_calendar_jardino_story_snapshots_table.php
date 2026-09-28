<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calendar_jardino_story_snapshots', function (Blueprint $table) {
            $table->dropColumn('deselected_at');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_jardino_story_snapshots', function (Blueprint $table) {
            $table->timestamp('deselected_at')->nullable()->after('selected_at');
        });
    }
};
