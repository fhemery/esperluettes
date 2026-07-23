<?php

use App\Domains\Quote\Private\Models\Quote;

function createQuote(int $userId, int $chapterId, int $storyId, array $attrs = []): Quote
{
    return Quote::create(array_merge([
        'user_id' => $userId,
        'chapter_id' => $chapterId,
        'story_id' => $storyId,
        'highlighted_text' => 'Some quoted text',
        'prefix' => 'words before',
        'suffix' => 'words after',
        'note' => null,
    ], $attrs));
}
