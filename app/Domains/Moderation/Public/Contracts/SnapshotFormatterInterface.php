<?php

namespace App\Domains\Moderation\Public\Contracts;

interface SnapshotFormatterInterface
{
    /**
     * Capture a snapshot of the entity at the time of reporting.
     * Returns an array that will be stored as JSON.
     */
    public function capture(int $entityId): array;

    /**
     * Render the snapshot for display in the admin panel.
     * Returns HTML string for the Filament resource.
     */
    public function render(array $snapshot): string;

    /**
     * Get the user ID of the content owner (reported user).
     * Returns the entity's created_by_user_id or equivalent.
     */
    public function getReportedUserId(int $entityId): int;

    public function getContentUrl(int $entityId): string;
}
