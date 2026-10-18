<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Public\Events\FanartDeletedByModeration;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Fanart\Private\Support\SubmitFanartData;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Support\FanartViewer;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;
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
        private readonly FanartPolicy $policy,
        private readonly ModeService $modes,
        private readonly ProfilePublicApi $profiles,
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
     * Published, opted-in images of public stories (and published chapters) that the Discord bot
     * may relay. Readability is the guest's, so community and private stories never qualify.
     */
    private function discordEligible(?int $onlyFanartId = null): Builder
    {
        $query = Fanart::query()
            ->published()
            ->where('kind', FanartKind::Image)
            ->where('share_on_discord', true);
        if ($onlyFanartId !== null) {
            $query->whereKey($onlyFanartId);
        }

        $storyIds = (clone $query)->distinct()->pluck('story_id')->all();
        if ($storyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $readable = $this->stories->filterReadableStoryIds(null, $storyIds);
        $publishedChapterIds = [];
        foreach ($this->stories->getChaptersForStories($readable) as $chapters) {
            array_push($publishedChapterIds, ...$this->publishedChapterIds($chapters));
        }

        return $query->whereIn('story_id', $readable)
            ->where(fn (Builder $chapter) => $chapter->whereNull('chapter_id')
                ->orWhereIn('chapter_id', $publishedChapterIds));
    }

    public function isDiscordEligible(int $fanartId): bool
    {
        return $this->discordEligible($fanartId)->exists();
    }

    /**
     * One page of fanarts waiting for the bot, oldest first.
     *
     * @return array{paginator: LengthAwarePaginator, items: array<int, array<string, mixed>>}
     */
    public function discordPending(int $perPage, int $page): array
    {
        $paginator = $this->discordEligible()
            ->whereNull('discord_sent_at')
            ->orderBy('published_at')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $fanarts = $paginator->items();
        $storyIds = array_values(array_unique(array_map(fn (Fanart $f) => $f->story_id, $fanarts)));
        $chapterIds = array_values(array_filter(array_map(fn (Fanart $f) => $f->chapter_id, $fanarts)));
        $stories = $storyIds === [] ? [] : $this->stories->getStoriesByIds($storyIds);
        $chapters = $chapterIds === [] ? [] : $this->stories->getChaptersByIds($chapterIds);
        $artists = $this->artistNames(array_values(array_unique(array_map(fn (Fanart $f) => $f->user_id, $fanarts))));

        $items = [];
        foreach ($fanarts as $fanart) {
            $story = $stories[$fanart->story_id] ?? null;
            if ($story === null) {
                continue;
            }
            $item = [
                'id' => $fanart->id,
                'title' => $fanart->title,
                'artistName' => $artists[$fanart->user_id] ?? '',
                'storyTitle' => $story->title,
                'storyUrl' => route('stories.show', $story->slug),
                'imageUrl' => URL::temporarySignedRoute('fanart.discord-image', now()->addDay(), ['fanartId' => $fanart->id]),
            ];
            if ($fanart->chapter_id !== null && isset($chapters[$fanart->chapter_id])) {
                $item['chapterTitle'] = $chapters[$fanart->chapter_id]->title;
            }
            $items[] = $item;
        }

        return ['paginator' => $paginator, 'items' => $items];
    }

    /** @param int[] $ids @return int number of fanarts newly marked */
    public function markDiscordSent(array $ids): int
    {
        return Fanart::query()
            ->whereIn('id', $ids)
            ->whereNull('discord_sent_at')
            ->update(['discord_sent_at' => now()]);
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

    /**
     * Story-page playlist: the published audio the viewer may hear, in chapter order
     * (readers: published chapters only). Computed once per request and story.
     *
     * @return array<int, array{chapterId: int, chapterSlug: string, fanartId: int}>
     */
    public function audioPlaylist(int $storyId, int $userId): array
    {
        $key = "fanart.audio.{$storyId}.{$userId}";
        $attributes = request()->attributes;
        if ($attributes->has($key)) {
            return $attributes->get($key);
        }

        $chapters = $this->stories->getChaptersForStory($storyId);
        $audios = Fanart::query()
            ->visibleTo($this->viewerFor($userId, $storyId, $chapters))
            ->published()
            ->where('story_id', $storyId)
            ->where('kind', FanartKind::Audio)
            ->whereNotNull('chapter_id')
            ->get()
            ->keyBy('chapter_id');

        $playlist = [];
        foreach ($chapters as $chapter) {
            if ($audio = $audios->get($chapter->id)) {
                $playlist[] = ['chapterId' => $chapter->id, 'chapterSlug' => $chapter->slug, 'fanartId' => $audio->id];
            }
        }
        $attributes->set($key, $playlist);

        return $playlist;
    }

    /** What the viewer may list on this story, resolved once (callers pass it to Fanart::visibleTo). */
    public function viewerFor(int $userId, int $storyId, ?array $chapters = null): FanartViewer
    {
        $seesAll = $this->policy->seesEverything($userId, $storyId);
        $published = [];
        if (!$seesAll) {
            $chapters ??= $this->stories->getChaptersForStory($storyId);
            $published = $this->publishedChapterIds($chapters);
        }

        return new FanartViewer($userId, $seesAll, $published);
    }

    /**
     * Header data of the Fanarts tab: one group per chapter holding something the viewer may see
     * (story order) plus « Toute l'œuvre », the pending queue for authors / staff, and for authors
     * the effective reception modes.
     *
     * @return array{groups: array<int, array{key: string, title: ?string, count: int, collapsed: bool}>,
     *     pending: Collection<int, Fanart>, isAuthor: bool, modes: ?array}
     */
    public function tabSummary(int $storyId, int $userId): array
    {
        $chapters = $this->stories->getChaptersForStory($storyId);
        $viewer = $this->viewerFor($userId, $storyId, $chapters);
        $isAuthor = $this->stories->isAuthor($userId, $storyId);

        $counts = Fanart::query()
            ->visibleTo($viewer)
            ->published()
            ->where('story_id', $storyId)
            ->selectRaw('chapter_id, count(*) as total')
            ->groupBy('chapter_id')
            ->pluck('total', 'chapter_id')
            ->all();

        $read = $isAuthor ? [] : $this->stories->getReadChapterIds($userId, $storyId);

        $groups = [];
        if (($counts[''] ?? 0) > 0) {
            $groups[] = ['key' => 'work', 'title' => null, 'count' => (int) $counts[''], 'collapsed' => false];
        }
        foreach ($chapters as $chapter) {
            $count = (int) ($counts[$chapter->id] ?? 0);
            if ($count > 0) {
                $groups[] = [
                    'key' => (string) $chapter->id,
                    'title' => $chapter->title,
                    'count' => $count,
                    'collapsed' => !$isAuthor && !in_array($chapter->id, $read, true),
                ];
            }
        }

        return [
            'groups' => $groups,
            'pending' => $viewer->seesAll ? $this->pendingFor($storyId) : new Collection(),
            'isAuthor' => $isAuthor,
            'modes' => $isAuthor ? [
                'images' => $this->modes->effectiveMode($storyId, FanartKind::Image)->value,
                'audio' => $this->modes->effectiveMode($storyId, FanartKind::Audio)->value,
                'coauthors' => count($this->stories->getAuthorIds($storyId)) > 1,
            ] : null,
        ];
    }

    /** The story's pending fanarts, oldest first. Callers must already know the viewer sees everything. */
    public function pendingFor(int $storyId): Collection
    {
        return Fanart::query()
            ->where('story_id', $storyId)
            ->where('status', FanartStatus::Pending)
            ->orderBy('id')
            ->get();
    }

    /**
     * Published fanarts of one group, or null when the viewer may not see the group at all
     * (chapter of another story, or unpublished and the viewer is a reader).
     *
     * @return array{fanarts: Collection<int, Fanart>, artists: array<int, string>}|null
     */
    public function group(int $storyId, ?int $chapterId, int $userId): ?array
    {
        $chapters = $this->stories->getChaptersForStory($storyId);
        $viewer = $this->viewerFor($userId, $storyId, $chapters);

        if ($chapterId !== null) {
            $chapter = collect($chapters)->firstWhere('id', $chapterId);
            if ($chapter === null || (!$viewer->seesAll && $chapter->status !== 'published')) {
                return null;
            }
        }

        $fanarts = Fanart::query()
            ->visibleTo($viewer)
            ->published()
            ->where('story_id', $storyId)
            ->where('chapter_id', $chapterId)
            ->orderBy('kind')
            ->orderBy('id')
            ->get();

        $audioUserIds = $fanarts->where('kind', FanartKind::Audio)->pluck('user_id')->unique()->all();

        return ['fanarts' => $fanarts, 'artists' => $this->artistNames($audioUserIds)];
    }

    /**
     * Profile tab: the owner's fanarts the viewer may see, one entry per story (alphabetical) in
     * each kind's section. The viewer's readable stories come from a single `filterReadableStoryIds` call.
     *
     * @return array<string, array<int, array{storyId: int, title: string, slug: string, count: int}>>
     *     keyed by FanartKind value
     */
    public function profileStories(int $ownerId, int $viewerId): array
    {
        $fanarts = $this->profileFanarts($ownerId, $viewerId);
        $stories = $fanarts->isEmpty() ? [] : $this->stories->getStoriesByIds($fanarts->pluck('story_id')->unique()->all());

        $sections = [];
        foreach (FanartKind::cases() as $kind) {
            $entries = [];
            foreach ($fanarts->where('kind', $kind)->countBy('story_id') as $storyId => $count) {
                if (isset($stories[$storyId])) {
                    $entries[] = [
                        'storyId' => (int) $storyId,
                        'title' => $stories[$storyId]->title,
                        'slug' => $stories[$storyId]->slug,
                        'count' => $count,
                    ];
                }
            }
            usort($entries, fn ($a, $b) => strcasecmp($a['title'], $b['title']));
            $sections[$kind->value] = $entries;
        }

        return $sections;
    }

    /**
     * Profile tab, one story and kind: « Toute l'œuvre » then chapters in story order, each with its
     * fanarts. Null when the viewer may not read the story. A
     * chapter the viewer has not read is collapsed (never for authors of the story).
     *
     * @return array{groups: array<int, array{key: string, title: ?string, collapsed: bool, fanarts: Collection<int, Fanart>}>}|null
     */
    public function profileStoryGroups(int $ownerId, int $viewerId, int $storyId, FanartKind $kind): ?array
    {
        $readable = $this->stories->filterReadableStoryIds($viewerId, [$storyId]);
        if ($readable === []) {
            return null;
        }

        $fanarts = $this->profileFanarts($ownerId, $viewerId, [$storyId], $readable)->where('kind', $kind);
        $byChapter = $fanarts->groupBy('chapter_id');
        $isAuthor = in_array($viewerId, $this->stories->getAuthorIdsByStoryIds([$storyId])[$storyId] ?? [], true);
        $read = $isAuthor ? [] : ($this->stories->getReadChapterIdsByStoryIds($viewerId, [$storyId])[$storyId] ?? []);

        $groups = [];
        if ($byChapter->has('')) {
            $groups[] = ['key' => 'work', 'title' => null, 'collapsed' => false, 'fanarts' => $byChapter->get('')];
        }
        foreach ($this->stories->getChaptersForStory($storyId) as $chapter) {
            if ($byChapter->has($chapter->id)) {
                $groups[] = [
                    'key' => (string) $chapter->id,
                    'title' => $chapter->title,
                    'collapsed' => !$isAuthor && !in_array($chapter->id, $read, true),
                    'fanarts' => $byChapter->get($chapter->id),
                ];
            }
        }

        return ['groups' => $groups];
    }

    /**
     * The owner's fanarts of a kind that the viewer may see: only on stories the viewer can read
     * (one readability call); pending ones for the owner and staff; published ones in published
     * chapters, unless the viewer is staff or an author of the story.
     *
     * @param int[]|null $onlyStoryIds Restrict to these stories (their readability is then already known:
     *     pass the readable subset as $readable).
     * @param int[]|null $readable
     * @return Collection<int, Fanart>
     */
    private function profileFanarts(int $ownerId, int $viewerId, ?array $onlyStoryIds = null, ?array $readable = null): Collection
    {
        $fanarts = Fanart::query()
            ->where('user_id', $ownerId)
            ->when($onlyStoryIds !== null, fn ($q) => $q->whereIn('story_id', $onlyStoryIds))
            ->orderBy('id')
            ->get();
        if ($fanarts->isEmpty()) {
            return $fanarts;
        }

        $readable ??= $this->stories->filterReadableStoryIds($viewerId, $fanarts->pluck('story_id')->unique()->values()->all());
        $fanarts = $fanarts->whereIn('story_id', $readable);
        if ($fanarts->isEmpty()) {
            return $fanarts->values();
        }

        $isStaff = $this->policy->isStaff($viewerId);
        $seesPending = $isStaff || $viewerId === $ownerId;
        $authors = $isStaff ? [] : $this->stories->getAuthorIdsByStoryIds($readable);
        $published = [];
        foreach ($this->stories->getChaptersForStories($readable) as $chapters) {
            foreach ($chapters as $chapter) {
                if ($chapter->status === 'published') {
                    $published[$chapter->id] = true;
                }
            }
        }

        return $fanarts->filter(function (Fanart $fanart) use ($seesPending, $isStaff, $authors, $published, $viewerId) {
            if ($fanart->status === FanartStatus::Pending) {
                return $seesPending;
            }

            return $isStaff
                || in_array($viewerId, $authors[$fanart->story_id] ?? [], true)
                || $fanart->chapter_id === null
                || isset($published[$fanart->chapter_id]);
        })->values();
    }

    /**
     * Everything the viewer fragment shows around the file itself.
     *
     * @return array{artist: ?ProfileDto, chapterTitle: ?string, description: ?string}
     */
    public function viewerDetails(Fanart $fanart): array
    {
        $chapter = $fanart->chapter_id !== null
            ? ($this->stories->getChaptersByIds([$fanart->chapter_id])[$fanart->chapter_id] ?? null)
            : null;

        return [
            'artist' => $this->profiles->getPublicProfiles([$fanart->user_id])[$fanart->user_id] ?? null,
            'chapterTitle' => $chapter?->title,
            'description' => $fanart->description === null
                ? null
                : $this->cleanDescription($fanart->description),
        ];
    }

    /** @param array<int, \App\Domains\Story\Public\Contracts\StoryChapterDto> $chapters @return int[] */
    private function publishedChapterIds(array $chapters): array
    {
        return array_values(array_map(
            fn ($c) => $c->id,
            array_filter($chapters, fn ($c) => $c->status === 'published'),
        ));
    }

    /** @param int[] $userIds @return array<int, string> */
    private function artistNames(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return array_map(
            fn ($profile) => $profile->display_name,
            $this->profiles->getPublicProfiles($userIds),
        );
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
