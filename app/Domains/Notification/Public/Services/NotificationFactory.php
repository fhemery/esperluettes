<?php

namespace App\Domains\Notification\Public\Services;

use App\Domains\Notification\Public\Contracts\NotificationContent;
use App\Domains\Notification\Public\Contracts\NotificationGroupDefinition;
use App\Domains\Notification\Public\Contracts\NotificationTypeDefinition;

class NotificationFactory
{
    /** @var array<string, class-string<NotificationContent>> */
    private array $map = [];

    /** @var array<string, NotificationGroupDefinition> */
    private array $groups = [];

    /** @var array<string, NotificationTypeDefinition> */
    private array $types = [];

    public function registerGroup(string $id, int $sortOrder, string $translationKey): void
    {
        $this->groups[$id] = new NotificationGroupDefinition($id, $sortOrder, $translationKey);
    }

    /**
     * Register a notification content type.
     *
     * @param class-string<NotificationContent> $class
     * @throws \InvalidArgumentException if groupId is not registered
     */
    public function register(
        string $type,
        string $class,
        string $groupId,
        string $nameKey,
        bool $forcedOnWebsite = false,
        bool $hideInSettings = false,
    ): void {
        if (!isset($this->groups[$groupId])) {
            throw new \InvalidArgumentException("Group '{$groupId}' is not registered. Call registerGroup() first.");
        }
        $this->map[$type] = $class;
        $this->types[$type] = new NotificationTypeDefinition($type, $class, $groupId, $nameKey, $forcedOnWebsite, $hideInSettings);
    }

    /**
     * @return array<NotificationGroupDefinition> sorted by sortOrder
     */
    public function getGroups(): array
    {
        return collect($this->groups)->sortBy('sortOrder')->values()->all();
    }

    /**
     * @return array<NotificationTypeDefinition>
     */
    public function getTypesForGroup(string $groupId, bool $includeHidden = false): array
    {
        return array_values(array_filter(
            $this->types,
            fn($t) => $t->groupId === $groupId && ($includeHidden || !$t->hideInSettings)
        ));
    }

    public function getTypeDefinition(string $type): ?NotificationTypeDefinition
    {
        return $this->types[$type] ?? null;
    }

    /**
     * Resolve the class for a notification type.
     *
     * @return class-string<NotificationContent>|null
     */
    public function resolve(string $type): ?string
    {
        return $this->map[$type] ?? null;
    }

    /**
     * Create a NotificationContent instance from stored data.
     */
    public function make(string $type, array $data): ?NotificationContent
    {
        $class = $this->resolve($type);
        if (!is_string($class) || !class_exists($class) || !method_exists($class, 'fromData')) {
            return null;
        }
        /** @var class-string<NotificationContent> $class */
        return $class::fromData($data);
    }

    /**
     * @return array<int, string>
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->map);
    }
}
