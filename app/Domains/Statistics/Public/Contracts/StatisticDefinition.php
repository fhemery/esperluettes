<?php

namespace App\Domains\Statistics\Public\Contracts;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Public\DTOs\ComputeResult;

interface StatisticDefinition
{
    /**
     * Unique identifier for this statistic (e.g., 'global.total_users')
     */
    public static function key(): string;

    /**
     * Scope type: 'global', 'user', 'story', etc.
     */
    public static function scopeType(): string;

    /**
     * Whether this statistic maintains time-series history
     */
    public static function hasTimeSeries(): bool;

    /**
     * Event names this statistic reacts to
     * @return string[]
     */
    public static function listensTo(): array;

    /**
     * Recompute the statistic from scratch.
     * 
     * The statistic is responsible for:
     * - Clearing its existing data (snapshot and time-series)
     * - Rebuilding all data from its source (database, events, etc.)
     * 
     * @param mixed $scopeId The scope identifier (null for global)
     * @return ComputeResult Summary of the computation
     */
    public function recompute(mixed $scopeId = null): ComputeResult;

    /**
     * Compute incremental update from an event
     * Returns [scopeId => delta] pairs, or null if event doesn't affect this stat
     * @return array<mixed, float|int>|null
     */
    public function computeDelta(DomainEvent $event): ?array;

    /**
     * Human-readable label (for admin/debug)
     */
    public static function label(): string;
}
