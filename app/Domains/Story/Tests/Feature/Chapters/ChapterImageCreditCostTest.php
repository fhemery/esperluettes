<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Story\Public\Events\ChapterCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->author = alice($this);
    $this->actingAs($this->author);
    $this->story = createStoryForAuthor($this->author->id, ['title' => 'Credit Story']);
    $this->balance = fn (int $userId) => app(ChapterCreditService::class)->availableForUser($userId);
    $this->twoImagesPayload = fn () => [
        'title' => 'Chapitre illustré',
        'mode' => 'advanced',
        'blocks_order' => 'b0,b1,b2',
        'blocks' => [
            'b0' => ['type' => 'text', 'html' => '<p>Du texte</p>'],
            'b1' => ['type' => 'image', 'file' => UploadedFile::fake()->image('a.jpg', 800, 600), 'alt' => 'A'],
            'b2' => ['type' => 'image', 'file' => UploadedFile::fake()->image('b.jpg', 800, 600), 'alt' => 'B'],
        ],
        'published' => '1',
    ];
    $this->editUrl = fn (Chapter $chapter) => route('chapters.update', [
        'storySlug' => $this->story->slug,
        'chapterSlug' => $chapter->slug,
    ]);
});

describe('Chapter image credit cost', function () {

    it('spends one credit per uploaded image on top of the creation credit', function () {
        setAvailableCredits($this->author->id, 3);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), ($this->twoImagesPayload)())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(Chapter::query()->count())->toBe(1);
        expect(Storage::disk('public')->allFiles('chapters'))->not->toBeEmpty();
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('rejects a creation the balance cannot cover and stores nothing', function () {
        setAvailableCredits($this->author->id, 2);
        $createUrl = route('chapters.create', ['storySlug' => $this->story->slug]);

        $this->from($createUrl)
            ->post(route('chapters.store', ['storySlug' => $this->story->slug]), ($this->twoImagesPayload)())
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors(['credits'])
            ->assertSessionHasInput('title', 'Chapitre illustré');

        expect(session('errors')->first('credits'))
            ->toBe('story::validation.chapter.credits.insufficient');
        expect(Chapter::query()->count())->toBe(0);
        expect(Storage::disk('public')->allFiles('chapters'))->toBeEmpty();
        expect(($this->balance)($this->author->id))->toBe(2);
    });

    it('charges nothing for a library or already saved image', function () {
        $path = 'chapters/' . $this->author->id . '/lib.jpg';
        Storage::disk('public')->put($path, 'x');
        setAvailableCredits($this->author->id, 1);

        $payload = [
            'title' => 'Avec image de la bibliothèque',
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Du texte</p>'],
                'b1' => ['type' => 'image', 'path' => $path, 'alt' => 'Lib'],
            ],
            'published' => '1',
        ];

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        expect(($this->balance)($this->author->id))->toBe(0);

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        $this->put(($this->editUrl)($chapter), array_merge($payload, ['title' => 'Renommé']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($chapter->fresh()->title)->toBe('Renommé');
        expect($chapter->fresh()->content_blocks[1]['path'])->toBe($path);
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('spends one credit per uploaded image when editing', function () {
        $chapter = createUnpublishedChapter($this, $this->story, $this->author);
        setAvailableCredits($this->author->id, 2);

        $this->put(($this->editUrl)($chapter), ($this->twoImagesPayload)())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($chapter->fresh()->content_blocks)->toHaveCount(3);
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('rejects an edit the balance cannot cover and leaves the chapter untouched', function () {
        $chapter = createUnpublishedChapter($this, $this->story, $this->author);
        $before = $chapter->fresh();
        setAvailableCredits($this->author->id, 1);

        $this->put(($this->editUrl)($chapter), ($this->twoImagesPayload)())
            ->assertRedirect()
            ->assertSessionHasErrors(['credits']);

        expect(session('errors')->first('credits'))
            ->toBe('story::validation.chapter.credits.insufficient');
        $after = $chapter->fresh();
        expect($after->title)->toBe($before->title);
        expect($after->content)->toBe($before->content);
        expect($after->content_blocks)->toBeNull();
        expect(Storage::disk('public')->allFiles('chapters'))->toBeEmpty();
        expect(($this->balance)($this->author->id))->toBe(1);
    });

    it('keeps editing free when no image is uploaded', function () {
        $chapter = createUnpublishedChapter($this, $this->story, $this->author);
        setAvailableCredits($this->author->id, 0);

        updateChapter($this, $this->story, $chapter, ['title' => 'Texte seul'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($chapter->fresh()->title)->toBe('Texte seul');
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('ignores uploaded files in simple mode', function () {
        setAvailableCredits($this->author->id, 1);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), array_merge(
            ($this->twoImagesPayload)(),
            ['mode' => 'simple', 'content' => '<p>Texte simple</p>'],
        ))->assertRedirect()->assertSessionHasNoErrors();

        expect(Chapter::query()->count())->toBe(1);
        expect(Storage::disk('public')->allFiles('chapters'))->toBeEmpty();
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('ignores a file attached to a block missing from blocks_order', function () {
        setAvailableCredits($this->author->id, 2);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), array_merge(
            ($this->twoImagesPayload)(),
            ['blocks_order' => 'b0,b1'],
        ))->assertRedirect()->assertSessionHasNoErrors();

        expect(Chapter::query()->latest('id')->firstOrFail()->content_blocks)->toHaveCount(2);
        expect(($this->balance)($this->author->id))->toBe(0);
    });

    it('charges the acting co-author, not the story owner', function () {
        $chapter = createUnpublishedChapter($this, $this->story, $this->author);
        $coAuthor = bob($this);
        addCollaborator($this->story->id, $coAuthor->id);
        setAvailableCredits($this->author->id, 4);
        setAvailableCredits($coAuthor->id, 2);

        $this->actingAs($coAuthor)
            ->put(($this->editUrl)($chapter), ($this->twoImagesPayload)())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(($this->balance)($coAuthor->id))->toBe(0);
        expect(($this->balance)($this->author->id))->toBe(4);
        expect($chapter->fresh()->content_blocks[1]['path'])->toStartWith('chapters/' . $coAuthor->id . '/');
    });

    it('rolls back the chapter when the spend fails inside the transaction', function () {
        setAvailableCredits($this->author->id, 5);
        app()->instance(ChapterCreditService::class, new class (app(\App\Domains\Events\Public\Api\EventBus::class)) extends ChapterCreditService {
            public function trySpend(int $userId, int $amount): bool
            {
                return false;
            }
        });

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), ($this->twoImagesPayload)())
            ->assertRedirect()
            ->assertSessionHasErrors(['credits']);

        expect(Chapter::query()->count())->toBe(0);
        expect(countEvents(ChapterCreated::name()))->toBe(0);
        expect(($this->balance)($this->author->id))->toBe(5);
    });
});
