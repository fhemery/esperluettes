<?php

use App\Domains\StaticPage\Private\Services\StaticPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function staticPageSvc(): StaticPageService
{
    return app(StaticPageService::class);
}

function staticPageData(array $override = []): array
{
    return array_merge([
        'title' => 'Page ' . uniqid(),
        'slug' => 'p-' . uniqid(),
        'summary' => 'Résumé',
        'status' => 'draft',
    ], $override);
}

describe('Static page advanced mode — create', function () {
    it('persists blocks and renders the content cache', function () {
        // A normal reused image has variants on disk → responsive rendering.
        Storage::disk('public')->put('static-pages/sep-800w.webp', 'x');
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Intro</p>'],
                'b1' => ['type' => 'image', 'path' => 'static-pages/sep.jpg', 'alt' => 'Sep', 'caption' => 'Cap'],
            ],
        ]));

        expect($page->content_blocks)->toHaveCount(2);
        expect($page->content_blocks[0])->toMatchArray(['type' => 'text']);
        expect($page->content_blocks[1])->toMatchArray(['type' => 'image', 'path' => 'static-pages/sep.jpg', 'alt' => 'Sep']);
        expect($page->content)->toContain('Intro');
        expect($page->content)->toContain('storage/static-pages/sep-800w.webp');
        expect($page->content)->toContain('Cap');
    });

    it('stores uploaded image blocks through Media', function () {
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 800, 600), 'alt' => 'A']],
        ]));

        $path = $page->content_blocks[0]['path'];
        expect($path)->toStartWith('static-pages/');
        Storage::disk('public')->assertExists($path);
    });

    it('stores a keep-original image without variants and flags the block', function () {
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => [
                'type' => 'image',
                'file' => UploadedFile::fake()->image('small.png', 120, 90),
                'alt' => 'Small',
                'keep_original' => '1',
            ]],
        ]));

        $path = $page->content_blocks[0]['path'];
        expect($page->content_blocks[0]['keep_original'])->toBeTrue();
        Storage::disk('public')->assertExists($path);
        // No responsive variants were generated.
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        Storage::disk('public')->assertMissing("{$dir}/{$name}-800w.webp");
        // The rendered cache serves the original directly (no srcset).
        expect($page->content)->not->toContain('srcset');
    });

    it('forces raw rendering when reusing an image that has no variants', function () {
        Storage::disk('public')->put('static-pages/nov.png', 'x'); // original only, no variants
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'static-pages/nov.png', 'alt' => 'A']],
        ]));

        expect($page->content_blocks[0]['keep_original'])->toBeTrue();
    });

    it('drops empty blocks on save', function () {
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1,b2',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p><br></p>'],       // empty
                'b1' => ['type' => 'text', 'html' => '<p>Real</p>'],        // keep
                'b2' => ['type' => 'image', 'path' => '', 'alt' => ''],     // empty image
            ],
        ]));

        expect($page->content_blocks)->toHaveCount(1);
        expect($page->content_blocks[0]['html'])->toContain('Real');
    });

    it('respects the submitted block order', function () {
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b1,b0',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => '<p>Second</p>'],
                'b1' => ['type' => 'text', 'html' => '<p>First</p>'],
            ],
        ]));

        expect($page->content_blocks[0]['html'])->toContain('First');
        expect($page->content_blocks[1]['html'])->toContain('Second');
    });

    it('rejects an image block without alt text', function () {
        expect(fn () => staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'static-pages/x.jpg', 'alt' => '']],
        ])))->toThrow(ValidationException::class);
    });

    it('rejects advanced content with no surviving block', function () {
        expect(fn () => staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p></p>']],
        ])))->toThrow(ValidationException::class);
    });

    it('keeps simple mode unchanged (no blocks, sanitized content)', function () {
        $page = staticPageSvc()->create(staticPageData([
            'content' => '<p>Hello <a href="https://example.com">lien</a></p>',
        ]));

        expect($page->content_blocks)->toBeNull();
        expect($page->content)->toContain('Hello');
        expect($page->content)->toContain('target="_blank"');
    });
});

describe('Static page advanced mode — round trip', function () {
    it('switches an advanced page back to simple', function () {
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Body</p>']],
        ]));
        expect($page->content_blocks)->not->toBeNull();

        staticPageSvc()->update($page, staticPageData([
            'slug' => $page->slug,
            'mode' => 'simple',
            'content' => '<p>Now simple</p>',
        ]));

        $page->refresh();
        expect($page->content_blocks)->toBeNull();
        expect($page->content)->toContain('Now simple');
    });
});
