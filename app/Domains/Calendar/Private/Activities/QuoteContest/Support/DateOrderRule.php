<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Compares a date field to another field of the *same* request payload.
 *
 * The contest's timeline rule (`début activité ≤ fin soumissions ≤ début votes
 * ≤ fin activité`, assumption A4) spans the activity's own dates and the
 * plugin's, which the admin form submits together. A data-aware rule is
 * therefore enough — nothing is read from the database.
 *
 * It carries its own French message so a violation renders as a field error on
 * the activity form: `configRules()` has no hook to contribute custom messages
 * to `ActivityRequest`.
 *
 * Parsing failures are reported here too, which is why the field's rule list
 * bails: `required` speaks first, then this rule, and never Laravel's untranslated
 * `date` message.
 */
final class DateOrderRule implements DataAwareRule, ValidationRule
{
    /** @var array<string,mixed> */
    private array $data = [];

    private function __construct(
        private readonly string $otherField,
        private readonly bool $mustNotBeBefore,
        private readonly string $messageKey,
    ) {}

    /** The value must be greater than or equal to `$otherField`. */
    public static function notBefore(string $otherField, string $messageKey): self
    {
        return new self($otherField, true, $messageKey);
    }

    /** The value must be less than or equal to `$otherField`. */
    public static function notAfter(string $otherField, string $messageKey): self
    {
        return new self($otherField, false, $messageKey);
    }

    /** @param array<string,mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $date = $this->toDate($value);
        if ($date === null) {
            $fail(__('quote-contest::quote-contest.validation.invalid_date'));

            return;
        }

        // An unset bound is no bound: an activity may have no start or no end.
        $bound = $this->toDate(Arr::get($this->data, $this->otherField));
        if ($bound === null) {
            return;
        }

        $satisfied = $this->mustNotBeBefore
            ? $date->greaterThanOrEqualTo($bound)
            : $date->lessThanOrEqualTo($bound);

        if (! $satisfied) {
            $fail(__($this->messageKey));
        }
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
