<?php

namespace App\Domains\Auth\Events;

class UserNameUpdated
{
    public function __construct(
        public int $userId,
        public string $oldName,
        public string $newName,
        public ?\DateTimeInterface $changedAt = null,
    ) {}
}
