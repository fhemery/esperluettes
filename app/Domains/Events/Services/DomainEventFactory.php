<?php

namespace App\Domains\Events\Services;

use App\Domains\Events\Contracts\DomainEvent;

class DomainEventFactory
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $map = [];

    public function register(string $name, string $dtoClass): void
    {
        $this->map[$name] = $dtoClass;
    }

    public function resolve(string $name): ?string
    {
        return $this->map[$name] ?? null;
    }

    public function make(string $name, array $payload): ?DomainEvent
    {
        $class = $this->resolve($name);
        if (!is_string($class) || !class_exists($class) || !method_exists($class, 'fromPayload')) {
            return null;
        }
        /** @var class-string<DomainEvent> $class */
        return $class::fromPayload($payload);
    }
}
