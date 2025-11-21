<?php

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Services\ChapterCommentPolicy;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\StoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Chapter comment policy integration (min length = 140)', function () {
    it('exposes minRootCommentLength=140 in list config for entityType=chapter', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $list = listComments('chapter', 123);
        expect($list->config->minRootCommentLength)->toBe(140);
    });

    it('rejects creating a chapter root comment shorter than 140 characters', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        expect(function () {
            createComment('chapter', 123, generateDummyText(139), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
    });

    it('allows creating a chapter root comment with exactly 140 characters', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentId = createComment('chapter', 123, generateDummyText(140), null);
        expect($commentId)->toBeGreaterThan(0);
    });
});

describe('URL generation for chapter comments', function () {
    it('should generate correct URL for chapter comment with story and chapter slugs', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id, ['slug' => 'test-story']);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Test Chapter', 'slug' => 'test-chapter']);
        
        $policy = new ChapterCommentPolicy(
            app(ChapterService::class),
            app(StoryService::class),
            app(CommentPublicApi::class)
        );
        
        $url = $policy->getUrl($chapter->id, 123);
        expect($url)->toBe(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]) . '?comment=123');
    });
    
    it('should return null when chapter does not exist', function () {
        $policy = new ChapterCommentPolicy(
            app(ChapterService::class),
            app(StoryService::class),
            app(CommentPublicApi::class)
        );
        
        $url = $policy->getUrl(999999, 123);
        expect($url)->toBeNull();
    });
});

describe('Regarding root comment creation', function () {
    it('should not allow authors to create a root comment', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $list = listComments('chapter', $chapter->id);
        expect($list->config->canCreateRoot)->toBe(false);

        expect(function () use ($chapter) {
            createComment('chapter', $chapter->id, generateDummyText(140), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });

    it('should allow only one root comment per user', function () {
        $user = alice($this);
        $story = publicStory('Public Story', $user->id);
        $chapter = createPublishedChapter($this, $story, $user, ['title' => 'Pub Chap']);

        $bob = bob($this);
        $this->actingAs($bob);
        createComment('chapter', $chapter->id, generateDummyText(140), null);

        $list = listComments('chapter', $chapter->id);
        expect($list->config->canCreateRoot)->toBe(false);

        expect(function () use ($chapter) {
            createComment('chapter', $chapter->id, generateDummyText(140), null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });
});
