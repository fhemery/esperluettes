<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function requestUrl(string $slug): string
{
    return route('fanart.role-requests.store', ['slug' => $slug]);
}

function pendingRequest(int $storyId, int $userId): FanartRoleRequest
{
    return FanartRoleRequest::create(['story_id' => $storyId, 'user_id' => $userId, 'status' => FanartRoleRequest::STATUS_PENDING]);
}

it('creates a request and notifies every author', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $coAuthor = carol($this);
    addCollaborator($story->id, $coAuthor->id, 'author');

    $this->actingAs($reader)->post(requestUrl($story->slug))->assertRedirect()->assertSessionHas('success');

    $row = FanartRoleRequest::query()->where('story_id', $story->id)->first();
    expect($row->user_id)->toBe($reader->id)
        ->and($row->status)->toBe(FanartRoleRequest::STATUS_PENDING)
        ->and(countNotificationsByKey('fanart.role_requested'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.role_requested')->id))
        ->toEqualCanonicalizing([$author->id, $coAuthor->id]);
});

it('refuses a request when no media type is in designated mode', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'yes');
    setSettingsValue($author->id, 'stories', 'fanart-audio-mode', 'yes');

    $this->actingAs($reader)->post(requestUrl($story->slug))->assertForbidden();

    expect(FanartRoleRequest::query()->count())->toBe(0);
});

it('refuses a second pending request for the same story', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    pendingRequest($story->id, $reader->id);

    $this->actingAs($reader)->post(requestUrl($story->slug))->assertForbidden();

    expect(FanartRoleRequest::query()->count())->toBe(1);
});

it('lets the requester withdraw', function () {
    $author = alice($this);
    $reader = bob($this);
    $other = carol($this);
    $story = publicStory('Histoire', $author->id);
    $request = pendingRequest($story->id, $reader->id);
    $url = route('fanart.role-requests.destroy', ['slug' => $story->slug, 'request' => $request->id]);

    $this->actingAs($other)->delete($url)->assertForbidden();
    expect(FanartRoleRequest::query()->count())->toBe(1);

    $this->actingAs($reader)->delete($url)->assertRedirect();
    expect(FanartRoleRequest::query()->count())->toBe(0);
});

it('accepts: grants the role, deletes the request, notifies role given', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $request = pendingRequest($story->id, $reader->id);

    $this->actingAs($author)
        ->post(route('fanart.role-requests.accept', ['slug' => $story->slug, 'request' => $request->id]))
        ->assertRedirect()->assertSessionHas('success');

    expect(Fanartist::query()->where('story_id', $story->id)->where('user_id', $reader->id)->exists())->toBeTrue()
        ->and(FanartRoleRequest::query()->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.role_given'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.role_given')->id))->toBe([$reader->id]);
});

it('declines: notifies the requester; a new request is refused for 30 days then allowed', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $request = pendingRequest($story->id, $reader->id);

    $this->actingAs($author)
        ->post(route('fanart.role-requests.decline', ['slug' => $story->slug, 'request' => $request->id]))
        ->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe(FanartRoleRequest::STATUS_DECLINED)
        ->and($request->decided_by_user_id)->toBe($author->id)
        ->and($request->decided_at)->not->toBeNull()
        ->and(countNotificationsByKey('fanart.role_declined'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.role_declined')->id))->toBe([$reader->id]);

    $this->actingAs($reader)->post(requestUrl($story->slug))->assertForbidden();

    $this->travel(29)->days();
    $this->actingAs($reader)->post(requestUrl($story->slug))->assertForbidden();

    $this->travel(2)->days();
    $this->actingAs($reader)->post(requestUrl($story->slug))->assertRedirect()->assertSessionHas('success');
    expect(FanartRoleRequest::query()->where('status', FanartRoleRequest::STATUS_PENDING)->count())->toBe(1);
});

it('refuses requests from authors, existing fanartistes and non-confirmed users', function () {
    $author = alice($this);
    $fan = bob($this);
    $plain = carol($this, roles: [Roles::USER]);
    $story = publicStory('Histoire', $author->id);
    Fanartist::create(['story_id' => $story->id, 'user_id' => $fan->id, 'granted_by_user_id' => $author->id]);

    $this->actingAs($author)->post(requestUrl($story->slug))->assertForbidden();
    $this->actingAs($fan)->post(requestUrl($story->slug))->assertForbidden();
    $this->actingAs($plain)->post(requestUrl($story->slug))->assertRedirect()->assertSessionMissing('success');

    expect(FanartRoleRequest::query()->count())->toBe(0);
});

it('returns 403 on accept/decline to a non-author', function () {
    $author = alice($this);
    $reader = bob($this);
    $other = carol($this);
    $story = publicStory('Histoire', $author->id);
    $request = pendingRequest($story->id, $reader->id);

    foreach (['accept', 'decline'] as $action) {
        $this->actingAs($other)
            ->post(route('fanart.role-requests.' . $action, ['slug' => $story->slug, 'request' => $request->id]))
            ->assertForbidden();
        $this->actingAs($reader)
            ->post(route('fanart.role-requests.' . $action, ['slug' => $story->slug, 'request' => $request->id]))
            ->assertForbidden();
    }

    expect($request->fresh()->status)->toBe(FanartRoleRequest::STATUS_PENDING)
        ->and(Fanartist::query()->count())->toBe(0);
});

it('lists pending requests in the collaborators section for authors only', function () {
    $author = alice($this);
    $reader = bob($this, ['name' => 'Lectrice']);
    $story = publicStory('Histoire', $author->id);
    $request = pendingRequest($story->id, $reader->id);

    $html = (string) $this->blade('<x-fanart::collaborators-section :story-id="$id" />', ['id' => $story->id]);
    expect($html)->toBe('');

    $this->actingAs($author);
    $html = (string) $this->blade('<x-fanart::collaborators-section :story-id="$id" />', ['id' => $story->id]);
    expect($html)->toContain(route('fanart.role-requests.accept', ['slug' => $story->slug, 'request' => $request->id]))
        ->toContain(route('fanart.role-requests.decline', ['slug' => $story->slug, 'request' => $request->id]));
});

it('renders the request button according to the viewer state', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $render = fn () => (string) $this->blade('<x-fanart::request-role-button :story-id="$id" />', ['id' => $story->id]);

    expect(trim($render()))->toBe('');

    $this->actingAs($reader);
    expect($render())->toContain(requestUrl($story->slug));

    $request = pendingRequest($story->id, $reader->id);
    expect($render())->toContain(route('fanart.role-requests.destroy', ['slug' => $story->slug, 'request' => $request->id]));

    $this->actingAs($author);
    expect(trim($render()))->toBe('');
});
