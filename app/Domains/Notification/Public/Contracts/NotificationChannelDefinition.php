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
        /** fn(NotificationDto $dto, array $userIds): void */
        public readonly Closure $deliveryCallback,
        /** fn(): bool — null means always active */
        public readonly ?Closure $featureCheck = null,
    ) {}
}
