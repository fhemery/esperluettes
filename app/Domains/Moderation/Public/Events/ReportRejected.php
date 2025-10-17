<?php

namespace App\Domains\Moderation\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Contracts\AuditableEvent;

class ReportRejected implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $reportId,
    ) {}

    public static function name(): string { return 'Moderation.ReportRejected'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'reportId' => $this->reportId,
        ];
    }

    public function summary(): string
    {
        return trans('moderation::events.report_rejected.summary', [
            'reportId' => $this->reportId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            reportId: (int) ($payload['reportId'] ?? 0),
        );
    }
}
