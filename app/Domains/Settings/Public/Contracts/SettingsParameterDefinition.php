<?php

namespace App\Domains\Settings\Public\Contracts;

use App\Domains\Shared\Contracts\ParameterType;

final class SettingsParameterDefinition
{
    /**
     * @param string $tabId              Reference to parent tab
     * @param string $sectionId          Reference to parent section
     * @param string $key                Unique key within tab (used for storage)
     * @param ParameterType $type        Value type
     * @param mixed $default             Default value when no override exists
     * @param int $order                 Display order within section
     * @param string $nameKey            Full translation key for parameter name
     * @param string|null $descriptionKey Full translation key for description
     * @param array $constraints         Type-specific constraints (min, max, options, etc.)
     * @param array $roles               Roles required to see this parameter (empty = all authenticated users)
     */
    public function __construct(
        public readonly string $tabId,
        public readonly string $sectionId,
        public readonly string $key,
        public readonly ParameterType $type,
        public readonly mixed $default,
        public readonly int $order,
        public readonly string $nameKey,
        public readonly ?string $descriptionKey = null,
        public readonly array $constraints = [],
        public readonly array $roles = [],
    ) {}

    /**
     * Cast a stored string value to the correct PHP type.
     */
    public function cast(mixed $value): mixed
    {
        return $this->type->cast($value);
    }

    /**
     * Serialize a typed value to string for storage.
     */
    public function serialize(mixed $value): string
    {
        return $this->type->serialize($value);
    }

    /**
     * Validate a value against the parameter's constraints.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(mixed $value): void
    {
        $rules = $this->buildValidationRules();
        if (empty($rules)) {
            return;
        }

        validator(['value' => $value], ['value' => $rules])->validate();
    }

    /**
     * Build Laravel validation rules based on type and constraints.
     */
    private function buildValidationRules(): array
    {
        $rules = [];

        switch ($this->type) {
            case ParameterType::INT:
            case ParameterType::RANGE:
                $rules[] = 'integer';
                if (isset($this->constraints['min'])) {
                    $rules[] = 'min:'.$this->constraints['min'];
                }
                if (isset($this->constraints['max'])) {
                    $rules[] = 'max:'.$this->constraints['max'];
                }
                break;

            case ParameterType::STRING:
                $rules[] = 'string';
                if (isset($this->constraints['min_length'])) {
                    $rules[] = 'min:'.$this->constraints['min_length'];
                }
                if (isset($this->constraints['max_length'])) {
                    $rules[] = 'max:'.$this->constraints['max_length'];
                }
                if (isset($this->constraints['pattern'])) {
                    $rules[] = 'regex:'.$this->constraints['pattern'];
                }
                break;

            case ParameterType::BOOL:
                $rules[] = 'boolean';
                break;

            case ParameterType::TIME:
                $rules[] = 'integer';
                if (isset($this->constraints['min'])) {
                    $rules[] = 'min:'.$this->constraints['min'];
                }
                if (isset($this->constraints['max'])) {
                    $rules[] = 'max:'.$this->constraints['max'];
                }
                break;

            case ParameterType::ENUM:
                if (isset($this->constraints['options'])) {
                    $rules[] = 'in:'.implode(',', array_keys($this->constraints['options']));
                }
                break;

            case ParameterType::MULTI_SELECT:
                $rules[] = 'array';
                if (isset($this->constraints['options'])) {
                    $rules[] = function ($attribute, $value, $fail) {
                        $validOptions = array_keys($this->constraints['options']);
                        foreach ($value as $item) {
                            if (!in_array($item, $validOptions, true)) {
                                $fail("The selected $attribute contains an invalid option.");
                            }
                        }
                    };
                }
                break;
        }

        return $rules;
    }
}
