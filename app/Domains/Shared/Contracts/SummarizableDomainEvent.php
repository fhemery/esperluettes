<?php

namespace App\Domains\Shared\Contracts;

interface SummarizableDomainEvent
{
    /**
     * Return a short, human-readable summary for admin display, based on payload.
     */
    public static function summarizePayload(array $payload): string;
}
