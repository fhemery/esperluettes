<?php

namespace App\Domains\Shared\Validation;

use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class CustomValidators
{
    public static function register(): void
    {
        // maxstripped:<max>
        Validator::extend('maxstripped', function ($attribute, $value, $parameters) {
            if ($value === null) {
                return true;
            }
            $max = isset($parameters[0]) ? (int) $parameters[0] : 0;
            if ($max <= 0) {
                return true; // if misconfigured, do not fail hard
            }
            $profile = $parameters[1] ?? 'strict';
            $clean = Purifier::clean((string) $value, $profile);
            $plain = str_replace("\n\n", "\n", trim(strip_tags($clean)));
            return mb_strlen($plain) <= $max;
        });

        Validator::replacer('maxstripped', function ($message, $attribute, $rule, $parameters) {
            $max = isset($parameters[0]) ? (int) $parameters[0] : 0;
            return str_replace(':max', (string) $max, $message);
        });

        // minstripped:<min>
        Validator::extend('minstripped', function ($attribute, $value, $parameters) {
            if ($value === null) {
                return true;
            }
            $min = isset($parameters[0]) ? (int) $parameters[0] : 0;
            if ($min <= 0) {
                return true; // if misconfigured, do not fail hard
            }
            $profile = $parameters[1] ?? 'strict';
            $clean = Purifier::clean((string) $value, $profile);
            $plain = str_replace("\n\n", "\n", trim(strip_tags($clean)));
            // Do not count newline separators that purifier may inject between block tags
            $plainNoNewlines = str_replace(["\n", "\r"], '', $plain);
            return mb_strlen($plainNoNewlines) >= $min;
        });

        Validator::replacer('minstripped', function ($message, $attribute, $rule, $parameters) {
            $min = isset($parameters[0]) ? (int) $parameters[0] : 0;
            return str_replace(':min', (string) $min, $message);
        });

        // required_trimmed: value must be non-empty after trim
        Validator::extend('required_trimmed', function ($attribute, $value, $parameters) {
            if ($value === null) {
                return false;
            }
            if (is_string($value)) {
                return trim($value) !== '';
            }
            // For non-strings, cast to string to keep semantics similar to HTML form inputs
            return trim((string) $value) !== '';
        });
    }
}
