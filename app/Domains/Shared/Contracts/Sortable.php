<?php

namespace App\Domains\Shared\Contracts;

interface Sortable
{
    public function getId(): int;

    public function getSortOrder(): int;

    public function setSortOrder(int $order): void;
}
