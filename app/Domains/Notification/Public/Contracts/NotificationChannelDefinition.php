<?php

namespace App\Domains\Notification\Public\Contracts;

use Closure;

final class NotificationChannelDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $nameTranslationKey,
        public readonly bool $defaultEnabled,
        public readonly int $sortOrder,
        /** fn(Notification $notification, array $userIds): void */
        public readonly Closure $deliveryCallback,
        public readonly ?string $featureFlag = null,
    ) {}
}
