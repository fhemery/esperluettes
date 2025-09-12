<?php

namespace App\Domains\Events\Contracts;

use App\Domains\Events\Contracts\DomainEvent;

class StoredDomainEventDto
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly array $payload,
        private readonly ?\DateTimeInterface $occurredAt,
        private readonly ?DomainEvent $domainEvent,
        private readonly ?int $triggeredByUserId,
        private readonly ?string $contextIp,
        private readonly ?string $contextUserAgent,
        private readonly ?string $contextUrl,
        private readonly ?array $meta,
    ) {}

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function payload(): array { return $this->payload; }
    public function occurredAt(): ?\DateTimeInterface { return $this->occurredAt; }
    public function domainEvent(): ?DomainEvent { return $this->domainEvent; }
    public function triggeredByUserId(): ?int { return $this->triggeredByUserId; }
    public function contextIp(): ?string { return $this->contextIp; }
    public function contextUserAgent(): ?string { return $this->contextUserAgent; }
    public function contextUrl(): ?string { return $this->contextUrl; }
    public function meta(): ?array { return $this->meta; }

    public function summary(): ?string
    {
        return $this->domainEvent?->summary();
    }
}
