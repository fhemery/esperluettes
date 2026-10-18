<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Fanart\Private\Support\SubmitFanartData;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

/** The only writer of `fanarts`. */
class FanartService
{
    public function __construct(
        private readonly MediaPublicApi $media,
        private readonly EditorPublicApi $editor,
        private readonly StoryPublicApi $stories,
        private readonly FanartNotifier $notifier,
    ) {
    }

    public function submit(int $userId, int $storyId, SubmitFanartData $data): Fanart
    {
        $scope = 'fanart/' . $storyId;
        $isImage = $data->kind === FanartKind::Image;

        $path = $isImage
            ? $this->media->storePrivate($scope, $data->file, [400, 800])
            : $this->media->storePrivateFile($scope, $data->file);

        $fanart = Fanart::create([
            'story_id' => $storyId,
            'chapter_id' => $data->chapterId,
            'user_id' => $userId,
            'kind' => $data->kind,
            'status' => FanartStatus::Pending,
            'is_artwork' => false,
            'title' => $data->title,
            'description' => $this->cleanDescription($data->description),
            'file_path' => $path,
            'share_on_discord' => $isImage && $data->shareOnDiscord,
        ]);

        $this->notifier->fanartSubmitted($fanart, $userId);

        return $fanart;
    }

    /** Chapter ids of the story's published chapters. */
    public function publishedChapterOptions(int $storyId): array
    {
        $chapters = array_filter(
            $this->stories->getChaptersForStory($storyId),
            fn ($c) => $c->status === 'published',
        );

        return array_values(array_map(fn ($c) => ['id' => $c->id, 'name' => $c->title], $chapters));
    }

    private function cleanDescription(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $clean = trim($this->editor->sanitizeText($html, 'strict'));

        return $clean === '' ? null : $clean;
    }
}
