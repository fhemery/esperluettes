<?php

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Events\Public\Api\EventPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('EventPublicApi::getEventsByNames()', function () {
    it('returns empty array when given empty array', function () {
        $api = app(EventPublicApi::class);
        
        $events = $api->getEventsByNames([]);
        
        expect($events)->toBeArray()->toBeEmpty();
    });

    it('returns empty array when no events of the given names exist', function () {
        $api = app(EventPublicApi::class);
        
        $events = $api->getEventsByNames(['NonExistent.Event', 'Another.Missing']);
        
        expect($events)->toBeArray()->toBeEmpty();
    });

    it('returns events matching multiple names', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Multi.First', MultiFirstEvent::class);
        $bus->registerEvent('Multi.Second', MultiSecondEvent::class);
        $bus->registerEvent('Multi.Third', MultiThirdEvent::class);
        
        $bus->emit(new MultiFirstEvent(id: 1));
        $bus->emit(new MultiSecondEvent(id: 2));
        $bus->emit(new MultiThirdEvent(id: 3));
        $bus->emit(new MultiFirstEvent(id: 4));
        
        // Query for two of the three event types
        $events = $api->getEventsByNames(['Multi.First', 'Multi.Second']);
        
        expect($events)->toHaveCount(3);
        
        $names = array_map(fn($dto) => $dto->name(), $events);
        expect($names)->toContain('Multi.First');
        expect($names)->toContain('Multi.Second');
        expect($names)->not->toContain('Multi.Third');
    });

    it('returns events ordered by ID descending across multiple names', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Order.A', OrderAEvent::class);
        $bus->registerEvent('Order.B', OrderBEvent::class);
        
        // Emit in sequence: A(1), B(2), A(3), B(4)
        $bus->emit(new OrderAEvent(value: 1));
        $bus->emit(new OrderBEvent(value: 2));
        $bus->emit(new OrderAEvent(value: 3));
        $bus->emit(new OrderBEvent(value: 4));
        
        $events = $api->getEventsByNames(['Order.A', 'Order.B']);
        
        expect($events)->toHaveCount(4);
        
        // Should be ordered by ID desc: 4, 3, 2, 1
        $values = array_map(fn($dto) => $dto->domainEvent()->value, $events);
        expect($values)->toBe([4, 3, 2, 1]);
    });

    it('getEventsByName calls getEventsByNames with single element array', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Single.Test', SingleTestEvent::class);
        $bus->emit(new SingleTestEvent(id: 42));
        
        $eventsFromSingle = $api->getEventsByName('Single.Test');
        $eventsFromMulti = $api->getEventsByNames(['Single.Test']);
        
        expect($eventsFromSingle)->toHaveCount(1);
        expect($eventsFromMulti)->toHaveCount(1);
        expect($eventsFromSingle[0]->id())->toBe($eventsFromMulti[0]->id());
    });
});

describe('EventPublicApi::getEventsByName()', function () {
    it('does everything getEventsByNames is doing, but for one single event type', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        // Register and emit test events
        $bus->registerEvent('Test.UserAction', TestUserActionEvent::class);
        $bus->registerEvent('Test.OtherAction', TestOtherActionEvent::class);
        
        // Emit multiple events of same type
        $bus->emit(new TestUserActionEvent(userId: 1));
        $bus->emit(new TestUserActionEvent(userId: 2));
        $bus->emit(new TestUserActionEvent(userId: 3));
        
        // Emit different event type
        $bus->emit(new TestOtherActionEvent(id: 99));
        
        // Query by name
        $events = $api->getEventsByName('Test.UserAction');
        
        expect($events)->toBeArray()->toHaveCount(3);
        
        // Verify all are of the correct type
        foreach ($events as $dto) {
            expect($dto->name())->toBe('Test.UserAction');
            expect($dto->domainEvent())->toBeInstanceOf(TestUserActionEvent::class);
        }
    });
});

// Test event implementations

final class TestUserActionEvent implements DomainEvent
{
    public function __construct(public readonly int $userId) {}
    
    public static function name(): string { return 'Test.UserAction'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['userId' => $this->userId]; }
    public function summary(): string { return "User {$this->userId} acted"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(userId: (int) ($payload['userId'] ?? 0));
    }
}

final class TestOtherActionEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'Test.OtherAction'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "Other {$this->id}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class TestOrderedEvent implements DomainEvent
{
    public function __construct(public readonly int $value) {}
    
    public static function name(): string { return 'Test.Ordered'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['value' => $this->value]; }
    public function summary(): string { return "Value {$this->value}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(value: (int) ($payload['value'] ?? 0));
    }
}

final class TestReconstructEvent implements DomainEvent
{
    public function __construct(
        public readonly string $name,
        public readonly int $count
    ) {}
    
    public static function name(): string { return 'Test.Reconstruct'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['name' => $this->name, 'count' => $this->count]; }
    public function summary(): string { return "{$this->name}: {$this->count}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(
            name: (string) ($payload['name'] ?? ''),
            count: (int) ($payload['count'] ?? 0)
        );
    }
}

final class TestInvalidEvent implements DomainEvent
{
    public function __construct(public readonly string $data) {}
    
    public static function name(): string { return 'Test.Invalid'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['data' => $this->data]; }
    public function summary(): string { return $this->data; }
    
    public static function fromPayload(array $payload): static
    {
        // Will throw if 'data' key is missing
        if (!isset($payload['data'])) {
            throw new \InvalidArgumentException('Missing data');
        }
        return new static(data: $payload['data']);
    }
}

final class TestUserCreatedEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'User.Created'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "User {$this->id} created"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class TestUserCreatedV2Event implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'User.CreatedV2'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "User {$this->id} created v2"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class MultiFirstEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'Multi.First'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "Multi First {$this->id}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class MultiSecondEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'Multi.Second'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "Multi Second {$this->id}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class MultiThirdEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'Multi.Third'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "Multi Third {$this->id}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}

final class OrderAEvent implements DomainEvent
{
    public function __construct(public readonly int $value) {}
    
    public static function name(): string { return 'Order.A'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['value' => $this->value]; }
    public function summary(): string { return "Order A {$this->value}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(value: (int) ($payload['value'] ?? 0));
    }
}

final class OrderBEvent implements DomainEvent
{
    public function __construct(public readonly int $value) {}
    
    public static function name(): string { return 'Order.B'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['value' => $this->value]; }
    public function summary(): string { return "Order B {$this->value}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(value: (int) ($payload['value'] ?? 0));
    }
}

final class SingleTestEvent implements DomainEvent
{
    public function __construct(public readonly int $id) {}
    
    public static function name(): string { return 'Single.Test'; }
    public static function version(): int { return 1; }
    
    public function toPayload(): array { return ['id' => $this->id]; }
    public function summary(): string { return "Single Test {$this->id}"; }
    
    public static function fromPayload(array $payload): static
    {
        return new static(id: (int) ($payload['id'] ?? 0));
    }
}
