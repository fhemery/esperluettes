<?php

use App\Domains\StaticPage\Private\Models\StaticPage;
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

    it('accepts an image block without alt text', function () {
        Storage::disk('public')->put('static-pages/x.jpg', 'x');
        $page = staticPageSvc()->create(staticPageData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'static-pages/x.jpg', 'alt' => '']],
        ]));

        expect($page->content_blocks[0])->toMatchArray([
            'type' => 'image',
            'path' => 'static-pages/x.jpg',
            'alt' => '',
        ]);
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

describe('Static page advanced mode — form errors', function () {
    it('shows an image block error in the blocks error area', function () {
        app()->setLocale('fr');

        $html = $this->actingAs(admin($this))
            ->from(route('static.admin.create'))
            ->followingRedirects()
            ->post(route('static.admin.store'), staticPageData([
                'mode' => 'advanced',
                'blocks_order' => 'b0',
                'blocks' => ['b0' => ['type' => 'image', 'file' => UploadedFile::fake()->create('doc.pdf', 10)]],
            ]))
            ->assertOk()
            ->getContent();

        // The field-level error list, not the layout's flash block (which lists every error).
        expect($html)->toMatch('#<ul class="text-sm text-red-600[^"]*">\s*<li>Le fichier doit être une image\.</li>#u');
        expect($html)->not->toContain('validation.');
        expect(StaticPage::query()->count())->toBe(0);
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

function staticSoundPayload(array $block): array
{
    return staticPageData([
        'mode' => 'advanced',
        'blocks_order' => 'b0',
        'blocks' => ['b0' => array_merge(['type' => 'audio'], $block)],
    ]);
}

function staticMp3(int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->create('song.mp3', $kb, 'audio/mpeg');
}

describe('Static page advanced mode — sound blocks', function () {
    it('stores an uploaded sound on the public disk and renders a player', function () {
        $this->actingAs(admin($this))
            ->post(route('static.admin.store'), staticSoundPayload(['sound' => staticMp3()]))
            ->assertSessionHasNoErrors();

        $page = StaticPage::query()->latest('id')->firstOrFail();
        $block = $page->content_blocks[0];
        expect($block['type'])->toBe('audio');
        expect($block['path'])->toStartWith('static-pages/')->toEndWith('.mp3');
        Storage::disk('public')->assertExists($block['path']);
        expect($page->content)->toContain('<audio');
    });

    it('rejects a non-mp3 sound', function () {
        app()->setLocale('fr');
        $this->actingAs(admin($this))
            ->post(route('static.admin.store'), staticSoundPayload(['sound' => UploadedFile::fake()->create('a.wav', 10, 'audio/wav')]))
            ->assertSessionHasErrors(['blocks.b0.sound' => __('static::admin.validation.audio_type')]);

        expect(StaticPage::query()->count())->toBe(0);
    });

    it('rejects a sound block without a file', function () {
        app()->setLocale('fr');
        $this->actingAs(admin($this))
            ->post(route('static.admin.store'), staticSoundPayload(['caption' => 'x']))
            ->assertSessionHasErrors(['blocks' => __('static::admin.validation.audio_required')]);

        expect(StaticPage::query()->count())->toBe(0);
    });

    it('keeps the stored sound on a round trip without re-upload', function () {
        $page = staticPageSvc()->create(staticSoundPayload(['sound' => staticMp3()]));
        $path = $page->content_blocks[0]['path'];

        $this->actingAs(admin($this))
            ->put(route('static.admin.update', $page), staticSoundPayload(['path' => $path, 'caption' => 'c']) + ['slug' => $page->slug])
            ->assertSessionHasNoErrors();

        expect($page->refresh()->content_blocks[0]['path'])->toBe($path);
    });

    it('rejects a sound path outside the static-pages folder', function () {
        foreach (['fanart/1/x.mp3', 'static-pages/x.jpg', 'static-pages/../fanart/x.mp3'] as $path) {
            $this->actingAs(admin($this))
                ->post(route('static.admin.store'), staticSoundPayload(['path' => $path]))
                ->assertSessionHasErrors('blocks');
        }

        expect(StaticPage::query()->count())->toBe(0);
    });

    it('refuses switching to simple mode while a sound block exists', function () {
        $page = staticPageSvc()->create(staticSoundPayload(['sound' => staticMp3()]));

        $this->actingAs(admin($this))
            ->put(route('static.admin.update', $page), staticPageData(['slug' => $page->slug, 'mode' => 'simple', 'content' => '<p>x</p>']))
            ->assertSessionHasErrors('mode');

        expect($page->refresh()->content_blocks[0]['type'])->toBe('audio');
    });

    it('shows the sound player to a guest on a published page', function () {
        $page = staticPageSvc()->create(staticSoundPayload(['sound' => staticMp3()]));
        staticPageSvc()->publish($page);

        auth()->logout();
        $this->get('/' . $page->slug)
            ->assertOk()
            ->assertSee('<audio controls', false);
    });
});
