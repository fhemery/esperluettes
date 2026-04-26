<?php

namespace App\Domains\Notification\Public\Contracts;

final readonly class NotificationDto
{
    public function __construct(
        public int    $id,
        public string $type,
        public array  $data,
    ) {}
}
