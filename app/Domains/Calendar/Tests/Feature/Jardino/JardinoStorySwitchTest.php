<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoStorySnapshot;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Jardino story switching', function () {
    beforeEach(function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        $user = alice($this);
        $this->user = $user;
        $this->actingAs($user);

        $this->storyA = publicStory('Story A', $user->id);
        $this->storyB = publicStory('Story B', $user->id);

        $admin = admin($this);
        $this->actingAs($admin);
        $activityId = createActiveJardino($this);

        $this->actingAs($user);
        $this->activity = Activity::findOrFail($activityId->id);

        createGoal($this->activity->id, $user->id, $this->storyA->id, 10000);
        $this->goal = JardinoGoal::query()
            ->where('activity_id', $this->activity->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    });

    $snapshotFor = function (int $goalId, int $storyId): ?JardinoStorySnapshot {
        return JardinoStorySnapshot::query()
            ->where('goal_id', $goalId)
            ->where('story_id', $storyId)
            ->first();
    };

    it('keeps one snapshot per story when switching A → B → A', function () use ($snapshotFor) {
        $firstA = $snapshotFor($this->goal->id, $this->storyA->id);
        expect($firstA)->not->toBeNull();

        createGoal($this->activity->id, $this->user->id, $this->storyB->id, 10000);
        createGoal($this->activity->id, $this->user->id, $this->storyA->id, 10000);

        $snapshots = JardinoStorySnapshot::query()->where('goal_id', $this->goal->id)->get();
        expect($snapshots)->toHaveCount(2);
        expect($snapshots->pluck('story_id')->sort()->values()->all())
            ->toBe(collect([$this->storyA->id, $this->storyB->id])->sort()->values()->all());

        $resumedA = $snapshotFor($this->goal->id, $this->storyA->id);
        expect($resumedA->id)->toBe($firstA->id);
        expect($resumedA->initial_word_count)->toBe($firstA->initial_word_count);
    });

    it('counts progress from every story across switches', function () {
        dispatchChapterCreated($this->storyA->id, 1500);

        createGoal($this->activity->id, $this->user->id, $this->storyB->id, 10000);
        dispatchChapterCreated($this->storyB->id, 1000);

        createGoal($this->activity->id, $this->user->id, $this->storyA->id, 10000);
        dispatchChapterCreated($this->storyA->id, 500, ['id' => 2]);

        $objective = getJardinoObjectiveViewModel($this->activity);
        expect($objective->wordsWritten)->toBe(3000);
    });

    it('applies word deltas only to the tracked story snapshot', function () use ($snapshotFor) {
        createGoal($this->activity->id, $this->user->id, $this->storyB->id, 10000);

        $aBefore = $snapshotFor($this->goal->id, $this->storyA->id)->current_word_count;
        $bBefore = $snapshotFor($this->goal->id, $this->storyB->id)->current_word_count;

        dispatchChapterCreated($this->storyA->id, 700);

        expect($snapshotFor($this->goal->id, $this->storyA->id)->current_word_count)->toBe($aBefore);
        expect($snapshotFor($this->goal->id, $this->storyB->id)->current_word_count)->toBe($bBefore);

        dispatchChapterCreated($this->storyB->id, 400, ['id' => 2]);

        expect($snapshotFor($this->goal->id, $this->storyA->id)->current_word_count)->toBe($aBefore);
        expect($snapshotFor($this->goal->id, $this->storyB->id)->current_word_count)->toBe($bBefore + 400);
    });

    it('no longer has a deselected_at column', function () {
        expect(Schema::hasColumn('calendar_jardino_story_snapshots', 'deselected_at'))->toBeFalse();
    });
});
