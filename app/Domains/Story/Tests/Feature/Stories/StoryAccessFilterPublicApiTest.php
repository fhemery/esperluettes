<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('StoryPublicApi::filterUsersWithAccessToStory', function () {
    it('returns only existing users for a public story (dedup preserved)', function () {
        $author = alice($this);
        $story = publicStory('Public', $author->id);

        $u1 = bob($this);              // confirmed by default helper
        $u2 = carol($this);            // confirmed by default helper
        $ids = [$u1->id, $u2->id, $u1->id, 999999]; // includes duplicate and non-existing id

        $api = app(StoryPublicApi::class);
        $allowed = $api->filterUsersWithAccessToStory($ids, $story->id);

        // Order is not guaranteed; compare as sets
        expect($allowed)->toContain($u1->id);
        expect($allowed)->toContain($u2->id);
        expect($allowed)->not->toContain(999999);
        expect(count($allowed))->toBe(2);
    });

    it('returns only authors for a private story', function () {
        $author = alice($this);
        $story = privateStory('Private', $author->id);
        $other = bob($this);

        $api = app(StoryPublicApi::class);
        $allowed = $api->filterUsersWithAccessToStory([$author->id, $other->id], $story->id);

        expect($allowed)->toContain($author->id);
        expect($allowed)->not->toContain($other->id);
        expect(count($allowed))->toBe(1);
    });

    it('returns confirmed users for a community story, plus authors and collaborators', function () {
        $author = alice($this);
        $story = communityStory('Community', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $collaborator = daniel($this, roles: [Roles::USER]);

        addCollaborator($story->id, $collaborator->id, 'whatever');

        $api = app(StoryPublicApi::class);
        $allowed = $api->filterUsersWithAccessToStory([$confirmed->id, $unconfirmed->id, $author->id, $collaborator->id], $story->id);

        expect($allowed)->toContain($author->id);
        expect($allowed)->toContain($confirmed->id);
        expect($allowed)->toContain($collaborator->id);
        expect($allowed)->not->toContain($unconfirmed->id);
        expect(count($allowed))->toBe(3);
    });

    it('returns empty array when story does not exist', function () {
        $u1 = bob($this);
        $api = app(StoryPublicApi::class);
        $allowed = $api->filterUsersWithAccessToStory([$u1->id], 123456);
        expect($allowed)->toBeArray()->toBeEmpty();
    });

    it('returns empty array when input list is empty', function () {
        $author = alice($this);
        $story = publicStory('Any', $author->id);
        $api = app(StoryPublicApi::class);
        $allowed = $api->filterUsersWithAccessToStory([], $story->id);
        expect($allowed)->toBeArray()->toBeEmpty();
    });
});
