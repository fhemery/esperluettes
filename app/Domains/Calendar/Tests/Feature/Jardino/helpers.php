<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoProgressService;
use App\Domains\Calendar\Private\Activities\Jardino\View\Components\JardinoComponent;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoObjectiveViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoViewModel;
use App\Domains\Calendar\Private\Services\ActivityService;
use Tests\TestCase;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use Illuminate\Testing\TestResponse;

/**
 * Create an ACTIVE Jardino activity and return helper data.
 * Returns an object: { id: int, url: string }
 */
function createActiveJardino(TestCase $t, array $overrides = [], ?int $actorUserId = null): object
{
    $baseOverrides = [
        'name' => 'Jardino',
        'activity_type' => 'jardino',
        'preview_starts_at' => now()->subDay(),
        'active_starts_at' => now()->subHour(),
        'active_ends_at' => now()->addDay(),
    ];

    $id = createActivity($t, overrides: array_merge($baseOverrides, $overrides), actorUserId: $actorUserId);
    $activity = Activity::findOrFail($id);
    $url = route('calendar.activities.show', $activity->slug);

    return (object) [
        'id' => $id,
        'url' => $url,
    ];
}


/**
 * Create a chapter snapshot for testing
 */
function chapterSnapshot(
    int $id,
    string $title,
    string $slug,
    int $sortOrder,
    string $status,
    int $wordCount,
    int $charCount
): ChapterSnapshot {
    return new ChapterSnapshot(
        id: $id,
        title: $title,
        slug: $slug,
        sortOrder: $sortOrder,
        status: $status,
        wordCount: $wordCount,
        charCount: $charCount
    );
}

/**
 * Create and dispatch a ChapterCreated event
 */
function dispatchChapterCreated(int $storyId, int $nbWords, array $overrides = []): void
{
    $chapterSnapshot = chapterSnapshot(
        id: $overrides['id'] ?? 1,
        title: $overrides['title'] ?? 'Chapter',
        slug: $overrides['slug'] ?? 'chapter',
        sortOrder: $overrides['sortOrder'] ?? 1,
        status: $overrides['status'] ?? 'published',
        wordCount: $nbWords,
        charCount: $nbWords * 6
    );
    dispatchEvent(new ChapterCreated($storyId, $chapterSnapshot));
}

/**
 * Create and dispatch a ChapterUpdated event
 */
function dispatchChapterUpdated(int $storyId, int $beforeWordCount, int $afterWordCount): void
{
    $beforeSnapshot = chapterSnapshot(
        id: 1,
        title: 'Chapter',
        slug: 'chapter',
        sortOrder: 1,
        status: 'published',
        wordCount: $beforeWordCount,
        charCount: $beforeWordCount * 6
    );
    $afterSnapshot = chapterSnapshot(
        id: 1,
        title: 'Chapter',
        slug: 'chapter',
        sortOrder: 1,
        status: 'published',
        wordCount: $afterWordCount,
        charCount: $afterWordCount * 6
    );
    dispatchEvent(new ChapterUpdated($storyId, $beforeSnapshot, $afterSnapshot));
}

function dispatchChapterDeleted(int $storyId, int $nbWords): void
{
    $chapter = chapterSnapshot(
        id: 1,
        title: 'Chapter',
        slug: 'chapter',
        sortOrder: 1,
        status: 'published',
        wordCount: $nbWords,
        charCount: $nbWords * 6
    );
    dispatchEvent(new ChapterDeleted($storyId, $chapter));
}

function renderJardinoComponent(Activity $activity): string
{
    $component = new JardinoComponent(
        activity: $activity,
        stories: app(StoryPublicApi::class),
        progressService: app(JardinoProgressService::class),
        flowerService: app(JardinoFlowerService::class),
        profileApi: app(ProfilePublicApi::class),
        activityService: app(ActivityService::class),
    );

    return $component->render()->render();
}

/**
 * Create component and return its viewmodel for testing
 */
function getJardinoViewModel(Activity $activity): ?JardinoViewModel
{
    $component = new JardinoComponent(
        activity: $activity,
        stories: app(StoryPublicApi::class),
        progressService: app(JardinoProgressService::class),
        flowerService: app(JardinoFlowerService::class),
        profileApi: app(ProfilePublicApi::class),
        activityService: app(ActivityService::class),
    );

    $view = $component->render();
    $viewModel = $view->getData()['vm'];

    return $viewModel;
}

function getJardinoObjectiveViewModel(Activity $activity): ?JardinoObjectiveViewModel
{
    return getJardinoViewModel($activity)->objective;
}

function createGoal(int $activityId, int $userId, int $storyId, int $wordCount): void
{
    $goalService = app(JardinoGoalService::class);
    $goalService->createOrUpdateGoal($activityId, $userId, $storyId, $wordCount);
}

function plantFlower(TestCase $t, int $activityId, int $x, int $y, string $flowerName='01'): TestResponse
{
    return $t->post(route('jardino.flower.plant', $activityId), [
        'x' => $x,
        'y' => $y,
        'flower_image' => $flowerName.'.png',
    ]);
}

function removeFlower(TestCase $t, int $activityId, int $x, int $y): TestResponse
{
    return $t->post(route('jardino.flower.remove', $activityId), [
        'x' => $x,
        'y' => $y,
    ]);
}
