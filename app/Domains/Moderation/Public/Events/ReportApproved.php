<?php

namespace App\Domains\Moderation\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Contracts\AuditableEvent;

class ReportApproved implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $reportId,
    ) {}

    public static function name(): string { return 'Moderation.ReportApproved'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'reportId' => $this->reportId,
        ];
    }

    public function summary(): string
    {
        return trans('moderation::events.report_approved.summary', [
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
