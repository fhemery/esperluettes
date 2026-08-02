<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest;

use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestConfigService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\DateOrderRule;
use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class QuoteContestRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'quote-contest';

    /** Where the config panel's own fields live in the activity form payload. */
    public const CONFIG_KEY = 'quote_contest';

    public function displayComponentKey(): string
    {
        return 'quote-contest::quote-contest-component';
    }

    public function configComponentKey(): ?string
    {
        return 'quote-contest::quote-contest-config';
    }

    /**
     * The two contest dates, bounded by the activity's own two dates — which
     * travel in the same request payload, so the ordering rule of assumption A4
     * needs no database read.
     *
     * `bail` keeps a missing value on Laravel's translated `required` message
     * and hands every other failure to DateOrderRule, which carries a French
     * message of its own.
     */
    public function configRules(): array
    {
        return [
            self::CONFIG_KEY . '.submissions_end_at' => [
                'bail',
                'required',
                DateOrderRule::notBefore(
                    'active_starts_at',
                    'quote-contest::quote-contest.validation.submissions_end_before_activity_start',
                ),
            ],
            self::CONFIG_KEY . '.votes_start_at' => [
                'bail',
                'required',
                DateOrderRule::notBefore(
                    self::CONFIG_KEY . '.submissions_end_at',
                    'quote-contest::quote-contest.validation.votes_start_before_submissions_end',
                ),
                DateOrderRule::notAfter(
                    'active_ends_at',
                    'quote-contest::quote-contest.validation.votes_start_after_activity_end',
                ),
            ],
        ];
    }

    /**
     * Runs inside the activity's transaction: a contest activity never exists
     * without its settings row.
     */
    public function persistConfig(int $activityId, array $validated): void
    {
        $config = $validated[self::CONFIG_KEY] ?? null;
        if (! is_array($config)) {
            return;
        }

        app(QuoteContestConfigService::class)->saveSettings(
            $activityId,
            $config['submissions_end_at'],
            $config['votes_start_at'],
        );
    }
}
