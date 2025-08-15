<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('story_ref_genres', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('slug');
            $table->index('order');
        });

        // Backfill existing rows with a stable sequence based on id
        $rows = DB::table('story_ref_genres')->orderBy('id')->get(['id']);
        $i = 1;
        foreach ($rows as $row) {
            DB::table('story_ref_genres')->where('id', $row->id)->update(['order' => $i++]);
        }
    }

    public function down(): void
    {
        Schema::table('story_ref_genres', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropColumn('order');
        });
    }
};
