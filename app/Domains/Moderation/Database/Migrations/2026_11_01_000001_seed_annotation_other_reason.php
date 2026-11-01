<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give the annotation topic its « Autre » reason so the report modal is usable in production.
     * Skipped when the topic already has any reason (moderators may have created their own).
     */
    public function up(): void
    {
        if (DB::table('moderation_reasons')->where('topic_key', 'annotation')->exists()) {
            return;
        }

        DB::table('moderation_reasons')->insert([
            'topic_key' => 'annotation',
            'label' => 'Autre',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Deliberate no-op: reports may reference the reason, so it is never deleted on rollback.
     */
    public function down(): void
    {
    }
};
