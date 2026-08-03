<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

use RuntimeException;

/**
 * A category that holds at least one entry — withdrawn or not — cannot be
 * deleted (decision #5). Carries the French refusal the admin is shown.
 */
final class CategoryNotEmptyException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('quote-contest::quote-contest.flash.category_not_empty'));
    }
}
