<?php

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

function artworkImagePayload(array $overrides = []): array
{
    return array_merge([
        'kind' => 'image',
        'title' => 'Mon artwork',
        'description' => '<p>Un <strong>joli</strong> dessin</p>',
        'attestation' => '1',
        'image' => ['file' => UploadedFile::fake()->image('art.jpg', 1200, 800)],
    ], $overrides);
}

function credits(int $userId): int
{
    return app(StoryPublicApi::class)->getAvailableChapterCredits($userId);
}

function artwork(array $overrides = []): Fanart
{
    return createPublishedImage(array_merge(['is_artwork' => true], $overrides));
}

it('publishes an author image immediately and spends one credit of the uploader', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $before = credits($author->id);

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), artworkImagePayload())
        ->assertRedirect(route('stories.show', $story->slug))
        ->assertSessionHas('success');

    $fanart = Fanart::first();
    expect($fanart->status)->toBe(FanartStatus::Published)
        ->and($fanart->is_artwork)->toBeTrue()
        ->and($fanart->user_id)->toBe($author->id)
        ->and($fanart->published_at)->not->toBeNull()
        ->and(credits($author->id))->toBe($before - 1);
    Storage::disk('private')->assertExists($fanart->file_path);
});

it('shows the artwork create page with the credit warning to an author', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $this->actingAs($author)
        ->get(route('fanart.create', [$story->slug, 'image']))
        ->assertOk()
        ->assertSee(__('fanart::ui.create.artwork_heading', ['story' => 'Histoire']), false)
        ->assertSee(trans_choice('fanart::ui.create.credit_warning', 5, ['count' => 5]), false)
        ->assertSee('data-kind="image"', false);
});

it('refuses the upload with the no-credit message and stores nothing at zero credits', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    DB::table('story_chapter_credits')->where('user_id', $author->id)
        ->update(['credits_gained' => 3, 'credits_spent' => 3]);

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), artworkImagePayload())
        ->assertSessionHasErrors('credit');

    expect(Fanart::count())->toBe(0)
        ->and(credits($author->id))->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('does not notify co-authors', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), artworkImagePayload())
        ->assertSessionHasNoErrors();

    expect(DB::table('notifications')->count())->toBe(0);
});

it('replaces the chapter audio when an author uploads an audio artwork', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $old = createPendingAudio([
        'story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $reader->id,
        'status' => FanartStatus::Published, 'published_at' => now(),
    ]);

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), [
            'kind' => 'audio',
            'title' => 'Ma lecture',
            'chapter_id' => $chapter->id,
            'attestation' => '1',
            'sound' => UploadedFile::fake()->create('lecture.mp3', 100, 'audio/mpeg'),
        ])
        ->assertSessionHasNoErrors();

    expect(Fanart::withTrashed()->find($old->id))->toBeNull()
        ->and(Fanart::where('kind', FanartKind::Audio)->where('status', FanartStatus::Published)->count())->toBe(1);
});

it('lets the author edit title, description, chapter and file for free', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $fanart = artwork(['story_id' => $story->id, 'user_id' => $author->id]);
    $oldPath = $fanart->file_path;
    $before = credits($author->id);

    $this->actingAs($author)->get(route('fanart.edit', $fanart->id))->assertOk()->assertSee('Un fanart');

    $this->actingAs($author)
        ->put(route('fanart.update', $fanart->id), [
            'title' => 'Nouveau titre',
            'description' => '<p>Nouvelle</p>',
            'chapter_id' => $chapter->id,
            'image' => ['file' => UploadedFile::fake()->image('new.png', 900, 900)],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $fresh = $fanart->fresh();
    expect($fresh->title)->toBe('Nouveau titre')
        ->and($fresh->chapter_id)->toBe($chapter->id)
        ->and($fresh->description)->toContain('Nouvelle')
        ->and($fresh->file_path)->not->toBe($oldPath)
        ->and(credits($author->id))->toBe($before);
    Storage::disk('private')->assertExists($fresh->file_path);
});

it('keeps the file when an edit sends none', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = artwork(['story_id' => $story->id, 'user_id' => $author->id]);
    $path = $fanart->file_path;

    $this->actingAs($author)
        ->put(route('fanart.update', $fanart->id), ['title' => 'Autre'])
        ->assertSessionHasNoErrors();

    expect($fanart->fresh()->file_path)->toBe($path)->and($fanart->fresh()->title)->toBe('Autre');
});

it('replaces the target chapter audio when an audio artwork changes chapter', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $chapterA = createPublishedChapter($this, $story, $author);
    $chapterB = createPublishedChapter($this, $story, $author);
    $other = createPendingAudio([
        'story_id' => $story->id, 'chapter_id' => $chapterB->id, 'user_id' => $reader->id,
        'status' => FanartStatus::Published, 'published_at' => now(),
    ]);
    $mine = createPendingAudio([
        'story_id' => $story->id, 'chapter_id' => $chapterA->id, 'user_id' => $author->id,
        'status' => FanartStatus::Published, 'is_artwork' => true,
    ]);

    $this->actingAs($author)
        ->put(route('fanart.update', $mine->id), ['title' => 'Lecture', 'chapter_id' => $chapterB->id])
        ->assertSessionHasNoErrors();

    expect(Fanart::find($other->id))->toBeNull()
        ->and($mine->fresh()->chapter_id)->toBe($chapterB->id);
});

it('returns 403 on edit to a co-author who did not upload it, and to the fanartist of a fanart', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);
    $artwork = artwork(['story_id' => $story->id, 'user_id' => $author->id]);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $reader->id]);

    $this->actingAs($coAuthor);
    $this->get(route('fanart.edit', $artwork->id))->assertForbidden();
    $this->put(route('fanart.update', $artwork->id), ['title' => 'X'])->assertForbidden();

    $this->actingAs($reader);
    $this->get(route('fanart.edit', $fanart->id))->assertForbidden();
    $this->put(route('fanart.update', $fanart->id), ['title' => 'X'])->assertForbidden();

    $this->actingAs($author);
    $this->get(route('fanart.edit', $fanart->id))->assertForbidden();

    expect($artwork->fresh()->title)->toBe('Un fanart');
});

it('returns 403 on upload to an author of a private story', function () {
    $author = alice($this);
    $story = privateStory('Secrete', $author->id);

    $this->actingAs($author);
    $this->get(route('fanart.create', [$story->slug, 'image']))->assertForbidden();
    $this->post(route('fanart.store', $story->slug), artworkImagePayload())->assertForbidden();

    expect(Fanart::count())->toBe(0);
});

it('does not refund the credit on deletion', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $before = credits($author->id);

    $this->actingAs($author)->post(route('fanart.store', $story->slug), artworkImagePayload());
    Fanart::first()->forceDelete();

    expect(credits($author->id))->toBe($before - 1);
});

it('leaves the credit unspent when storing the file fails (transaction rollback)', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $before = credits($author->id);

    $media = Mockery::mock(MediaPublicApi::class);
    $media->shouldReceive('storePrivate')->andThrow(new RuntimeException('disk full'));
    app()->instance(MediaPublicApi::class, $media);
    app()->forgetInstance(FanartService::class);

    $data = new App\Domains\Fanart\Private\Support\SubmitFanartData(
        FanartKind::Image,
        UploadedFile::fake()->image('a.jpg'),
        'T',
        null,
        null,
        false,
    );

    expect(fn () => app(FanartService::class)->uploadArtwork($author->id, $story->id, $data))
        ->toThrow(RuntimeException::class);

    expect(credits($author->id))->toBe($before)->and(Fanart::count())->toBe(0);
});

it('ignores a message sent with an author artwork upload', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);

    $this->actingAs($author)
        ->post(route('fanart.store', $story->slug), artworkImagePayload(['message' => 'Note secrète']))
        ->assertRedirect();

    expect(Fanart::count())->toBe(1)
        ->and(Fanart::first()->status)->toBe(FanartStatus::Published)
        ->and(DB::table('notifications')->count())->toBe(0)
        ->and(json_encode(DB::table('fanarts')->first()))->not->toContain('Note secrète');
});
