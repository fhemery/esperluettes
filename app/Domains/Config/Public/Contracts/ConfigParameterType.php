<?php

namespace App\Domains\Config\Public\Contracts;

enum ConfigParameterType: string
{
    case INT = 'int';
    case STRING = 'string';
    case BOOL = 'bool';
    case TIME = 'time'; // Stores seconds, displayed as number + unit selector

    /**
     * Cast a stored string value to the correct PHP type.
     */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::INT, self::TIME => (int) $value,
            self::STRING => (string) $value,
            self::BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        };
    }

    /**
     * Serialize a typed value to string for storage.
     */
    public function serialize(mixed $value): string
    {
        return match ($this) {
            self::BOOL => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
