<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

function imagePayload(array $overrides = []): array
{
    return array_merge([
        'kind' => 'image',
        'title' => 'Mon dessin',
        'description' => '<p>Un <strong>joli</strong> dessin</p>',
        'attestation' => '1',
        'image' => ['file' => UploadedFile::fake()->image('art.jpg', 1200, 800)],
    ], $overrides);
}

function audioPayload(int $chapterId, array $overrides = []): array
{
    return array_merge([
        'kind' => 'audio',
        'title' => 'Ma lecture',
        'chapter_id' => $chapterId,
        'attestation' => '1',
        'sound' => UploadedFile::fake()->create('lecture.mp3', 100, 'audio/mpeg'),
    ], $overrides);
}

function grantAudioRole(int $storyId, int $userId): void
{
    Fanartist::create(['story_id' => $storyId, 'user_id' => $userId, 'granted_by_user_id' => $userId]);
}

it('shows the create page with only the kinds the mode allows', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $reader = bob($this);

    // Defaults: images = yes, audio = designated (the reader is not a fanartiste).
    $this->actingAs($reader);
    $this->get(route('fanart.create', $story->slug))
        ->assertOk()
        ->assertSee('data-allowed-kinds="image"', false);

    grantAudioRole($story->id, $reader->id);
    $this->get(route('fanart.create', $story->slug))
        ->assertOk()
        ->assertSee('data-allowed-kinds="image,audio"', false);
});

it('stores a pending image with 400/800 variants and notifies every author', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);
    $reader = bob($this);

    $this->actingAs($reader)
        ->post(route('fanart.store', $story->slug), imagePayload())
        ->assertRedirect(route('stories.show', $story->slug))
        ->assertSessionHas('success');

    $fanart = Fanart::first();
    expect($fanart->status)->toBe(FanartStatus::Pending)
        ->and($fanart->kind)->toBe(FanartKind::Image)
        ->and($fanart->is_artwork)->toBeFalse()
        ->and($fanart->user_id)->toBe($reader->id)
        ->and($fanart->story_id)->toBe($story->id)
        ->and($fanart->chapter_id)->toBeNull()
        ->and($fanart->title)->toBe('Mon dessin')
        ->and($fanart->file_path)->toStartWith('fanart/' . $story->id . '/');

    Storage::disk('private')->assertExists($fanart->file_path);
    $this->actingAs($author)->get(route('fanart.thumb', [$fanart->id, 400]))->assertOk();
    $this->get(route('fanart.thumb', [$fanart->id, 800]))->assertOk();

    expect(countNotificationsByKey('fanart.submitted'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.submitted')->id))
        ->toEqualCanonicalizing([$author->id, $coAuthor->id]);
});

it('stores a pending audio and requires a chapter', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $reader = bob($this);
    grantAudioRole($story->id, $reader->id);

    $this->actingAs($reader)
        ->post(route('fanart.store', $story->slug), audioPayload($chapter->id, ['chapter_id' => null]))
        ->assertSessionHasErrors('chapter_id');
    expect(Fanart::count())->toBe(0);

    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id))
        ->assertRedirect(route('stories.show', $story->slug));

    $fanart = Fanart::first();
    expect($fanart->kind)->toBe(FanartKind::Audio)
        ->and($fanart->status)->toBe(FanartStatus::Pending)
        ->and($fanart->chapter_id)->toBe($chapter->id);
    Storage::disk('private')->assertExists($fanart->file_path);
});

it('accepts an image without chapter (Toute l\'oeuvre)', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs(bob($this))
        ->post(route('fanart.store', $story->slug), imagePayload(['chapter_id' => '']))
        ->assertRedirect();

    expect(Fanart::first()->chapter_id)->toBeNull();
});

it('rejects gif, oversize image, non-mp3 audio, oversize audio', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $reader = bob($this);
    grantAudioRole($story->id, $reader->id);
    $this->actingAs($reader);

    $this->post(route('fanart.store', $story->slug), imagePayload([
        'image' => ['file' => UploadedFile::fake()->create('a.gif', 10, 'image/gif')],
    ]))->assertSessionHasErrors('image.file');

    $this->post(route('fanart.store', $story->slug), imagePayload([
        'image' => ['file' => UploadedFile::fake()->image('big.jpg')->size(2049)],
    ]))->assertSessionHasErrors('image.file');

    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id, [
        'sound' => UploadedFile::fake()->create('a.wav', 10, 'audio/wav'),
    ]))->assertSessionHasErrors('sound');

    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id, [
        'sound' => UploadedFile::fake()->create('big.mp3', 15361, 'audio/mpeg'),
    ]))->assertSessionHasErrors('sound');

    $this->post(route('fanart.store', $story->slug), imagePayload(['image' => []]))
        ->assertSessionHasErrors('image.file');

    expect(Fanart::count())->toBe(0);
});

it('requires title and the attestation; caps description at 1000 visible chars', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $this->actingAs(bob($this));

    $this->from(route('fanart.create', $story->slug))
        ->post(route('fanart.store', $story->slug), imagePayload(['title' => '', 'attestation' => null]))
        ->assertRedirect(route('fanart.create', $story->slug))
        ->assertSessionHasErrors(['title', 'attestation'])
        ->assertSessionHasInput('description');

    $this->post(route('fanart.store', $story->slug), imagePayload([
        'description' => '<p>' . str_repeat('a', 1001) . '</p>',
    ]))->assertSessionHasErrors('description');

    $this->post(route('fanart.store', $story->slug), imagePayload([
        'description' => '<p><strong>' . str_repeat('a', 1000) . '</strong></p>',
    ]))->assertSessionHasNoErrors();

    expect(Fanart::count())->toBe(1);
});

it('sanitises the description HTML', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs(bob($this))
        ->post(route('fanart.store', $story->slug), imagePayload([
            'description' => '<p>Salut<script>alert(1)</script> <a href="http://x.y" onclick="x()">lien</a><strong>gras</strong></p>',
        ]))->assertRedirect();

    $description = Fanart::first()->description;
    expect($description)->not->toContain('<script')
        ->and($description)->not->toContain('<a ')
        ->and($description)->not->toContain('onclick')
        ->and($description)->toContain('<strong>gras</strong>');
});

it('rejects a chapter from another story', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $other = publicStory('Autre', $author->id);
    $foreign = createPublishedChapter($this, $other, $author);
    $reader = bob($this);
    grantAudioRole($story->id, $reader->id);
    $this->actingAs($reader);

    $this->post(route('fanart.store', $story->slug), audioPayload($foreign->id))
        ->assertSessionHasErrors('chapter_id');
    $this->post(route('fanart.store', $story->slug), imagePayload(['chapter_id' => $foreign->id]))
        ->assertSessionHasErrors('chapter_id');

    expect(Fanart::count())->toBe(0);
});

it('rejects an unpublished chapter of the story', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $draft = createUnpublishedChapter($this, $story, $author);

    $this->actingAs(bob($this))
        ->post(route('fanart.store', $story->slug), imagePayload(['chapter_id' => $draft->id]))
        ->assertSessionHasErrors('chapter_id');
});

it('returns 403 when the mode is no, or designated without the role', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $reader = bob($this);
    $this->actingAs($reader);

    // Audio defaults to designated and the reader is not a fanartiste.
    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id))->assertForbidden();

    expect(Fanart::count())->toBe(0);
});

it('returns 403 when both modes are no, even for a fanartiste', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $reader = bob($this);
    grantAudioRole($story->id, $reader->id);
    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'no');
    setSettingsValue($author->id, 'stories', 'fanart-audio-mode', 'no');
    $this->actingAs($reader);

    $this->post(route('fanart.store', $story->slug), imagePayload())->assertForbidden();
    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id))->assertForbidden();
    $this->get(route('fanart.create', $story->slug))->assertForbidden();

    expect(Fanart::count())->toBe(0);
});

it('returns 403 on a private story', function () {
    $author = alice($this);
    $story = privateStory('Secrète', $author->id);
    $this->actingAs(bob($this));

    $this->get(route('fanart.create', $story->slug))->assertForbidden();
    $this->post(route('fanart.store', $story->slug), imagePayload())->assertForbidden();
});

it('returns 403 to an author, 404 for an unknown story', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $this->actingAs($author);

    $this->post(route('fanart.store', $story->slug), imagePayload())->assertForbidden();
    $this->get('/stories/inconnue-999999/fanarts/create')->assertNotFound();
    $this->get('/stories/sans-id/fanarts/create')->assertNotFound();
});

it('turns away a non-confirmed user and redirects a guest', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->post(route('fanart.store', $story->slug), imagePayload())->assertRedirect(route('login'));

    // The convention `role` middleware answers a non-confirmed user with a redirect, never a body.
    $this->actingAs(bob($this, roles: [Roles::USER]));
    $this->post(route('fanart.store', $story->slug), imagePayload())->assertRedirect();
    $this->get(route('fanart.create', $story->slug))->assertRedirect();

    expect(Fanart::count())->toBe(0);
});

it('ignores share_on_discord for audio', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $reader = bob($this);
    grantAudioRole($story->id, $reader->id);
    $this->actingAs($reader);

    $this->post(route('fanart.store', $story->slug), audioPayload($chapter->id, ['share_on_discord' => '1']))
        ->assertRedirect();
    expect(Fanart::first()->share_on_discord)->toBeFalse();

    $this->post(route('fanart.store', $story->slug), imagePayload(['share_on_discord' => '1']))->assertRedirect();
    expect(Fanart::where('kind', FanartKind::Image)->first()->share_on_discord)->toBeTrue();
});
