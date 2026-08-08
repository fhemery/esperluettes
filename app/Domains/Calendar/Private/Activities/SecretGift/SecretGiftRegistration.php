<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift;

use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService;
use App\Domains\Calendar\Private\Support\DateOrderRule;
use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class SecretGiftRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'secret-gift';

    /** Where the config panel's own fields live in the activity form payload. */
    public const CONFIG_KEY = 'secret_gift';

    public function displayComponentKey(): string
    {
        return 'secret-gift::secret-gift-component';
    }

    public function configComponentKey(): ?string
    {
        return 'secret-gift::secret-gift-config';
    }

    /**
     * The mandatory registration deadline, bounded by the activity's own dates —
     * which travel in the same request payload, so the ordering rule needs no
     * database read.
     *
     * `bail` keeps a missing value on Laravel's translated `required` message and
     * hands every other failure to DateOrderRule, which carries a French message
     * of its own.
     */
    public function configRules(): array
    {
        return [
            self::CONFIG_KEY . '.registration_ends_at' => [
                'bail',
                'required',
                DateOrderRule::notBefore(
                    'preview_starts_at',
                    'secret-gift::secret-gift.validation.registration_ends_before_preview_start',
                ),
                DateOrderRule::notAfter(
                    'active_starts_at',
                    'secret-gift::secret-gift.validation.registration_ends_after_activity_start',
                ),
            ],
        ];
    }

    /**
     * Runs inside the activity's transaction: a Secret Gift never exists without
     * its settings row.
     */
    public function persistConfig(int $activityId, array $validated): void
    {
        $config = $validated[self::CONFIG_KEY] ?? null;
        if (! is_array($config)) {
            return;
        }

        app(SecretGiftConfigService::class)->saveSettings(
            $activityId,
            $config['registration_ends_at'],
        );
    }
}
