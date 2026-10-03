<?php

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Private\Models\Notification;
use App\Domains\Story\Private\Services\ChapterCommentPolicy;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Story\Public\Notifications\ChapterRootCommentNotification;
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

describe('Regarding annotations', function () {
    beforeEach(function () {
        $this->policy = new ChapterCommentPolicy(
            app(ChapterService::class),
            app(StoryService::class),
            app(CommentPublicApi::class)
        );
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
    });

    it('supportsAnnotations: chapters are annotatable', function () {
        expect($this->policy->supportsAnnotations())->toBeTrue();
    });

    it('canAnnotate: true for a reader who has not commented yet', function () {
        $reader = bob($this);

        expect($this->policy->canAnnotate($this->chapter->id, $reader->id))->toBeTrue();
    });

    it('canAnnotate: false for the author, a co-author, and user id 0', function () {
        $coAuthor = carol($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');

        expect($this->policy->canAnnotate($this->chapter->id, $this->author->id))->toBeFalse()
            ->and($this->policy->canAnnotate($this->chapter->id, $coAuthor->id))->toBeFalse()
            ->and($this->policy->canAnnotate($this->chapter->id, 0))->toBeFalse();
    });

    it('canAnnotate: true for a reader who already posted a root comment', function () {
        $commenter = bob($this);
        $this->actingAs($commenter);
        createComment('chapter', $this->chapter->id, generateDummyText(140), null);

        expect($this->policy->canAnnotate($this->chapter->id, $commenter->id))->toBeTrue();
    });

    it('canAnnotate: true for a reader whose root comment was emptied by a moderator', function () {
        $commenter = bob($this);
        $this->actingAs($commenter);
        $commentId = createComment('chapter', $this->chapter->id, generateDummyText(140), null);

        $this->actingAs(moderator($this))
            ->post(route('comments.moderation.empty-content', ['commentId' => $commentId]))
            ->assertRedirect();

        expect($this->policy->canAnnotate($this->chapter->id, $commenter->id))->toBeTrue();
    });

    it('canAnnotate: true for a beta reader', function () {
        $betaReader = daniel($this);
        addCollaborator($this->story->id, $betaReader->id, 'betareader');

        expect($this->policy->canAnnotate($this->chapter->id, $betaReader->id))->toBeTrue();
    });

    it('canMarkAsProcessed: true for the author and a co-author; false for a beta reader and for a plain reader', function () {
        $coAuthor = carol($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');
        $betaReader = daniel($this);
        addCollaborator($this->story->id, $betaReader->id, 'betareader');
        $reader = bob($this);

        expect($this->policy->canMarkAsProcessed($this->chapter->id, $this->author->id))->toBeTrue()
            ->and($this->policy->canMarkAsProcessed($this->chapter->id, $coAuthor->id))->toBeTrue()
            ->and($this->policy->canMarkAsProcessed($this->chapter->id, $betaReader->id))->toBeFalse()
            ->and($this->policy->canMarkAsProcessed($this->chapter->id, $reader->id))->toBeFalse();
    });

    it('caps annotation bodies at 1000 and highlights at 500 characters', function () {
        expect($this->policy->getAnnotationBodyMaxLength())->toBe(1000)
            ->and($this->policy->getAnnotationHighlightMaxLength())->toBe(500);
    });

    it('posting a root comment with annotations grants one credit and sends one ChapterRootCommentNotification', function () {
        $reader = bob($this);
        $credits = app(ChapterCreditService::class);
        $credits->grantInitialOnRegistration($reader->id);
        $before = $credits->availableForUser($reader->id);

        $this->actingAs($reader);
        $this->post('/comments', [
            'entity_type' => 'chapter',
            'entity_id' => $this->chapter->id,
            'body' => generateDummyText(140),
            'annotations' => json_encode([
                ['body' => '<p>Un</p>', 'highlighted_text' => 'premier passage'],
                ['body' => '<p>Deux</p>', 'highlighted_text' => 'second passage'],
            ]),
        ])->assertSessionHasNoErrors();

        expect($credits->availableForUser($reader->id))->toBe($before + 1)
            ->and(Notification::query()->where('content_key', ChapterRootCommentNotification::type())->count())->toBe(1);
    });
});
