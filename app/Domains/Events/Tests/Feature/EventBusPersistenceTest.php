<?php

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Events\PublicApi\EventPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('persists an auditable event and lists it via EventPublicApi', function () {
    app(EventBus::class)->registerEvent('Test.Auditable', TestAuditableEvent::class);

    app(EventBus::class)->emit(new TestAuditableEvent(userId: 123));

    $items = app(EventPublicApi::class)->list();
    expect($items)->not->toBeEmpty();

    // Find the first DTO with matching name
    $dto = collect($items)->first(fn($it) => $it->name() === 'Test.Auditable');
    expect($dto)->not->toBeNull();
    expect($dto->payload())->toBe(['userId' => 123]);
    // Context exists on DTO accessors; values depend on runtime (CLI vs HTTP)
    expect($dto->occurredAt())->not->toBeNull();
    // Accessors should be callable; no strict value assertions
    $dto->triggeredByUserId();
    $dto->contextIp();
    $dto->contextUserAgent();
    $dto->contextUrl();
});

it('persists a non-auditable event and lists it via EventPublicApi', function () {
    app(EventBus::class)->registerEvent('Test.NonAuditable', TestNonAuditableEvent::class);

    app(EventBus::class)->emit(new TestNonAuditableEvent(id: 42));

    $items = app(EventPublicApi::class)->list();
    expect($items)->not->toBeEmpty();

    $dto = collect($items)->first(fn($it) => $it->name() === 'Test.NonAuditable');
    expect($dto)->not->toBeNull();
    expect($dto->payload())->toBe(['id' => 42]);
    // Non-auditable: context accessors should exist
    $dto->triggeredByUserId();
});

// Test event implementations

final class TestAuditableEvent implements DomainEvent, AuditableEvent
{
    public function __construct(public readonly int $userId) {}

    public static function name(): string { return 'Test.Auditable'; }
    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return ['userId' => $this->userId];
    }

    public function summary(): string
    {
        return 'Auditable userId=' . $this->userId;
    }

    public function occurredAt(): \DateTimeInterface
    {
        // Not persisted via payload in tests; return "now" as a placeholder
        return new \DateTimeImmutable();
    }

    public static function fromPayload(array $payload): static
    {
        return new static(userId: (int) ($payload['userId'] ?? 0));
    }
}

final class TestNonAuditableEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}

    public static function name(): string { return 'Test.NonAuditable'; }
    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return ['id' => $this->id];
    }

    public function summary(): string
    {
        return 'NonAuditable id=' . $this->id;
    }

    public function occurredAt(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }

    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}
