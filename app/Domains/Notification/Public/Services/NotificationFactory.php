<?php

namespace App\Domains\Notification\Public\Services;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class NotificationFactory
{
    /** @var array<string, class-string<NotificationContent>> */
    private array $map = [];

    /**
     * Register a notification content type.
     *
     * @param string $type Unique type identifier
     * @param class-string<NotificationContent> $class
     */
    public function register(string $type, string $class): void
    {
        $this->map[$type] = $class;
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
}
