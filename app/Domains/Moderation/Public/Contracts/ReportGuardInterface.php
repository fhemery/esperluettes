<?php

namespace App\Domains\Moderation\Public\Contracts;

/**
 * Optional. Implemented by a topic's formatter alongside
 * SnapshotFormatterInterface to refuse a report for a given (entity, reporter).
 * A refusal makes the report endpoint answer 403 and stores nothing.
 */
interface ReportGuardInterface
{
    /** False refuses the report; called before any snapshot is captured. */
    public function canReport(int $entityId, int $reporterId): bool;
}
