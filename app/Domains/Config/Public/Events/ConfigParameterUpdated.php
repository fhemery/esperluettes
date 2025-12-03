<?php

namespace App\Domains\Config\Public\Events;

use App\Domains\Config\Public\Events\DTO\ConfigParameterSnapshot;
use App\Domains\Events\Public\Contracts\DomainEvent;

final class ConfigParameterUpdated implements DomainEvent
{
    public function __construct(
        public readonly ConfigParameterSnapshot $parameter,
    ) {}

    public static function name(): string
    {
        return 'Config.ConfigParameterUpdated';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'parameter' => $this->parameter->toArray(),
        ];
    }

    public function summary(): string
    {
        return __('config::config_parameter.events.updated', [
            'domain' => $this->parameter->domain,
            'key' => $this->parameter->key,
            'value' => $this->parameter->value,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            parameter: ConfigParameterSnapshot::fromPayload($payload['parameter']),
        );
    }
}
