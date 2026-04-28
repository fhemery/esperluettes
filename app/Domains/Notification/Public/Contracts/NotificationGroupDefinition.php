<?php

namespace App\Domains\Notification\Public\Contracts;

final class NotificationGroupDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly int $sortOrder,
        public readonly string $translationKey,
    ) {}
}
