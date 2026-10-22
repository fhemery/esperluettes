<?php

namespace App\Domains\Moderation\Public\Contracts;

interface SnapshotFormatterInterface
{
    /**
     * Capture a snapshot of the entity at the time of reporting.
     * Returns an array that will be stored as JSON.
     *
     * Reserved key `_media`: a `string[]` of Media paths to keep with the report.
     * Moderation copies each file into `moderation/{topicKey}` and stores the
     * copied paths in its place (a missing source is dropped). Copies are kept as
     * long as the report row exists.
     */
    public function capture(int $entityId): array;

    /**
     * Render the snapshot for display in the admin panel.
     * Returns HTML string for the Filament resource.
     *
     * When the snapshot has `_media`, it also carries `_media_urls` (same order):
     * moderator-only URLs usable in `<img>` / `<audio>`.
     */
    public function render(array $snapshot): string;

    /**
     * Get the user ID of the content owner (reported user).
     * Returns the entity's created_by_user_id or equivalent.
     */
    public function getReportedUserId(int $entityId): int;

    public function getContentUrl(int $entityId): string;
}
