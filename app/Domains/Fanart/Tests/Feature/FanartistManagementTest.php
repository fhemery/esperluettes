<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function grantUrl(string $slug): string
{
    return route('fanart.fanartists.store', ['slug' => $slug]);
}

function revokeUrl(string $slug, int $userId): string
{
    return route('fanart.fanartists.destroy', ['slug' => $slug, 'userId' => $userId]);
}

it('lets an author grant the role to a confirmed user and notifies them', function () {
    $author = alice($this);
    $target = bob($this);
    $story = publicStory('Histoire', $author->id);
    FanartRoleRequest::create(['story_id' => $story->id, 'user_id' => $target->id, 'status' => FanartRoleRequest::STATUS_PENDING]);

    $this->actingAs($author)
        ->post(grantUrl($story->slug), ['target_users' => [$target->id]])
        ->assertRedirect()
        ->assertSessionHas('success');

    $row = Fanartist::query()->where('story_id', $story->id)->where('user_id', $target->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->granted_by_user_id)->toBe($author->id)
        ->and(FanartRoleRequest::query()->where('story_id', $story->id)->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.role_given'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.role_given')->id))->toBe([$target->id]);

    // idempotent: no duplicate row, no second notification
    $this->actingAs($author)->post(grantUrl($story->slug), ['target_users' => [$target->id]])->assertRedirect();
    expect(Fanartist::query()->where('story_id', $story->id)->count())->toBe(1)
        ->and(countNotificationsByKey('fanart.role_given'))->toBe(1);
});

it('does not give a fanartiste access to a private story', function () {
    $author = alice($this);
    $target = bob($this);
    $story = privateStory('Secrete', $author->id);

    $this->actingAs($author)->post(grantUrl($story->slug), ['target_users' => [$target->id]]);

    expect(Fanartist::query()->where('user_id', $target->id)->exists())->toBeTrue()
        ->and(app(StoryPublicApi::class)->canRead($target->id, $story->id))->toBeFalse();
});

it('refuses to grant the role to a non-confirmed user', function () {
    $author = alice($this);
    $plain = bob($this, roles: [Roles::USER]);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs($author)
        ->post(grantUrl($story->slug), ['target_users' => [$plain->id]])
        ->assertRedirect()
        ->assertSessionHasErrors('target_users');

    expect(Fanartist::query()->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.role_given'))->toBe(0);
});

it('lets a bêta-lecteur also be fanartiste (cumulative)', function () {
    $author = alice($this);
    $beta = bob($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $beta->id, 'beta-reader');

    $this->actingAs($author)->post(grantUrl($story->slug), ['target_users' => [$beta->id]])->assertSessionHas('success');

    expect(app(FanartistService::class)->isFanartist($beta->id, $story->id))->toBeTrue()
        ->and(app(StoryPublicApi::class)->canRead($beta->id, $story->id))->toBeTrue();
});

it('revokes the role without touching the user\'s fanarts', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $this->actingAs($author)->post(grantUrl($story->slug), ['target_users' => [$artist->id]]);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($author)
        ->delete(revokeUrl($story->slug, $artist->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Fanartist::query()->count())->toBe(0)
        ->and(Fanart::query()->find($fanart->id))->not->toBeNull();
});

it('returns 403 to a co-collaborator who is not an author and to the fanartiste', function () {
    $author = alice($this);
    $beta = bob($this);
    $fan = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $beta->id, 'beta-reader');
    Fanartist::create(['story_id' => $story->id, 'user_id' => $fan->id, 'granted_by_user_id' => $author->id]);

    foreach ([$beta, $fan] as $user) {
        $this->actingAs($user)->post(grantUrl($story->slug), ['target_users' => [$user->id]])->assertForbidden();
        $this->actingAs($user)->delete(revokeUrl($story->slug, $fan->id))->assertForbidden();
    }

    expect(Fanartist::query()->count())->toBe(1);
});

it('renders the Fanartistes section on the collaborators page for an author', function () {
    $author = alice($this);
    $fan = bob($this);
    $story = publicStory('Histoire', $author->id);
    Fanartist::create(['story_id' => $story->id, 'user_id' => $fan->id, 'granted_by_user_id' => $author->id]);

    $this->actingAs($author)
        ->get(route('stories.collaborators.index', ['slug' => $story->slug]))
        ->assertOk()
        ->assertSee(__('fanart::ui.fanartists.title'))
        ->assertSee(revokeUrl($story->slug, $fan->id), false)
        ->assertSee(grantUrl($story->slug), false);
});
