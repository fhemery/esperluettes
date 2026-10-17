<?php

namespace App\Domains\Fanart\Private\Services;

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
    ) {
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
            )) ?? ($kind === FanartKind::Image ? FanartMode::Yes : FanartMode::Designated),
            $this->stories->getAuthorIds($storyId),
        );

        return FanartMode::mostRestrictive(...$modes);
    }
}
