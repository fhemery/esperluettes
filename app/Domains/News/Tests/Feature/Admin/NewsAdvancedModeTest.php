<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\News\Private\Support\NewsMediaUsageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function newsSvc(): NewsService
{
    return app(NewsService::class);
}

function newsData(array $override = []): array
{
    return array_merge([
        'title' => 'Title ' . uniqid(),
        'slug' => 't-' . uniqid(),
        'summary' => 'Summary',
        'status' => 'draft',
    ], $override);
}

describe('News advanced mode — create', function () {
    it('persists blocks and renders the content cache', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Intro</p>'],
                'b1' => ['type' => 'image', 'path' => 'news/sep.jpg', 'alt' => 'Sep', 'caption' => 'Cap'],
            ],
        ]));

        expect($news->content_blocks)->toHaveCount(2);
        expect($news->content_blocks[0])->toMatchArray(['type' => 'text']);
        expect($news->content_blocks[1])->toMatchArray(['type' => 'image', 'path' => 'news/sep.jpg', 'alt' => 'Sep']);
        // content is the rendered cache.
        expect($news->content)->toContain('Intro');
        expect($news->content)->toContain('storage/news/sep-800w.webp');
        expect($news->content)->toContain('Cap');
    });

    it('stores uploaded image blocks through Media', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 800, 600), 'alt' => 'A']],
        ]));

        $path = $news->content_blocks[0]['path'];
        expect($path)->toStartWith('news/');
        Storage::disk('public')->assertExists($path);
    });

    it('drops empty blocks on save', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1,b2',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p><br></p>'],       // empty
                'b1' => ['type' => 'text', 'html' => '<p>Real</p>'],        // keep
                'b2' => ['type' => 'image', 'path' => '', 'alt' => ''],     // empty image
            ],
        ]));

        expect($news->content_blocks)->toHaveCount(1);
        expect($news->content_blocks[0]['html'])->toContain('Real');
    });

    it('respects the submitted block order', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b1,b0',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Second</p>'],
                'b1' => ['type' => 'text', 'html' => '<p>First</p>'],
            ],
        ]));

        expect($news->content_blocks[0]['html'])->toContain('First');
        expect($news->content_blocks[1]['html'])->toContain('Second');
    });

    it('rejects an image block without alt text', function () {
        expect(fn () => newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'news/x.jpg', 'alt' => '']],
        ])))->toThrow(ValidationException::class);
    });

    it('rejects advanced content with no surviving block', function () {
        expect(fn () => newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p></p>']],
        ])))->toThrow(ValidationException::class);
    });

    it('keeps simple mode unchanged (no blocks, sanitized content)', function () {
        $news = newsSvc()->create(newsData(['content' => '<p>Hello</p>']));

        expect($news->content_blocks)->toBeNull();
        expect($news->content)->toContain('Hello');
    });
});

describe('News advanced mode — round trip', function () {
    it('switches an advanced article back to simple', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Body</p>']],
        ]));
        expect($news->content_blocks)->not->toBeNull();

        newsSvc()->update($news, newsData([
            'slug' => $news->slug,
            'mode' => 'simple',
            'content' => '<p>Now simple</p>',
        ]));

        $news->refresh();
        expect($news->content_blocks)->toBeNull();
        expect($news->content)->toContain('Now simple');
    });
});

describe('NewsMediaUsageProvider', function () {
    it('reports header and block image paths, including a shared path twice', function () {
        newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'news/shared.jpg', 'alt' => 'A']],
        ]));
        newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'news/shared.jpg', 'alt' => 'B']],
        ]));
        News::create(newsData(['content' => '<p>x</p>', 'header_image_path' => 'news/2025/01/header.jpg']));

        $paths = iterator_to_array((function () {
            yield from (new NewsMediaUsageProvider())->usedPaths();
        })());

        expect(array_count_values($paths)['news/shared.jpg'])->toBe(2);
        expect($paths)->toContain('news/2025/01/header.jpg');
    });
});
