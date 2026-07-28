<?php

namespace App\Domains\Quote\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Public\Providers\QuoteServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

class QuotePolicy
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
        private readonly AuthPublicApi $authApi,
        private readonly SettingsPublicApi $settingsApi,
    ) {
    }

    public function canQuote(int $storyId, int $userId): bool
    {
        $roles = $this->authApi->getRolesByUserIds([$userId]);
        $slugs = array_map(fn($r) => $r->slug, $roles[$userId] ?? []);

        if (!in_array(Roles::USER_CONFIRMED, $slugs)) {
            return false;
        }

        if ($this->storyApi->isAuthor($userId, $storyId)) {
            return false;
        }

        $withAccess = $this->storyApi->filterUsersWithAccessToStory([$userId], $storyId);

        return in_array($userId, $withAccess, true);
    }

    public function canViewQuoteBook(int $profileUserId, ?int $viewerId): bool
    {
        if ($viewerId === $profileUserId) {
            return true;
        }

        if ($viewerId === null) {
            return false;
        }

        $isHidden = (bool) $this->settingsApi->getValue(
            $profileUserId,
            QuoteServiceProvider::TAB_PROFILE,
            QuoteServiceProvider::KEY_HIDE_QUOTES_TAB,
        );

        if ($isHidden) {
            return false;
        }

        $roles = $this->authApi->getRolesByUserIds([$viewerId]);
        $slugs = array_map(fn($r) => $r->slug, $roles[$viewerId] ?? []);

        return in_array(Roles::USER_CONFIRMED, $slugs);
    }

    public function canEditOrDelete(Quote $quote, int $userId): bool
    {
        return $quote->user_id === $userId;
    }
}
