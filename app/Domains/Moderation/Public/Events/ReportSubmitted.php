<?php

namespace App\Domains\Moderation\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Contracts\AuditableEvent;

class ReportSubmitted implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $reportId,
        public string $topicKey,
        public int $entityId,
        public int $reasonId,
        public int $reportedByUserId,
        public ?string $reasonLabel = null,
    ) {}

    public static function name(): string { return 'Moderation.ReportSubmitted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'reportId' => $this->reportId,
            'topicKey' => $this->topicKey,
            'entityId' => $this->entityId,
            'reasonId' => $this->reasonId,
            'reportedByUserId' => $this->reportedByUserId,
            'reasonLabel' => $this->reasonLabel,
        ];
    }

    public function summary(): string
    {
        return trans('moderation::events.report_submitted.summary', [
            'reportId' => $this->reportId,
            'topic' => $this->topicKey,
            'entityId' => $this->entityId,
            'reason' => $this->reasonLabel ?? (string) $this->reasonId,
            'userId' => $this->reportedByUserId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            reportId: (int) ($payload['reportId'] ?? 0),
            topicKey: (string) ($payload['topicKey'] ?? ''),
            entityId: (int) ($payload['entityId'] ?? 0),
            reasonId: (int) ($payload['reasonId'] ?? 0),
            reportedByUserId: (int) ($payload['reportedByUserId'] ?? 0),
            reasonLabel: $payload['reasonLabel'] ?? null,
        );
    }
}
