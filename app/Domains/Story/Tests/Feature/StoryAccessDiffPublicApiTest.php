<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('StoryPublicApi::diffAccessForUsers', function () {
    it('public -> community: unconfirmed users lose access; confirmed keep; authors keep', function () {
        $author = alice($this);
        $story = publicStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        // Make confirmed a collaborator as well
        addCollaborator($story->id, $confirmed->id, 'author');

        setStoryVisibility($story->id, 'community');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'public');

        expect($diff['gained'])->toBe([]);
        expect($diff['lost'])->toEqualCanonicalizing([$unconfirmed->id]);
    });

    it('community -> public: unconfirmed users gain access', function () {
        $author = alice($this);
        $story = communityStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        setStoryVisibility($story->id, 'public');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'community');

        expect($diff['gained'])->toEqualCanonicalizing([$unconfirmed->id]);
        expect($diff['lost'])->toBe([]);
    });

    it('private -> community: confirmed users gain access; authors always keep', function () {
        $author = alice($this);
        $story = privateStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        setStoryVisibility($story->id, 'community');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'private');

        expect($diff['gained'])->toEqualCanonicalizing([$confirmed->id]);
        expect($diff['lost'])->toBe([]);
    });

    it('private -> public: all non-authors gain', function () {
        $author = alice($this);
        $story = privateStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        setStoryVisibility($story->id, 'public');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'private');

        expect($diff['gained'])->toEqualCanonicalizing([$confirmed->id, $unconfirmed->id]);
        expect($diff['lost'])->toBe([]);
    });

    it('public -> private: everyone loses except collaborators', function () {
        $author = alice($this);
        $story = publicStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        addCollaborator($story->id, $confirmed->id, 'author');

        setStoryVisibility($story->id, 'private');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'public');

        expect($diff['gained'])->toBe([]);
        expect($diff['lost'])->toEqualCanonicalizing([$unconfirmed->id]);
    });

    it('community -> private: all confirmed users lose except collaborators', function () {
        $author = alice($this);
        $story = communityStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $collaborator = daniel($this, roles: [Roles::USER_CONFIRMED]);
        addCollaborator($story->id, $collaborator->id, 'collab');

        setStoryVisibility($story->id, 'private');

        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id, $collaborator->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'community');

        expect($diff['gained'])->toBe([]);
        expect($diff['lost'])->toEqualCanonicalizing([$confirmed->id]);
    });

    it('same visibility: returns empty changes', function () {
        $author = alice($this);
        $story = publicStory('S', $author->id);
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        setStoryVisibility($story->id, 'public');
        $api = app(StoryPublicApi::class);
        $userIds = [$confirmed->id, $unconfirmed->id];
        $diff = $api->diffAccessForUsers($userIds, $story->id, 'public');

        expect($diff['gained'])->toBe([]);
        expect($diff['lost'])->toBe([]);
    });

    it('unknown story: returns empty changes', function () {
        $api = app(StoryPublicApi::class);
        $diff = $api->diffAccessForUsers([1,2,3], 999999, 'public');
        expect($diff['gained'])->toBe([]);
        expect($diff['lost'])->toBe([]);
    });
});
