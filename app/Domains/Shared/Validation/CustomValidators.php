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
            $plain = trim(strip_tags($clean));
            return mb_strlen($plain) <= $max;
        });

        Validator::replacer('maxstripped', function ($message, $attribute, $rule, $parameters) {
            $max = isset($parameters[0]) ? (int) $parameters[0] : 0;
            return str_replace(':max', (string) $max, $message);
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
