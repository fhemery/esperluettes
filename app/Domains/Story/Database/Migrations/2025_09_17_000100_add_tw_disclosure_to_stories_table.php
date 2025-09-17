<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            // tw_disclosure: 'listed' | 'no_tw' | 'unspoiled' (non-null, default 'unspoiled')
            $table->string('tw_disclosure', 20)->default('unspoiled')->index()->after('story_ref_feedback_id');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('tw_disclosure');
        });
    }
};
