<?php

namespace App\Domains\Moderation\Private\Services;

use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Moderation\Public\Events\ReportSubmitted;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Moderation\Public\Events\ReportApproved;
use App\Domains\Moderation\Public\Events\ReportRejected;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ModerationService
{
    private const PENDING_COUNT_CACHE_KEY = 'moderation.pending_reports_count';
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

    public function approveReport(int $reportId): void
    {
        $report = ModerationReport::findOrFail($reportId);
        $report->update(['status' => 'confirmed']);

        // Invalidate cached pending count
        Cache::forget(self::PENDING_COUNT_CACHE_KEY);

        // Emit ReportApproved event
        app(EventBus::class)->emit(new ReportApproved(reportId: $reportId));
    }

    public function dismissReport(int $reportId): void
    {
        $report = ModerationReport::findOrFail($reportId);
        $report->update(['status' => 'dismissed']);

        // Invalidate cached pending count
        Cache::forget(self::PENDING_COUNT_CACHE_KEY);

        // Emit ReportRejected event
        app(EventBus::class)->emit(new ReportRejected(reportId: $reportId));
    }

    public function deleteReport(int $reportId): void
    {
        $report = ModerationReport::findOrFail($reportId);
        $report->delete();

        Cache::forget(self::PENDING_COUNT_CACHE_KEY);
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
        $created = ModerationReport::create([
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

        // Invalidate cached pending count
        Cache::forget(self::PENDING_COUNT_CACHE_KEY);

        // Emit domain event (persisted and dispatched)
        /** @var EventBus $eventBus */
        $eventBus = app(EventBus::class);
        $eventBus->emitSync(new ReportSubmitted(
            reportId: (int) $created->id,
            topicKey: $topicKey,
            entityId: $entityId,
            reasonId: $reasonId,
            reportedByUserId: (int) Auth::id(),
            reasonLabel: $reason->label ?? null,
        ));

        return $created;
    }

    /**
     * Build a content URL for the reported entity.
     * This is a basic implementation; domains may override this in the future.
     */
    private function buildContentUrl(string $topicKey, int $entityId): string
    {
        // Prefer formatter-specific URL if available
        if ($this->registry->hasFormatter($topicKey)) {
            try {
                return $this->registry->getFormatter($topicKey)->getContentUrl($entityId);
            } catch (\Throwable $e) {
                // Fallback to basic generation below
            }
        }

        // Basic fallback URL generation based on topic
        return '';
    }

    /**
     * Get total number of pending moderation reports (cached until invalidated).
     */
    public function getPendingReportsCount(): int
    {
        return Cache::rememberForever(self::PENDING_COUNT_CACHE_KEY, function () {
            return ModerationReport::where('status', 'pending')->count();
        });
    }
}
