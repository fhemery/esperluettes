<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->author = alice($this);
    $this->actingAs($this->author);
    $this->story = createStoryForAuthor($this->author->id, ['title' => 'Advanced Story']);
});

function advancedChapterPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Advanced Chapter',
        'mode' => 'advanced',
        'blocks_order' => 'b0',
        'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Du texte</p>']],
        'published' => '1',
    ], $overrides);
}

describe('Chapter advanced mode', function () {

    it('stores the blocks and the rendered content', function () {
        Storage::disk('public')->put('chapters/' . $this->author->id . '/sep.jpg', 'x');

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Premier paragraphe</p>'],
                'b1' => [
                    'type' => 'image',
                    'path' => 'chapters/' . $this->author->id . '/sep.jpg',
                    'alt' => 'Séparateur',
                    'caption' => 'Une légende',
                ],
            ],
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        expect($chapter->content_blocks)->toHaveCount(2);
        expect($chapter->content_blocks[0]['type'])->toBe('text');
        expect($chapter->content_blocks[1])->toMatchArray([
            'type' => 'image',
            'path' => 'chapters/' . $this->author->id . '/sep.jpg',
            'alt' => 'Séparateur',
        ]);
        expect($chapter->content)->toContain('Premier paragraphe');
        expect($chapter->content)->toContain('Une légende');
    });

    it('strips an external link inside a text block', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Voir <a href="https://evil.example.com/x">ici</a></p>']],
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        expect($chapter->content_blocks[0]['html'])->not->toContain('evil.example.com');
        expect($chapter->content_blocks[0]['html'])->toContain('ici');
        expect($chapter->content)->not->toContain('evil.example.com');
    });

    it('keeps an internal link inside a text block', function () {
        $internal = url('/stories/' . $this->story->slug);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Voir <a href="' . $internal . '">là</a></p>']],
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        expect($chapter->content_blocks[0]['html'])->toContain('href');
        expect($chapter->content)->toContain('href');
    });

    it('keeps alignment, spoiler and emoji classes through conversion', function () {
        $html = '<p class="ql-align-center">Centré</p>'
            . '<p><span class="ql-spoiler">Secret</span></p>'
            . '<p><span class="ql-custom-emoji-espersourire">:)</span></p>';

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'blocks' => ['b0' => ['type' => 'text', 'html' => $html]],
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        expect($chapter->content_blocks[0]['html'])->toContain('ql-align-center');
        expect($chapter->content_blocks[0]['html'])->toContain('ql-spoiler');
        expect($chapter->content_blocks[0]['html'])->toContain('ql-custom-emoji-espersourire');
    });

    it('rejects an image block without alt text', function () {
        Storage::disk('public')->put('chapters/' . $this->author->id . '/nude.jpg', 'x');

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Texte</p>'],
                'b1' => ['type' => 'image', 'path' => 'chapters/' . $this->author->id . '/nude.jpg', 'alt' => '   '],
            ],
        ]))->assertSessionHasErrors('blocks.b1.alt');

        expect(Chapter::query()->count())->toBe(0);
    });

    it('rejects an advanced save with zero blocks', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), [
            'title' => 'Empty Advanced',
            'mode' => 'advanced',
            'blocks_order' => '',
            'published' => '1',
        ])->assertSessionHasErrors('blocks');

        expect(Chapter::query()->count())->toBe(0);
    });

    it('still requires content in simple mode', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), [
            'title' => 'No Content',
            'mode' => 'simple',
            'content' => '',
            'published' => '1',
        ])->assertSessionHasErrors('content');

        expect(Chapter::query()->count())->toBe(0);
    });

    it('scopes an uploaded image to the acting user, never to the request', function () {
        $other = bob($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($this->author);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), advancedChapterPayload([
            'scope' => 'chapters/' . $other->id,
            'blocks' => ['b0' => [
                'type' => 'image',
                'scope' => 'chapters/' . $other->id,
                'file' => UploadedFile::fake()->image('p.jpg', 800, 600),
                'alt' => 'Une image',
            ]],
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();
        $path = $chapter->content_blocks[0]['path'];

        expect($path)->toStartWith('chapters/' . $this->author->id . '/');
        expect($path)->not->toContain('chapters/' . $other->id . '/');
        Storage::disk('public')->assertExists($path);
    });

    it('does not widen the gate for non-confirmed users', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Guarded']);

        $nonConfirmed = bob($this, roles: [Roles::USER]);
        $this->actingAs($nonConfirmed);

        $this->get(route('chapters.edit', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertRedirect(route('dashboard'));

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]), advancedChapterPayload())->assertRedirect(route('dashboard'));

        expect($chapter->fresh()->content_blocks)->toBeNull();
    });
});
