<?php

namespace App\Domains\Notification\Public\Contracts;

/**
 * Base contract for notification content.
 * Implementations should be immutable (readonly properties) and data must be JSON-serializable.
 */
interface NotificationContent
{
    /**
     * Unique notification type identifier (e.g., "story.chapter.comment").
     */
    public static function type(): string;

    /**
     * Serialize to a JSON-serializable array (scalars/arrays/IDs only). No Eloquent models.
     *
     * @return array<string, mixed>
     */
    public function toData(): array;

    /**
     * Reconstruct from stored data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): static;

    /**
     * Render the notification as HTML for display to the user.
     */
    public function display(): string;
}
