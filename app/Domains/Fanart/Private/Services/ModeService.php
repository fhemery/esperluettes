<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartMode;
use App\Domains\Fanart\Public\Providers\FanartServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

class ModeService
{
    public function __construct(
        private readonly SettingsPublicApi $settings,
        private readonly StoryPublicApi $stories,
        private readonly AuthPublicApi $auth,
        private readonly FanartistService $fanartists,
    ) {
    }

    /**
     * Whether the user may propose a fanart of this kind on the story. Authors
     * are not special-cased here: their path is the artwork ability.
     */
    public function canSubmit(int $userId, int $storyId, FanartKind $kind): bool
    {
        $slugs = array_map(fn ($r) => $r->slug, $this->auth->getRolesByUserIds([$userId])[$userId] ?? []);
        if (array_intersect($slugs, [Roles::USER_CONFIRMED, Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]) === []) {
            return false;
        }

        $story = $this->stories->getStory($storyId);
        if ($story === null || $story->visibility === 'private' || !$this->stories->canRead($userId, $storyId)) {
            return false;
        }

        return match ($this->effectiveMode($storyId, $kind)) {
            FanartMode::Yes => true,
            FanartMode::Designated => $this->fanartists->isFanartist($userId, $storyId),
            FanartMode::No => false,
        };
    }

    /** Most restrictive mode chosen by the story's authors for this kind; `no` when it has none. */
    public function effectiveMode(int $storyId, FanartKind $kind): FanartMode
    {
        $key = $kind === FanartKind::Image
            ? FanartServiceProvider::KEY_IMAGES_MODE
            : FanartServiceProvider::KEY_AUDIO_MODE;

        $modes = array_map(
            fn (int $authorId) => FanartMode::tryFrom((string) $this->settings->getValue(
                $authorId,
                FanartServiceProvider::TAB_STORIES,
                $key,
            )) ?? FanartMode::Designated,
            $this->stories->getAuthorIds($storyId),
        );

        return FanartMode::mostRestrictive(...$modes);
    }
}
