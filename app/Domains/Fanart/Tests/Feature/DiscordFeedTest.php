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

function botGetPending(TestCase $t, array $query = [])
{
    return $t->getJson('/api/discord/fanarts/pending?' . http_build_query($query), ['Authorization' => 'Bearer __test_api_key__']);
}

function sharedImage(array $overrides = []): Fanart
{
    return createPublishedImage(array_merge(['share_on_discord' => true], $overrides));
}

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
