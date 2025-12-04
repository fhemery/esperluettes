<?php

namespace App\Domains\Auth\Public\Support;

/**
 * Centralized config parameter keys for Auth domain.
 * Avoids hardcoding domain and key strings throughout the codebase.
 */
final class AuthConfigKeys
{
    public const DOMAIN = 'auth';

    public const REQUIRE_ACTIVATION_CODE = 'require_activation_code';
    public const NON_CONFIRMED_COMMENT_THRESHOLD = 'non_confirmed_comment_threshold';
    public const NON_CONFIRMED_TIMESPAN = 'non_confirmed_timespan';
}
