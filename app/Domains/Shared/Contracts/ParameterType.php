<?php

namespace App\Domains\Shared\Contracts;

/**
 * Shared parameter type enum used by Config (admin) and Settings (user) domains.
 */
enum ParameterType: string
{
    case INT = 'int';
    case STRING = 'string';
    case BOOL = 'bool';
    case TIME = 'time';        // Stores seconds, displayed as number + unit selector
    case ENUM = 'enum';        // Single selection from predefined options
    case RANGE = 'range';      // Numeric with slider UI
    case MULTI_SELECT = 'multi'; // Multiple selection from predefined options

    /**
     * Cast a stored string value to the correct PHP type.
     */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::INT, self::TIME, self::RANGE => (int) $value,
            self::STRING, self::ENUM => (string) $value,
            self::BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::MULTI_SELECT => is_array($value) ? $value : json_decode($value, true) ?? [],
        };
    }

    /**
     * Serialize a typed value to string for storage.
     */
    public function serialize(mixed $value): string
    {
        return match ($this) {
            self::BOOL => $value ? '1' : '0',
            self::MULTI_SELECT => json_encode($value),
            default => (string) $value,
        };
    }
}
