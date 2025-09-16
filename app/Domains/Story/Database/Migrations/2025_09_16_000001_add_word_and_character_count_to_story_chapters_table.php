<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Domains\Shared\Support\WordCounter;
use App\Domains\Shared\Support\CharacterCounter;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            $table->unsignedInteger('word_count')->default(0)->after('reads_logged_count');
            $table->unsignedInteger('character_count')->default(0)->after('word_count');
        });

        // Backfill existing rows in chunks to avoid memory issues
        DB::table('story_chapters')
            ->select('id', 'content')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $words = WordCounter::count($row->content ?? '');
                    $chars = CharacterCounter::count($row->content ?? '');
                    DB::table('story_chapters')
                        ->where('id', $row->id)
                        ->update([
                            'word_count' => $words,
                            'character_count' => $chars,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('story_chapters', function (Blueprint $table) {
            $table->dropColumn('character_count');
            $table->dropColumn('word_count');
        });
    }
};
