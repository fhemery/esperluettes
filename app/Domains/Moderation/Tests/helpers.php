<?php

use App\Domains\Moderation\Private\Models\ModerationReason;

/**
 * Create a ModerationReason for a topic.
 * If $sortOrder is null, it will be set to max+1 for that topic.
 */
function createReason(string $topicKey, string $label, ?int $sortOrder = null, bool $isActive = true): ModerationReason
{
    if ($sortOrder === null) {
        $sortOrder = (int) (ModerationReason::where('topic_key', $topicKey)->max('sort_order') ?? -1) + 1;
    }

    return ModerationReason::create([
        'topic_key' => $topicKey,
        'label' => $label,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
    ]);
}

/**
 * Seed multiple reasons for a topic, preserving provided order.
 * Returns the created ModerationReason[] in order.
 */
function seedReasons(string $topicKey, array $labels, bool $isActive = true): array
{
    $created = [];
    foreach (array_values($labels) as $index => $label) {
        $created[] = createReason($topicKey, (string) $label, null, $isActive);
    }
    return $created;
}

/**
 * Ensure a default 'Other' reason exists for a topic and return it.
 */
function defaultReason(string $topicKey): ModerationReason
{
    $existing = ModerationReason::where('topic_key', $topicKey)
        ->where('label', 'Other')
        ->first();

    if ($existing) {
        return $existing;
    }

    return createReason($topicKey, 'Other');
}
