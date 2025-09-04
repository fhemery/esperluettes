<?php

use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Chapter comment policy integration (min length = 140)', function () {
    it('exposes minBodyLength=140 in list config for entityType=chapter', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $list = listComments('chapter', 123);
        expect($list->config->minBodyLength)->toBe(140);
    });

    it('rejects creating a chapter root comment shorter than 140 characters', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        expect(function () {
            createComment('chapter', 123, 'too short', null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
    });

    it('allows creating a chapter root comment with exactly 140 characters', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $exact140 = str_repeat('a', 140);
        $commentId = createComment('chapter', 123, $exact140, null);
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
            createComment('chapter', $chapter->id, 'too short', null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });
});
