<?php

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Events\Public\Api\EventPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('EventPublicApi::getEventsByName()', function () {
    it('returns empty array when no events of the given name exist', function () {
        $api = app(EventPublicApi::class);
        
        $events = $api->getEventsByName('NonExistent.Event');
        
        expect($events)->toBeArray()->toBeEmpty();
    });

    it('returns all events matching the given name', function () {
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

    it('returns events ordered by ID descending (most recent first)', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Test.Ordered', TestOrderedEvent::class);
        
        // Emit events in sequence
        $bus->emit(new TestOrderedEvent(value: 100));
        $bus->emit(new TestOrderedEvent(value: 200));
        $bus->emit(new TestOrderedEvent(value: 300));
        
        $events = $api->getEventsByName('Test.Ordered');
        
        expect($events)->toHaveCount(3);
        
        // Most recent should be first (value: 300)
        $values = array_map(fn($dto) => $dto->domainEvent()->value, $events);
        expect($values)->toBe([300, 200, 100]);
    });

    it('reconstructs domain events from payload', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Test.Reconstruct', TestReconstructEvent::class);
        $bus->emit(new TestReconstructEvent(name: 'Alice', count: 42));
        
        $events = $api->getEventsByName('Test.Reconstruct');
        
        expect($events)->toHaveCount(1);
        
        $dto = $events[0];
        expect($dto->domainEvent())->toBeInstanceOf(TestReconstructEvent::class);
        expect($dto->domainEvent()->name)->toBe('Alice');
        expect($dto->domainEvent()->count)->toBe(42);
    });

    it('handles events that fail to reconstruct gracefully', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('Test.Invalid', TestInvalidEvent::class);
        $bus->emit(new TestInvalidEvent(data: 'valid'));
        
        // Manually corrupt the payload in database
        DB::table('events_domain')
            ->where('name', 'Test.Invalid')
            ->update(['payload' => json_encode(['corrupted' => 'data'])]);
        
        $events = $api->getEventsByName('Test.Invalid');
        
        expect($events)->toHaveCount(1);
        expect($events[0]->domainEvent())->toBeNull(); // Failed to reconstruct
        expect($events[0]->name())->toBe('Test.Invalid');
        expect($events[0]->payload())->toBe(['corrupted' => 'data']);
    });

    it('does not return events with similar but different names', function () {
        $bus = app(EventBus::class);
        $api = app(EventPublicApi::class);
        
        $bus->registerEvent('User.Created', TestUserCreatedEvent::class);
        $bus->registerEvent('User.CreatedV2', TestUserCreatedV2Event::class);
        
        $bus->emit(new TestUserCreatedEvent(id: 1));
        $bus->emit(new TestUserCreatedV2Event(id: 2));
        
        // Should only return exact match
        $events = $api->getEventsByName('User.Created');
        
        expect($events)->toHaveCount(1);
        expect($events[0]->domainEvent())->toBeInstanceOf(TestUserCreatedEvent::class);
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
