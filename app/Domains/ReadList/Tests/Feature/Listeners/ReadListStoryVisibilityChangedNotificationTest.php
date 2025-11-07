<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('ReadList notifications on StoryVisibilityChanged', function () {
    it('notifies readers who lose access when visibility tightens (public -> community)', function () {
        $author = alice($this);
        $story = publicStory('Vis Tighten', $author->id);

        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        // Both add to readlist (allowed while public)
        $this->actingAs($confirmed);
        addToReadList($this, $story->id);
        $this->actingAs($unconfirmed);
        addToReadList($this, $story->id);

        // Emit event (public -> community)
        setStoryVisibility($story->id, 'community');
        dispatchEvent(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'public',
            newVisibility: 'community',
        ));

        $notif = getLatestNotificationByKey('readlist.story.unpublished');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($unconfirmed->id);
        expect($targets)->not->toContain($confirmed->id);
        expect($targets)->toHaveCount(1);
    });

    it('notifies readers who gain access when visibility loosens (community -> public)', function () {
        $author = alice($this);
        $story = communityStory('Vis Loosen', $author->id);

        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);

        // Confirmed can add via controller
        $this->actingAs($confirmed);
        addToReadList($this, $story->id);
        // Unconfirmed could not add while community; simulate prior entry (e.g., added before tightening)
        ReadListEntry::create(['user_id' => $unconfirmed->id, 'story_id' => $story->id]);

        // Emit event (community -> public)
        setStoryVisibility($story->id, 'public');
        dispatchEvent(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'community',
            newVisibility: 'public',
        ));

        $notif = getLatestNotificationByKey('readlist.story.republished');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($unconfirmed->id);
        expect($targets)->not->toContain($confirmed->id);
        expect($targets)->toHaveCount(1);
    });
});
