<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

describe('Chapter form and reading page under advanced mode', function () {

    function advancedChapter(TestCase $t, array $blocks): Chapter
    {
        $order = [];
        $payload = [];
        foreach (array_values($blocks) as $i => $block) {
            $uid = 'b' . $i;
            $order[] = $uid;
            $payload[$uid] = $block;
        }

        $t->post(route('chapters.store', ['storySlug' => $t->story->slug]), [
            'title' => 'Advanced Chapter',
            'mode' => 'advanced',
            'blocks_order' => implode(',', $order),
            'blocks' => $payload,
            'published' => '1',
        ])->assertRedirect();

        return Chapter::query()->latest('id')->firstOrFail();
    }

    it('reopens an advanced chapter in advanced mode', function () {
        $chapter = advancedChapter($this, [
            ['type' => 'text', 'html' => '<p>Premier bloc</p>'],
            ['type' => 'text', 'html' => '<p>Second bloc</p>'],
        ]);

        $html = $this->get(route('chapters.edit', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertOk()->getContent();

        // The MultiEdit mount, opened on the advanced pane by its stored blocks.
        expect($html)->toContain('multi-editor');
        expect($html)->toContain('name="mode"');
        expect($html)->toContain("mode: 'advanced'");
        // Both stored blocks are rendered, in order, with their HTML.
        expect($html)->toContain('name="blocks[b0][html]"');
        expect($html)->toContain('name="blocks[b1][html]"');
        expect($html)->toContain('Premier bloc');
        expect($html)->toContain('Second bloc');
        // Not the bare rich-text field the form used before.
        expect($html)->not->toContain('rich-text-chapter-content-editor');
    });

    it('reopens a simple chapter in simple mode', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author, [
            'content' => '<p>Du texte simple</p>',
        ]);

        $html = $this->get(route('chapters.edit', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertOk()->getContent();

        expect($html)->toContain('multi-editor');
        expect($html)->toContain("mode: 'simple'");
        expect($html)->toContain('Du texte simple');
        // No server-rendered block: the advanced pane is empty.
        expect($html)->not->toContain('name="blocks[b0][html]"');
    });

    it('opens a new chapter form in simple mode', function () {
        $html = $this->get(route('chapters.create', ['storySlug' => $this->story->slug]))
            ->assertOk()->getContent();

        expect($html)->toContain('multi-editor');
        expect($html)->toContain("mode: 'simple'");
        expect($html)->not->toContain('name="blocks[b0][html]"');
    });

    it('gives every text block fifteen lines and indented paragraphs', function () {
        $chapter = advancedChapter($this, [
            ['type' => 'text', 'html' => '<p>Premier bloc</p>'],
            ['type' => 'text', 'html' => '<p>Second bloc</p>'],
        ]);

        $html = $this->get(route('chapters.edit', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertOk()->getContent();

        // Two stored blocks + the hidden template new blocks are cloned from,
        // plus the simple pane — every writing surface gets the same props.
        expect(substr_count($html, 'data-nb-lines="15"'))->toBe(4);
        expect(substr_count($html, 'ql-indent'))->toBe(4);
    });

    it('re-renders the submitted blocks in order after a validation error', function () {
        Storage::disk('public')->put('chapters/' . $this->author->id . '/sep.jpg', 'x');

        $this->from(route('chapters.create', ['storySlug' => $this->story->slug]))
            ->post(route('chapters.store', ['storySlug' => $this->story->slug]), [
                'title' => 'Advanced Chapter',
                'mode' => 'advanced',
                'blocks_order' => 'n1,n0',
                'blocks' => [
                    'n0' => ['type' => 'text', 'html' => '<p>Deuxième</p>'],
                    'n1' => ['type' => 'text', 'html' => '<p>Premier</p>'],
                    'n2' => [
                        'type' => 'image',
                        'path' => 'chapters/' . $this->author->id . '/sep.jpg',
                        'alt' => '   ',
                    ],
                ],
                'published' => '1',
            ])->assertSessionHasErrors();

        $html = $this->get(route('chapters.create', ['storySlug' => $this->story->slug]))
            ->assertOk()->getContent();

        expect($html)->toContain("mode: 'advanced'");
        expect(strpos($html, 'Premier'))->toBeLessThan(strpos($html, 'Deuxième'));
    });

    it('keeps the author note a simple rich-text field', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author, [
            'author_note' => '<p>Une note</p>',
        ]);

        $html = $this->get(route('chapters.edit', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertOk()->getContent();

        expect($html)->toContain('rich-text-chapter-author-note-editor');
        expect($html)->toContain('name="author_note"');
    });

    it('prints advanced content in a single quote root', function () {
        Storage::disk('public')->put('chapters/' . $this->author->id . '/sep.jpg', 'x');

        $chapter = advancedChapter($this, [
            ['type' => 'text', 'html' => '<p>Avant l\'image</p>'],
            [
                'type' => 'image',
                'path' => 'chapters/' . $this->author->id . '/sep.jpg',
                'alt' => 'Séparateur',
            ],
            ['type' => 'text', 'html' => '<p>Après l\'image</p>'],
        ]);

        $html = $this->get(route('chapters.show', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]))->assertOk()->getContent();

        expect(substr_count($html, 'data-quote-article'))->toBe(1);

        $article = Str::between($html, 'data-quote-article', '</article>');
        expect($article)->toContain('Avant l\'image');
        expect($article)->toContain('Après l\'image');
        expect($article)->toContain('ce-block--image');
    });
});
