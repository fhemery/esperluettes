<?php

namespace App\Domains\Config\Public\Contracts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Immutable definition of a configuration parameter.
 * Registered by domains in their ServiceProvider boot().
 */
final class ConfigParameterDefinition
{
    /**
     * @param string $domain Owning domain (e.g., 'story', 'calendar')
     * @param string $key Unique identifier within domain (e.g., 'max_chapter_length')
     * @param ConfigParameterType $type Value type
     * @param mixed $default Default value when no override exists
     * @param array $constraints Type-specific validation constraints
     * @param ConfigParameterVisibility $visibility Admin visibility level
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $key,
        public readonly ConfigParameterType $type,
        public readonly mixed $default,
        public readonly array $constraints = [],
        public readonly ConfigParameterVisibility $visibility = ConfigParameterVisibility::TECH_ADMINS_ONLY,
    ) {}

    /**
     * Validate a value against this definition's type and constraints.
     *
     * @throws ValidationException
     */
    public function validate(mixed $value): void
    {
        $rules = $this->buildValidationRules();
        $validator = Validator::make(['value' => $value], ['value' => $rules]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Cast a stored string value to the correct PHP type.
     */
    public function cast(string $storedValue): mixed
    {
        return $this->type->cast($storedValue);
    }

    /**
     * Serialize a typed value to string for storage.
     */
    public function serialize(mixed $value): string
    {
        return $this->type->serialize($value);
    }

    /**
     * Get the full identifier for this parameter (domain.key).
     */
    public function fullKey(): string
    {
        return $this->domain . '.' . $this->key;
    }

    /**
     * Get the translation key for the parameter name.
     */
    public function nameTranslationKey(): string
    {
        return $this->domain . '::config.params.' . $this->key . '.name';
    }

    /**
     * Get the translation key for the parameter description.
     */
    public function descriptionTranslationKey(): string
    {
        return $this->domain . '::config.params.' . $this->key . '.description';
    }

    /**
     * Build Laravel validation rules based on type and constraints.
     */
    private function buildValidationRules(): array
    {
        $rules = ['required'];

        match ($this->type) {
            ConfigParameterType::INT, ConfigParameterType::TIME => $this->addIntRules($rules),
            ConfigParameterType::STRING => $this->addStringRules($rules),
            ConfigParameterType::BOOL => $this->addBoolRules($rules),
        };

        return $rules;
    }

    private function addIntRules(array &$rules): void
    {
        $rules[] = 'integer';

        if (isset($this->constraints['min'])) {
            $rules[] = 'min:' . $this->constraints['min'];
        }

        if (isset($this->constraints['max'])) {
            $rules[] = 'max:' . $this->constraints['max'];
        }
    }

    private function addStringRules(array &$rules): void
    {
        $rules[] = 'string';

        if (isset($this->constraints['min_length'])) {
            $rules[] = 'min:' . $this->constraints['min_length'];
        }

        if (isset($this->constraints['max_length'])) {
            $rules[] = 'max:' . $this->constraints['max_length'];
        }

        if (isset($this->constraints['pattern'])) {
            $rules[] = 'regex:' . $this->constraints['pattern'];
        }
    }

    private function addBoolRules(array &$rules): void
    {
        $rules[] = 'boolean';
    }
}
