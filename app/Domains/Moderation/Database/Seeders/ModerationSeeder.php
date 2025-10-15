<?php

namespace App\Domains\Moderation\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Moderation\Models\ModerationReason;

class ModerationSeeder extends Seeder
{
    public function run(): void
    {
        $topics = ['profile', 'story', 'chapter', 'comment'];
        $label = 'Other';

        foreach ($topics as $topic) {
            // Check if an 'Other' reason already exists for this topic
            $exists = ModerationReason::where('topic_key', $topic)
                ->where('label', $label)
                ->exists();

            if ($exists) {
                continue;
            }

            // Compute next sort order per topic
            $nextOrder = (int) (ModerationReason::where('topic_key', $topic)->max('sort_order') ?? -1) + 1;

            ModerationReason::create([
                'topic_key'   => $topic,
                'label'       => $label,
                'sort_order'  => $nextOrder,
                'is_active'   => true,
            ]);
        }
    }
}
