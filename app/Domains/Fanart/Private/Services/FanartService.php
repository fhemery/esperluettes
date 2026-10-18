<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Public\Events\FanartDeletedByModeration;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Fanart\Private\Support\SubmitFanartData;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Fanart\Private\Support\UpdateArtworkData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The only writer of `fanarts`. */
class FanartService
{
    public function __construct(
        private readonly MediaPublicApi $media,
        private readonly EditorPublicApi $editor,
        private readonly StoryPublicApi $stories,
        private readonly FanartNotifier $notifier,
        private readonly EventBus $eventBus,
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

    /**
     * Publish an author's artwork at the cost of one chapter credit of the
     * uploader. Spend and row creation are one transaction; no notification.
     */
    public function uploadArtwork(int $userId, int $storyId, SubmitFanartData $data): Fanart
    {
        return DB::transaction(function () use ($userId, $storyId, $data) {
            if (!$this->stories->spendChapterCredit($userId)) {
                throw ValidationException::withMessages(['credit' => __('fanart::ui.validation.no_credit')]);
            }

            $fanart = Fanart::create([
                'story_id' => $storyId,
                'chapter_id' => $data->chapterId,
                'user_id' => $userId,
                'kind' => $data->kind,
                'status' => FanartStatus::Published,
                'is_artwork' => true,
                'title' => $data->title,
                'description' => $this->cleanDescription($data->description),
                'file_path' => $this->storeFile($storyId, $data->kind, $data->file),
                'share_on_discord' => $data->kind === FanartKind::Image && $data->shareOnDiscord,
                'published_at' => now(),
            ]);

            if ($fanart->kind === FanartKind::Audio && $fanart->chapter_id !== null) {
                $this->replacePublishedAudio($fanart->chapter_id, $userId, $fanart->id);
            }

            return $fanart;
        });
    }

    /** Free edit of an artwork. The previous file is left for `media:gc`. */
    public function updateArtwork(Fanart $fanart, int $actorId, UpdateArtworkData $data): Fanart
    {
        return DB::transaction(function () use ($fanart, $actorId, $data) {
            $attributes = [
                'title' => $data->title,
                'description' => $this->cleanDescription($data->description),
                'chapter_id' => $data->chapterId,
            ];

            if ($data->file !== null) {
                $attributes['file_path'] = $this->storeFile($fanart->story_id, $fanart->kind, $data->file);
            }

            $fanart->update($attributes);

            if ($fanart->kind === FanartKind::Audio && $fanart->chapter_id !== null) {
                $this->replacePublishedAudio($fanart->chapter_id, $actorId, $fanart->id);
            }

            return $fanart;
        });
    }

    private function storeFile(int $storyId, FanartKind $kind, UploadedFile $file): string
    {
        $scope = 'fanart/' . $storyId;

        return $kind === FanartKind::Image
            ? $this->media->storePrivate($scope, $file, [400, 800])
            : $this->media->storePrivateFile($scope, $file);
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

    /** The submitter withdraws or deletes their own fanart: nobody is told. */
    public function deleteByOwner(Fanart $fanart): void
    {
        $fanart->forceDelete();
    }

    public function deleteByAuthor(Fanart $fanart, int $actorId): void
    {
        $fanart->forceDelete();

        $this->notifier->fanartDeleted($fanart, $actorId);
    }

    public function deleteByModerator(Fanart $fanart, int $moderatorId): void
    {
        $fanart->forceDelete();

        $this->notifier->fanartDeletedByModeration($fanart, $moderatorId);

        $this->eventBus->emit(new FanartDeletedByModeration(
            fanartId: $fanart->id,
            storyId: $fanart->story_id,
            userId: $fanart->user_id,
            moderatorId: $moderatorId,
        ));
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
