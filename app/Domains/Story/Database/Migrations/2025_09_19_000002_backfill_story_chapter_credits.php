<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backfill story_chapter_credits from existing data
        // 1) Earned credits per user from root comments on published chapters, excluding self-comments
        $earned = DB::table('comments as c')
            ->join('story_chapters as ch', function ($j) {
                $j->on('ch.id', '=', 'c.commentable_id')
                  ->where('c.commentable_type', '=', 'chapter');
            })
            ->whereNull('c.parent_comment_id')
            ->where('ch.status', '=', 'published')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('story_collaborators as sc')
                  ->whereColumn('sc.story_id', 'ch.story_id')
                  ->whereColumn('sc.user_id', 'c.author_id')
                  ->where('sc.role', '=', 'author');
            })
            ->groupBy('c.author_id')
            ->select(['c.author_id as user_id', DB::raw('COUNT(DISTINCT c.commentable_id) as cnt')])
            ->pluck('cnt', 'user_id');

        // 2) Spent credits as number of chapters in stories they author
        $spent = DB::table('story_chapters as ch')
            ->join('stories as s', 's.id', '=', 'ch.story_id')
            ->join('story_collaborators as sc', function ($j) {
                $j->on('sc.story_id', '=', 's.id')
                  ->where('sc.role', '=', 'author');
            })
            ->groupBy('sc.user_id')
            ->select(['sc.user_id', DB::raw('COUNT(*) as cnt')])
            ->pluck('cnt', 'user_id');

        $userIds = array_values(array_unique(array_merge(array_keys($earned->all()), array_keys($spent->all()))));

        foreach ($userIds as $uid) {
            $gained = 5 + (int) ($earned[$uid] ?? 0);
            $spentCnt = (int) ($spent[$uid] ?? 0);
            DB::table('story_chapter_credits')->updateOrInsert(
                ['user_id' => $uid],
                [
                    'credits_gained' => $gained,
                    'credits_spent' => $spentCnt,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No down operation; counters can be reset manually if needed
    }
};
