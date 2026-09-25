<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Support\Facades\Auth;

/**
 * Reads the reader's two story preferences (« Histoires » settings tab).
 *
 * Bound as a container singleton by StoryServiceProvider: the memoization below
 * is what lets a library grid of twelve cards cost a single Settings lookup.
 */
class StoryPreferenceService
{
    /** @var array<string, bool> keyed by "{userId}:{key}" */
    private array $memo = [];

    public function __construct(private SettingsPublicApi $settings) {}

    public function hidesTriggerWarnings(?int $userId = null): bool
    {
        return $this->resolve(StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS, $userId);
    }

    public function hidesStoriesWithTriggerWarnings(?int $userId = null): bool
    {
        return $this->resolve(StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW, $userId);
    }

    private function resolve(string $key, ?int $userId): bool
    {
        $userId ??= Auth::id();

        // Guests never persist preferences: they always get the defaults.
        if ($userId === null) {
            return false;
        }

        return $this->memo["{$userId}:{$key}"] ??= (bool) $this->settings->getValue(
            $userId,
            StoryServiceProvider::TAB_STORIES,
            $key,
        );
    }
}
