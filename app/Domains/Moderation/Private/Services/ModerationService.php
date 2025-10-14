<?php

namespace App\Domains\Moderation\Private\Services;

use App\Domains\Moderation\Models\ModerationReason;
use App\Domains\Moderation\Models\ModerationReport;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use Illuminate\Support\Facades\Auth;

class ModerationService
{
    public function __construct(
        private ModerationRegistry $registry
    ) {
    }

    /**
     * Get active reasons for a given topic.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ModerationReason>
     */
    public function getReasonsForTopic(string $topicKey)
    {
        // Validate topic exists
        if (! $this->registry->hasTopic($topicKey)) {
            throw new \InvalidArgumentException("Topic '{$topicKey}' is not registered.");
        }

        return ModerationReason::where('topic_key', $topicKey)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'label']);
    }

    /**
     * Create a new moderation report.
     *
     * @throws \InvalidArgumentException If topic not registered or reason not found
     */
    public function createReport(
        string $topicKey,
        int $entityId,
        int $reasonId,
        ?string $description = null
    ): ModerationReport {
        // Validate topic exists
        if (! $this->registry->hasTopic($topicKey)) {
            throw new \InvalidArgumentException("Topic '{$topicKey}' is not registered.");
        }

        // Validate reason exists and belongs to this topic
        $reason = ModerationReason::where('id', $reasonId)
            ->where('topic_key', $topicKey)
            ->first();

        if (! $reason) {
            throw new \InvalidArgumentException("Reason '{$reasonId}' not found for topic '{$topicKey}'.");
        }

        // Get reported user ID (if formatter available)
        $reportedUserId = null;
        $snapshot = null;

        if ($this->registry->hasFormatter($topicKey)) {
            $formatter = $this->registry->getFormatter($topicKey);
            $reportedUserId = $formatter->getReportedUserId($entityId);
            $snapshot = $formatter->capture($entityId);
        }

        // Build content URL (basic implementation for now)
        $contentUrl = $this->buildContentUrl($topicKey, $entityId);

        // Create report
        return ModerationReport::create([
            'topic_key' => $topicKey,
            'entity_id' => $entityId,
            'reported_user_id' => $reportedUserId,
            'reported_by_user_id' => Auth::id(),
            'reason_id' => $reasonId,
            'description' => $description,
            'content_snapshot' => $snapshot,
            'content_url' => $contentUrl,
            'status' => 'pending',
        ]);
    }

    /**
     * Build a content URL for the reported entity.
     * This is a basic implementation; domains may override this in the future.
     */
    private function buildContentUrl(string $topicKey, int $entityId): string
    {
        // Basic URL generation based on topic
        // TODO: This should be improved to use actual routes from domains
        return match ($topicKey) {
            'profile' => route('profile.show', $entityId),
            'story' => route('story.show', $entityId),
            'chapter' => route('chapter.show', $entityId),
            'comment' => '#comment-' . $entityId, // Comments are anchors within pages
            default => '/',
        };
    }
}
