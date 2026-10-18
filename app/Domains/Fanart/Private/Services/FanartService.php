<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Fanart\Private\Support\SubmitFanartData;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Support\Facades\DB;

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

    public function find(int $id): ?Fanart
    {
        return Fanart::find($id);
    }

    /**
     * Publish a pending fanart. The comment lives only in the notification.
     * An accepted audio replaces the chapter's previous published one.
     */
    public function accept(Fanart $fanart, int $actorId, string $comment): void
    {
        DB::transaction(function () use ($fanart, $actorId) {
            $fanart->update(['status' => FanartStatus::Published, 'published_at' => now()]);

            if ($fanart->kind === FanartKind::Audio && $fanart->chapter_id !== null) {
                $this->replacePublishedAudio($fanart->chapter_id, $actorId, $fanart->id);
            }
        });

        $this->notifier->fanartAccepted($fanart, $actorId, $comment);
    }

    public function decline(Fanart $fanart, int $actorId, ?string $comment): void
    {
        $fanart->forceDelete();

        $this->notifier->fanartDeclined($fanart, $actorId, $comment);
    }

    /**
     * Hard-delete the chapter's published audio (other than $exceptId) and tell
     * its submitter. Locks the row so concurrent accepts cannot both survive.
     */
    public function replacePublishedAudio(int $chapterId, int $actorId, int $exceptId): void
    {
        $previous = Fanart::query()
            ->where('chapter_id', $chapterId)
            ->where('kind', FanartKind::Audio)
            ->where('status', FanartStatus::Published)
            ->where('id', '!=', $exceptId)
            ->lockForUpdate()
            ->get();

        foreach ($previous as $audio) {
            $audio->forceDelete();
            $this->notifier->fanartAudioReplaced($audio, $actorId);
        }
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
