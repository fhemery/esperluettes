<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function readabilityFixture($t): array
{
    $author = alice($t);
    $collab = bob($t, roles: [Roles::USER]);
    $pub = publicStory('Pub', $author->id);
    $com = communityStory('Com', $author->id);
    $priv = privateStory('Priv', $author->id);
    addCollaborator($priv->id, $collab->id, 'beta-reader');

    return [$author, $collab, $pub->id, $com->id, $priv->id];
}

describe('filterReadableStoryIds', function () {
    it('filterReadableStoryIds: guest gets only public stories', function () {
        [, , $pub, $com, $priv] = readabilityFixture($this);

        $ids = app(StoryPublicApi::class)->filterReadableStoryIds(null, [$pub, $com, $priv]);

        expect($ids)->toEqualCanonicalizing([$pub]);
    });

    it('filterReadableStoryIds: non-confirmed user gets public + private-where-collaborator, not community', function () {
        [, $collab, $pub, $com, $priv] = readabilityFixture($this);
        $other = carol($this, roles: [Roles::USER]);
        $api = app(StoryPublicApi::class);

        expect($api->filterReadableStoryIds($collab->id, [$pub, $com, $priv]))->toEqualCanonicalizing([$pub, $priv]);
        expect($api->filterReadableStoryIds($other->id, [$pub, $com, $priv]))->toEqualCanonicalizing([$pub]);
    });

    it('filterReadableStoryIds: confirmed user gets public + community + private-where-collaborator', function () {
        [$author, , $pub, $com, $priv] = readabilityFixture($this);
        $other = carol($this);
        $api = app(StoryPublicApi::class);

        expect($api->filterReadableStoryIds($other->id, [$pub, $com, $priv]))->toEqualCanonicalizing([$pub, $com]);
        expect($api->filterReadableStoryIds($author->id, [$pub, $com, $priv]))->toEqualCanonicalizing([$pub, $com, $priv]);
    });

    it('filterReadableStoryIds: moderator gets every non-deleted story', function () {
        [, , $pub, $com, $priv] = readabilityFixture($this);
        $mod = moderator($this);

        $ids = app(StoryPublicApi::class)->filterReadableStoryIds($mod->id, [$pub, $com, $priv, 999999]);

        expect($ids)->toEqualCanonicalizing([$pub, $com, $priv]);
    });

    it('filterReadableStoryIds excludes soft-deleted stories (deactivated author)', function () {
        [, , $pub, $com] = readabilityFixture($this);
        Story::query()->whereKey($pub)->firstOrFail()->delete();
        $mod = moderator($this);
        $api = app(StoryPublicApi::class);

        expect($api->filterReadableStoryIds(null, [$pub, $com]))->toBe([]);
        expect($api->filterReadableStoryIds($mod->id, [$pub, $com]))->toEqualCanonicalizing([$com]);
    });

    it('filterReadableStoryIds runs a single query for many stories', function () {
        $author = alice($this);
        $ids = [];
        foreach (range(1, 6) as $i) {
            $ids[] = ($i % 2 ? publicStory("S$i", $author->id) : communityStory("S$i", $author->id))->id;
        }
        $api = app(StoryPublicApi::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $api->filterReadableStoryIds(null, $ids);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($queries)->toHaveCount(1);
    });

    it('canRead mirrors StoryPolicy::view for each visibility', function () {
        [$author, $collab, $pub, $com, $priv] = readabilityFixture($this);
        $other = carol($this);
        $guestLike = carol($this, roles: [Roles::USER]);
        $mod = moderator($this);
        $api = app(StoryPublicApi::class);

        foreach ([$pub, $com, $priv] as $storyId) {
            $story = Story::query()->findOrFail($storyId);
            foreach ([null, $author, $collab, $other, $guestLike, $mod] as $user) {
                expect($api->canRead($user?->id, $storyId))
                    ->toBe(\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $story));
            }
        }
        expect($api->canRead(null, 999999))->toBeFalse();
    });
});

describe('chapter reads', function () {
    it('getReadChapterIds / getReadChapterIdsByStoryIds return the marked chapters', function () {
        $author = alice($this);
        $reader = bob($this);
        $s1 = publicStory('One', $author->id);
        $s2 = publicStory('Two', $author->id);
        $c1 = createPublishedChapter($this, $s1, $author);
        $c1b = createPublishedChapter($this, $s1, $author);
        $c2 = createPublishedChapter($this, $s2, $author);
        $this->actingAs($reader);
        markAsRead($this, $c1);
        markAsRead($this, $c2);

        $api = app(StoryPublicApi::class);

        expect($api->getReadChapterIds($reader->id, $s1->id))->toBe([$c1->id]);
        $byStory = $api->getReadChapterIdsByStoryIds($reader->id, [$s1->id, $s2->id]);
        expect($byStory[$s1->id])->toBe([$c1->id]);
        expect($byStory[$s2->id])->toBe([$c2->id]);
        expect($api->getReadChapterIdsByStoryIds($reader->id, []))->toBe([]);
        expect($c1b->id)->not->toBeIn($byStory[$s1->id]);
    });

    it('getChaptersForStory returns chapters in sort order with status', function () {
        $author = alice($this);
        $s1 = publicStory('One', $author->id);
        $s2 = publicStory('Two', $author->id);
        $a = createPublishedChapter($this, $s1, $author);
        $b = createUnpublishedChapter($this, $s1, $author);
        $c = createPublishedChapter($this, $s2, $author);
        $api = app(StoryPublicApi::class);

        $chapters = $api->getChaptersForStory($s1->id);

        expect(array_map(fn ($d) => $d->id, $chapters))->toBe([$a->id, $b->id]);
        expect($chapters[0]->status)->toBe('published');
        expect($chapters[1]->status)->not->toBe('published');

        $many = $api->getChaptersForStories([$s1->id, $s2->id]);
        expect(array_map(fn ($d) => $d->id, $many[$s1->id]))->toBe([$a->id, $b->id]);
        expect(array_map(fn ($d) => $d->id, $many[$s2->id]))->toBe([$c->id]);
    });
});

describe('chapter credits', function () {
    it('spendChapterCredit spends one and returns true while credits remain', function () {
        $user = alice($this);
        setUserCredits($user->id, 2);
        $api = app(StoryPublicApi::class);
        $before = $api->getAvailableChapterCredits($user->id);

        expect($api->spendChapterCredit($user->id))->toBeTrue();
        expect($api->getAvailableChapterCredits($user->id))->toBe($before - 1);
    });

    it('spendChapterCredit returns false and spends nothing at zero credits', function () {
        $user = alice($this);
        DB::table('story_chapter_credits')->where('user_id', $user->id)->update(['credits_gained' => 3, 'credits_spent' => 3]);
        $api = app(StoryPublicApi::class);

        expect($api->spendChapterCredit($user->id))->toBeFalse();
        expect($api->getAvailableChapterCredits($user->id))->toBe(0);
        expect((int) DB::table('story_chapter_credits')->where('user_id', $user->id)->value('credits_spent'))->toBe(3);
    });

    it('spendChapterCredit returns false for a user with no credits row', function () {
        $user = alice($this);
        DB::table('story_chapter_credits')->where('user_id', $user->id)->delete();
        $api = app(StoryPublicApi::class);

        expect($api->spendChapterCredit($user->id))->toBeFalse();
        expect(DB::table('story_chapter_credits')->where('user_id', $user->id)->exists())->toBeFalse();
    });
});
