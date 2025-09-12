<?php

namespace App\Domains\Events\Contracts;

/**
 * Base contract for emitted domain events.
 * Implementations should be immutable (readonly properties) and payloads must be JSON-serializable.
 */
interface DomainEvent
{
    /**
     * Logical event name used for storage and routing (e.g., "Auth.UserRegistered").
     */
    public static function name(): string;

    /**
     * Optional semantic version of the event. Defaults to 1.
     */
    public static function version(): int;

    /**
     * Serialize to a JSON-serializable array (scalars/arrays/IDs only). No Eloquent models.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * Returns the summary of the event for display
     */
    public function summary(): string;

    /**
     * Reconstruct a DTO from stored payload.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static;
}
