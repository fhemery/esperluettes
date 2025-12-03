<?php

namespace App\Domains\Config\Public\Events\DTO;

use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;

final class ConfigParameterSnapshot
{
    public function __construct(
        public readonly string $domain,
        public readonly string $key,
        public readonly string $type,
        public readonly mixed $value,
        public readonly mixed $previousValue,
    ) {}

    public static function fromDefinitionAndValues(
        ConfigParameterDefinition $definition,
        mixed $newValue,
        mixed $previousValue,
    ): self {
        return new self(
            domain: $definition->domain,
            key: $definition->key,
            type: $definition->type->value,
            value: $newValue,
            previousValue: $previousValue,
        );
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            domain: $payload['domain'],
            key: $payload['key'],
            type: $payload['type'],
            value: $payload['value'],
            previousValue: $payload['previousValue'],
        );
    }

    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'key' => $this->key,
            'type' => $this->type,
            'value' => $this->value,
            'previousValue' => $this->previousValue,
        ];
    }
}
