<?php

use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Models\ModerationReport;
use Illuminate\Contracts\Auth\Authenticatable;

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

/**
 * Create a moderation report for a specific user using direct model creation.
 * This bypasses authentication and service requirements for testing.
 */
function createReportForUser(Authenticatable $user, string $status = 'pending'): int
{
    $reason = createReason('comment', 'Test Reason');
    
    return ModerationReport::create([
        'reported_user_id' => $user->id,
        'reported_by_user_id' => $user->id, // Use same user for simplicity
        'reason_id' => $reason->id,
        'topic_key' => 'comment',
        'entity_id' => rand(1000, 9999),
        'status' => $status,
        'description' => 'Test report for user',
        'content_snapshot' => [],
        'content_url' => '/',
    ])->id;
}

/**
 * Test topic whose formatter attaches media files to the snapshot.
 * Set MediaSnapshotTestFormatter::$media to the paths to capture.
 */
class MediaSnapshotTestFormatter implements \App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface
{
    /** @var string[] */
    public static array $media = [];

    /** @var array|null Last snapshot given to render() */
    public static ?array $rendered = null;

    public function capture(int $entityId): array
    {
        return ['title' => 'Reported', '_media' => self::$media];
    }

    public function render(array $snapshot): string
    {
        self::$rendered = $snapshot;

        return '<p>' . e($snapshot['title'] ?? '') . '</p>';
    }

    public function getReportedUserId(int $entityId): int
    {
        return (int) \Illuminate\Support\Facades\Auth::id();
    }

    public function getContentUrl(int $entityId): string
    {
        return '/';
    }
}

/**
 * Register the 'mediatest' topic (once per app instance) with a reason, and
 * create a report on it as $reporter. Returns the report model.
 */
function createMediaReport(Authenticatable $reporter, array $media): ModerationReport
{
    $registry = app(\App\Domains\Moderation\Public\Services\ModerationRegistry::class);
    if (! $registry->hasTopic('mediatest')) {
        $registry->register('mediatest', 'Media test', MediaSnapshotTestFormatter::class);
    }
    MediaSnapshotTestFormatter::$media = $media;
    $reason = createReason('mediatest', 'Because');

    test()->actingAs($reporter);

    return app(\App\Domains\Moderation\Private\Services\ModerationService::class)
        ->createReport('mediatest', 1, $reason->id);
}
