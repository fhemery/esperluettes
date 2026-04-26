<?php

namespace App\Domains\Notification\Public\Services;

use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;

class NotificationChannelRegistry
{
    /** @var array<string, NotificationChannelDefinition> */
    private array $channels = [];

    /**
     * Register an external delivery channel.
     * Called from other domains' ServiceProvider::boot().
     *
     * @throws \InvalidArgumentException if channel id is the reserved 'website' id or already registered
     */
    public function register(NotificationChannelDefinition $channel): void
    {
        if ($channel->id === 'website') {
            throw new \InvalidArgumentException("Channel id 'website' is reserved for the native website channel.");
        }
        if (isset($this->channels[$channel->id])) {
            throw new \InvalidArgumentException("Channel '{$channel->id}' already registered.");
        }
        $this->channels[$channel->id] = $channel;
    }

    public function get(string $id): ?NotificationChannelDefinition
    {
        return $this->channels[$id] ?? null;
    }

    /**
     * Returns channels whose feature flag is active (or have no flag), sorted by sortOrder.
     *
     * @return array<NotificationChannelDefinition>
     */
    public function getActiveChannels(): array
    {
        return collect($this->channels)
            ->filter(fn($c) => $c->featureFlag === null || config($c->featureFlag, false))
            ->sortBy('sortOrder')
            ->values()
            ->all();
    }

    /**
     * @return array<NotificationChannelDefinition> all registered channels, sorted by sortOrder
     */
    public function getAllChannels(): array
    {
        return collect($this->channels)->sortBy('sortOrder')->values()->all();
    }
}
