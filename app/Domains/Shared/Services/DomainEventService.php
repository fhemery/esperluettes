<?php

namespace App\Domains\Shared\Services;

use App\Domains\Shared\Models\DomainEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class DomainEventService
{
    /**
     * Record a domain event for auditing.
     */
    public function record(string $eventName, object $event, array $context = []): void
    {
        if (!config('shared.event_auditing_enabled', true)) {
            return;
        }

        $payload = $this->extractPayload($event);
        $meta = $context['meta'] ?? [];

        DomainEvent::create([
            'name' => $eventName,
            'payload' => $payload,
            'triggered_by_user_id' => $context['user_id'] ?? Auth::id(),
            'context_ip' => $context['ip'] ?? Request::ip(),
            'context_user_agent' => $context['user_agent'] ?? Request::userAgent(),
            'context_url' => $context['url'] ?? Request::fullUrl(),
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Basic query helper for admin listing.
     */
    public function list(array $filters = [])
    {
        $q = DomainEvent::query()->latest('occurred_at');

        if ($name = Arr::get($filters, 'name')) {
            $q->where('name', 'like', $name.'%');
        }
        if ($userId = Arr::get($filters, 'user_id')) {
            $q->where('triggered_by_user_id', $userId);
        }
        if ($from = Arr::get($filters, 'from')) {
            $q->where('occurred_at', '>=', $from);
        }
        if ($to = Arr::get($filters, 'to')) {
            $q->where('occurred_at', '<=', $to);
        }

        return $q->paginate(50);
    }

    private function extractPayload(object $event): array
    {
        $raw = get_object_vars($event);
        $out = [];
        foreach ($raw as $key => $value) {
            $out[$key] = $this->normalize($value);
        }
        return $out;
    }

    private function normalize($value)
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            return array_map(fn($v) => $this->normalize($v), $value);
        }
        // Fallback to string representation
        return (string) $value;
    }
}
