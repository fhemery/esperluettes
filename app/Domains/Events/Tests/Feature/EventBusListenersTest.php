<?php

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    ListenerSpy::reset();
});

it('calls a listener registered for a single event when that event is emitted', function () {
    // Arrange: register mapping and subscribe
    app(EventBus::class)->registerEvent('Test.A', FakeEventA::class);
    app(EventBus::class)->subscribe('Test.A', [ListenerSpy::class, 'handle']);

    // Act
    app(EventBus::class)->emit(new FakeEventA(1));

    // Assert
    expect(ListenerSpy::$received)->toHaveCount(1);
    expect(ListenerSpy::$received[0])->toBeInstanceOf(FakeEventA::class);
});

it('does not call a listener when a different event is emitted', function () {
    // Arrange
    app(EventBus::class)->registerEvent('Test.A', FakeEventA::class);
    app(EventBus::class)->registerEvent('Test.B', FakeEventB::class);
    app(EventBus::class)->subscribe('Test.A', [ListenerSpy::class, 'handle']);

    // Act: emit B
    app(EventBus::class)->emit(new FakeEventB(2));

    // Assert
    expect(ListenerSpy::$received)->toBeEmpty();
});

it('calls a listener registered for multiple events when one of them is emitted', function () {
    // Arrange
    app(EventBus::class)->registerEvent('Test.A', FakeEventA::class);
    app(EventBus::class)->registerEvent('Test.B', FakeEventB::class);

    app(EventBus::class)->subscribe(['Test.A', 'Test.B'], [ListenerSpy::class, 'handle']);

    // Act & Assert
    app(EventBus::class)->emit(new FakeEventA(10));
    expect(ListenerSpy::$received)->toHaveCount(1);
    expect(ListenerSpy::$received[0])->toBeInstanceOf(FakeEventA::class);

    ListenerSpy::reset();
    app(EventBus::class)->emit(new FakeEventB(20));
    expect(ListenerSpy::$received)->toHaveCount(1);
    expect(ListenerSpy::$received[0])->toBeInstanceOf(FakeEventB::class);
});

// Helper spy listener
final class ListenerSpy
{
    /** @var array<int, object> */
    public static array $received = [];

    public static function reset(): void
    {
        self::$received = [];
    }

    public function handle(object $event): void
    {
        self::$received[] = $event;
    }
}

// Fake events
final class FakeEventA implements DomainEvent
{
    public function __construct(public readonly int $id) {}

    public static function name(): string { return 'Test.A'; }
    public static function version(): int { return 1; }

    public function toPayload(): array { return ['id' => $this->id]; }

    public function summary(): string { return 'A=' . $this->id; }

    public function occurredAt(): \DateTimeInterface { return new \DateTimeImmutable(); }

    public static function fromPayload(array $payload): static
    {
        return new static((int) ($payload['id'] ?? 0));
    }
}

final class FakeEventB implements DomainEvent
{
    public function __construct(public readonly int $id) {}

    public static function name(): string { return 'Test.B'; }
    public static function version(): int { return 1; }

    public function toPayload(): array { return ['id' => $this->id]; }

    public function summary(): string { return 'B=' . $this->id; }

    public function occurredAt(): \DateTimeInterface { return new \DateTimeImmutable(); }

    public static function fromPayload(array $payload): static
    {
        return new static((int) ($payload['id'] ?? 0));
    }
}
