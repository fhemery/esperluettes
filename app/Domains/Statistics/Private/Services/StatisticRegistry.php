<?php

namespace App\Domains\Statistics\Private\Services;

use App\Domains\Statistics\Public\Contracts\StatisticDefinition;

class StatisticRegistry
{
    /** @var array<string, class-string<StatisticDefinition>> */
    private array $definitions = [];

    /** @var array<string, string[]> Event name => statistic keys that listen to it */
    private array $eventListeners = [];

    /**
     * Register a statistic definition
     * @param class-string<StatisticDefinition> $definitionClass
     */
    public function register(string $definitionClass): void
    {
        $key = $definitionClass::key();
        $this->definitions[$key] = $definitionClass;

        foreach ($definitionClass::listensTo() as $eventName) {
            $this->eventListeners[$eventName] ??= [];
            $this->eventListeners[$eventName][] = $key;
        }
    }

    /**
     * Get all registered definition classes
     * @return array<string, class-string<StatisticDefinition>>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Get a definition class by key
     * @return class-string<StatisticDefinition>|null
     */
    public function get(string $key): ?string
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * Get all statistic keys that listen to a given event
     * @return string[]
     */
    public function getListenersForEvent(string $eventName): array
    {
        return $this->eventListeners[$eventName] ?? [];
    }

    /**
     * Get all event names that have registered listeners
     * @return string[]
     */
    public function getAllListenedEvents(): array
    {
        return array_keys($this->eventListeners);
    }

    /**
     * Resolve a definition instance by key
     */
    public function resolve(string $key): ?StatisticDefinition
    {
        $class = $this->get($key);
        if ($class === null) {
            return null;
        }

        return app($class);
    }
}
