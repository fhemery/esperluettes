<?php

use App\Domains\Auth\PublicApi\Roles;
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
