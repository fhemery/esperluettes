<?php

namespace App\Domains\Moderation\Public\Services;

use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use InvalidArgumentException;

class ModerationRegistry
{
    /**
     * Registered topics stored in memory.
     * Structure: ['topic_key' => ['displayName' => '...', 'formatterClass' => '...']]
     */
    private array $topics = [];

    /**
     * Register a topic for moderation.
     *
     * @param string $key Unique topic identifier (e.g., 'profile', 'story', 'chapter', 'comment')
     * @param string $displayName Translatable display name for the topic
     * @param string|null $formatterClass FQCN of the formatter class (must implement SnapshotFormatterInterface)
     * @throws InvalidArgumentException If topic already registered or formatter invalid
     */
    public function register(string $key, string $displayName, ?string $formatterClass = null): void
    {
        // Check if topic already registered
        if (isset($this->topics[$key])) {
            throw new InvalidArgumentException("Topic '{$key}' is already registered.");
        }

        // Validate formatter class if provided
        if ($formatterClass !== null) {
            if (! class_exists($formatterClass)) {
                throw new InvalidArgumentException("Formatter class '{$formatterClass}' does not exist.");
            }

            if (! in_array(SnapshotFormatterInterface::class, class_implements($formatterClass) ?: [])) {
                throw new InvalidArgumentException(
                    "Formatter class '{$formatterClass}' must implement " . SnapshotFormatterInterface::class
                );
            }
        }

        // Store topic configuration
        $this->topics[$key] = [
            'displayName' => $displayName,
            'formatterClass' => $formatterClass,
        ];
    }

    /**
     * Get all registered topics.
     *
     * @return array<string, array{displayName: string, formatterClass: string|null}>
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    /**
     * Get a specific topic configuration.
     *
     * @throws InvalidArgumentException If topic not registered
     */
    public function getTopic(string $key): array
    {
        if (! isset($this->topics[$key])) {
            throw new InvalidArgumentException("Topic '{$key}' is not registered.");
        }

        return $this->topics[$key];
    }

    /**
     * Check if a topic is registered.
     */
    public function hasTopic(string $key): bool
    {
        return isset($this->topics[$key]);
    }

    /**
     * Get the formatter instance for a topic.
     *
     * @throws InvalidArgumentException If topic not registered or has no formatter
     */
    public function getFormatter(string $key): SnapshotFormatterInterface
    {
        $topic = $this->getTopic($key);

        if ($topic['formatterClass'] === null) {
            throw new InvalidArgumentException("Topic '{$key}' has no formatter configured.");
        }

        return app($topic['formatterClass']);
    }

    /**
     * Check if a topic has a formatter configured.
     */
    public function hasFormatter(string $key): bool
    {
        return $this->hasTopic($key) && $this->topics[$key]['formatterClass'] !== null;
    }
}
