<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsCommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function publishedNews(int $createdBy, array $overrides = []): News
{
    return News::factory()->published()->create(array_merge([
        'created_by' => $createdBy,
    ], $overrides));
}

function draftNews(int $createdBy, array $overrides = []): News
{
    return News::factory()->create(array_merge([
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $createdBy,
    ], $overrides));
}

describe('News comment policy — root comment length (min = 20)', function () {
    it('exposes minRootCommentLength=20 in list config for entityType=news', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        $list = listComments('news', $news->id);
        expect($list->config->minRootCommentLength)->toBe(20);
    });

    it('rejects a root comment shorter than 20 characters', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        expect(function () use ($news) {
            createComment('news', $news->id, generateDummyText(19), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
    });

    it('accepts a root comment of exactly 20 characters', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        $commentId = createComment('news', $news->id, generateDummyText(20), null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('applies no minimum length to replies', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
        $rootId = createComment('news', $news->id, generateDummyText(20), null);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
        $replyId = createComment('news', $news->id, 'abc', $rootId);

        expect($replyId)->toBeGreaterThan(0);
    });
});

describe('News comment policy — who may post a root comment', function () {
    it('allows the same user to post several root comments on one article', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        $first = createComment('news', $news->id, generateDummyText(20), null);
        $second = createComment('news', $news->id, generateDummyText(25), null);

        expect($first)->toBeGreaterThan(0);
        expect($second)->toBeGreaterThan(0);
    });

    it('allows the article creator to comment on their own article', function () {
        $author = admin($this);
        $news = publishedNews($author->id);

        $this->actingAs($author);

        $list = listComments('news', $news->id);
        expect($list->config->canCreateRoot)->toBe(true);

        $commentId = createComment('news', $news->id, generateDummyText(20), null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('refuses a root comment on a draft article', function () {
        $author = admin($this);
        $news = draftNews($author->id);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        $list = listComments('news', $news->id);
        expect($list->config->canCreateRoot)->toBe(false);

        expect(function () use ($news) {
            createComment('news', $news->id, generateDummyText(20), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });

    it('refuses a root comment on a draft article even for an admin', function () {
        $author = admin($this);
        $news = draftNews($author->id);

        $this->actingAs($author);

        expect(function () use ($news) {
            createComment('news', $news->id, generateDummyText(20), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });

    it('returns false from canCreateRoot for an article id that does not exist', function () {
        $user = alice($this);

        $policy = new NewsCommentPolicy();

        expect($policy->canCreateRoot(999999, $user->id))->toBeFalse();
    });
});

describe('News comment policy — URL generation', function () {
    it('builds the moderation deep link for a news comment', function () {
        $author = admin($this);
        $news = publishedNews($author->id, ['title' => 'Deep Link News', 'slug' => 'deep-link-news']);

        $policy = new NewsCommentPolicy();

        expect($policy->getUrl($news->id, 123))
            ->toBe(route('news.show', ['slug' => $news->slug]) . '?comment=123');
    });

    it('returns null from getUrl for an article id that does not exist', function () {
        $policy = new NewsCommentPolicy();

        expect($policy->getUrl(999999, 123))->toBeNull();
    });
});
