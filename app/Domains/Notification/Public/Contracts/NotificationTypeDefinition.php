<?php

namespace App\Domains\Notification\Public\Contracts;

final class NotificationTypeDefinition
{
    public function __construct(
        public readonly string $type,
        /** @var class-string<NotificationContent> */
        public readonly string $class,
        public readonly string $groupId,
        public readonly string $nameKey,
        public readonly bool $forcedOnWebsite = false,
        public readonly bool $hideInSettings = false,
    ) {}
}
