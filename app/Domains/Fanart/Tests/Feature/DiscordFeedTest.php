<?php

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');

    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY'] = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;
});

it('rejects a request without the bot token', function () {
    $this->getJson('/api/discord/fanarts/pending')->assertStatus(401);
    $this->getJson('/api/discord/fanarts/pending', ['Authorization' => 'Bearer wrong'])->assertStatus(401);
    $this->postJson('/api/discord/fanarts/mark-sent', ['ids' => [1]])->assertStatus(401);
});

it('lists published images with share_on_discord on public stories only', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $fanart = sharedImage(['story_id' => $story->id, 'user_id' => bob($this)->id, 'chapter_id' => $chapter->id, 'title' => 'Mon dessin']);
    sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'share_on_discord' => false]);

    $response = botGetPending($this)->assertOk();

    $response->assertJsonCount(1, 'data');
    $item = $response->json('data.0');
    expect($item['id'])->toBe($fanart->id)
        ->and($item['title'])->toBe('Mon dessin')
        ->and($item['artistName'])->toBeString()->not->toBe('')
        ->and($item['storyTitle'])->toBe('Histoire')
        ->and($item['storyUrl'])->toBe(route('stories.show', $story->slug))
        ->and($item['chapterTitle'])->toBe($chapter->title)
        ->and($item['imageUrl'])->toContain('/fanarts/' . $fanart->id . '/discord-image')
        ->and($item['imageUrl'])->toContain('signature=');
    $response->assertJsonPath('pagination.total', 1);
});

it('excludes audio, pending, community and private stories, and already-sent ones', function () {
    $author = alice($this);
    $public = publicStory('Publique', $author->id);
    $community = communityStory('Communaute', $author->id);
    $private = privateStory('Privee', $author->id);

    $kept = sharedImage(['story_id' => $public->id, 'user_id' => $author->id]);
    sharedImage(['story_id' => $public->id, 'user_id' => $author->id, 'status' => FanartStatus::Pending, 'published_at' => null]);
    sharedImage(['story_id' => $public->id, 'user_id' => $author->id, 'discord_sent_at' => now()]);
    createFanart(['story_id' => $public->id, 'user_id' => $author->id, 'kind' => FanartKind::Audio, 'share_on_discord' => true, 'chapter_id' => 1]);
    sharedImage(['story_id' => $community->id, 'user_id' => $author->id]);
    sharedImage(['story_id' => $private->id, 'user_id' => $author->id]);

    expect(collect(botGetPending($this)->assertOk()->json('data'))->pluck('id')->all())->toBe([$kept->id]);
});

it('includes author artworks that ticked the box', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $artwork = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'is_artwork' => true]);

    $data = botGetPending($this)->assertOk()->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['id'])->toBe($artwork->id)->and($data[0])->not->toHaveKey('chapterTitle');
});

it('lists oldest first and paginates', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $newer = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'published_at' => now()->subDay()]);
    $older = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'published_at' => now()->subDays(3)]);

    expect(collect(botGetPending($this)->json('data'))->pluck('id')->all())->toBe([$older->id, $newer->id]);

    $page = botGetPending($this, ['perPage' => 1, 'page' => 2])->assertOk();
    expect($page->json('data.0.id'))->toBe($newer->id);
    $page->assertJsonPath('pagination.hasMore', false);
});

it('marks given ids as sent', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $a = sharedImage(['story_id' => $story->id, 'user_id' => $author->id]);
    $b = sharedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $this->postJson('/api/discord/fanarts/mark-sent', ['ids' => [$a->id]], ['Authorization' => 'Bearer __test_api_key__'])
        ->assertOk()->assertJson(['success' => true, 'markedCount' => 1]);

    expect($a->fresh()->discord_sent_at)->not->toBeNull()
        ->and($b->fresh()->discord_sent_at)->toBeNull();
    expect(collect(botGetPending($this)->json('data'))->pluck('id')->all())->toBe([$b->id]);
});

it('validates the mark-sent payload', function () {
    $this->postJson('/api/discord/fanarts/mark-sent', ['ids' => 'x'], ['Authorization' => 'Bearer __test_api_key__'])
        ->assertStatus(422);
});

it('serves the signed image URL and rejects a tampered or expired one', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = sharedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $url = botGetPending($this)->json('data.0.imageUrl');

    $this->get($url)->assertOk();
    $this->get($url . 'x')->assertStatus(403);
    $this->get(route('fanart.discord-image', $fanart->id))->assertStatus(403);

    $expired = URL::temporarySignedRoute('fanart.discord-image', now()->subMinute(), ['fanartId' => $fanart->id]);
    $this->get($expired)->assertStatus(403);
});

it('does not serve the signed image once the fanart is no longer eligible', function () {
    $author = alice($this);
    $public = publicStory('Histoire', $author->id);
    $fanart = sharedImage(['story_id' => $public->id, 'user_id' => $author->id]);
    $url = fn () => URL::temporarySignedRoute('fanart.discord-image', now()->addDay(), ['fanartId' => $fanart->id]);

    $fanart->update(['share_on_discord' => false]);
    $this->get($url())->assertNotFound();

    $fanart->update(['share_on_discord' => true]);
    $this->get($url())->assertOk();

    $public->update(['visibility' => 'private']);
    $this->get($url())->assertNotFound();
});

it('drops a deleted fanart from pending', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = sharedImage(['story_id' => $story->id, 'user_id' => $author->id]);
    $url = botGetPending($this)->json('data.0.imageUrl');

    $fanart->delete();

    botGetPending($this)->assertOk()->assertJsonCount(0, 'data');
    $this->get($url)->assertNotFound();
});

it('exposes the fanartist id, slug and avatar url', function () {
    $author = alice($this);
    $bob = bob($this);
    $story = publicStory('Histoire', $author->id);
    sharedImage(['story_id' => $story->id, 'user_id' => $bob->id]);

    $artist = botGetPending($this)->assertOk()->json('data.0.artist');

    expect($artist['id'])->toBe($bob->id)
        ->and($artist['slug'])->toBe(app(\App\Domains\Shared\Contracts\ProfilePublicApi::class)->getPublicProfile($bob->id)->slug)
        ->and($artist['avatarUrl'])->toBeString()->not->toBe('');
});

it('lists every author of the story, co-authors included', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id, 'author');
    sharedImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);

    $authors = collect(botGetPending($this)->assertOk()->json('data.0.authors'));

    $profiles = app(\App\Domains\Shared\Contracts\ProfilePublicApi::class)->getPublicProfiles([$author->id, $coAuthor->id]);
    expect($authors)->toHaveCount(2);
    foreach ([$author, $coAuthor] as $user) {
        $entry = $authors->firstWhere('id', $user->id);
        expect($entry)->not->toBeNull()
            ->and($entry['name'])->toBe($profiles[$user->id]->display_name)
            ->and($entry['slug'])->toBe($profiles[$user->id]->slug);
    }
});

it('sends the chapter slug only for a chapter fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $withChapter = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id]);
    $storyLevel = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'published_at' => now()->addMinute()]);

    $data = collect(botGetPending($this)->assertOk()->json('data'));

    expect($data->firstWhere('id', $withChapter->id)['chapterSlug'])->toBe($chapter->slug)
        ->and(array_key_exists('chapterSlug', $data->firstWhere('id', $storyLevel->id)))->toBeFalse();
});

it('sends the description as plain text and omits it when empty', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $with = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'description' => '<p>Un <strong>beau</strong> dessin &amp; plus</p><p>Fin</p>']);
    $without = sharedImage(['story_id' => $story->id, 'user_id' => $author->id, 'description' => null, 'published_at' => now()->addMinute()]);

    $data = collect(botGetPending($this)->assertOk()->json('data'));

    expect($data->firstWhere('id', $with->id)['description'])->toBe("Un beau dessin & plus\nFin")
        ->and(array_key_exists('description', $data->firstWhere('id', $without->id)))->toBeFalse();
});

it('exposes the approval date of a reader fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $approvedAt = now()->subDays(2)->startOfSecond();
    sharedImage(['story_id' => $story->id, 'user_id' => bob($this)->id, 'published_at' => $approvedAt]);

    $item = botGetPending($this)->assertOk()->json('data.0');

    expect(\Illuminate\Support\Carbon::parse($item['approvedAt'])->equalTo($approvedAt))->toBeTrue();
});

it('uses the submission date as approval date for an author artwork', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $this->travelTo(now()->subDay()->startOfSecond());
    $submittedAt = now();

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), [
            'kind' => 'image',
            'title' => 'Mon artwork',
            'attestation' => '1',
            'share_on_discord' => '1',
            'image' => ['file' => \Illuminate\Http\UploadedFile::fake()->image('art.jpg', 1200, 800)],
        ])->assertRedirect();
    $this->travelBack();

    $item = botGetPending($this)->assertOk()->json('data.0');

    expect(\Illuminate\Support\Carbon::parse($item['approvedAt'])->equalTo($submittedAt))->toBeTrue();
});
