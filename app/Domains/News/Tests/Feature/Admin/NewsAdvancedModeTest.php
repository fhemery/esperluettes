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
        // A normal reused image has variants on disk → responsive rendering.
        Storage::disk('public')->put('news/sep-800w.webp', 'x');
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

    it('stores a keep-original image without variants and flags the block', function () {
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => [
                'type' => 'image',
                'file' => UploadedFile::fake()->image('small.png', 120, 90),
                'alt' => 'Small',
                'keep_original' => '1',
            ]],
        ]));

        $path = $news->content_blocks[0]['path'];
        expect($news->content_blocks[0]['keep_original'])->toBeTrue();
        Storage::disk('public')->assertExists($path);
        // No responsive variants were generated.
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        Storage::disk('public')->assertMissing("{$dir}/{$name}-800w.webp");
        // The rendered cache serves the original directly (no srcset).
        expect($news->content)->not->toContain('srcset');
    });

    it('forces raw rendering when reusing an image that has no variants', function () {
        Storage::disk('public')->put('news/nov.png', 'x'); // original only, no variants
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'news/nov.png', 'alt' => 'A']],
        ]));

        expect($news->content_blocks[0]['keep_original'])->toBeTrue();
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

    it('accepts an image block without alt text', function () {
        Storage::disk('public')->put('news/x.jpg', 'x');
        $news = newsSvc()->create(newsData([
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'image', 'path' => 'news/x.jpg', 'alt' => '']],
        ]));

        expect($news->content_blocks[0])->toMatchArray([
            'type' => 'image',
            'path' => 'news/x.jpg',
            'alt' => '',
        ]);
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

describe('News advanced mode — form errors', function () {
    function postNewsWithImageBlock(TestCase $t, UploadedFile $file): string
    {
        app()->setLocale('fr');

        return $t->actingAs(admin($t))
            ->from(route('news.admin.create'))
            ->followingRedirects()
            ->post(route('news.admin.store'), newsData([
                'mode' => 'advanced',
                'blocks_order' => 'b0',
                'blocks' => ['b0' => ['type' => 'image', 'file' => $file]],
            ]))
            ->assertOk()
            ->getContent();
    }

    it('shows an image block error in the blocks error area', function () {
        $html = postNewsWithImageBlock($this, UploadedFile::fake()->create('doc.pdf', 10));

        // The field-level error list, not the layout's flash block (which lists every error).
        expect($html)->toMatch('#<ul class="text-sm text-red-600[^"]*">\s*<li>Le fichier doit être une image\.</li>#u');
        expect($html)->not->toContain('validation.');
        expect(News::query()->count())->toBe(0);
    });

    it('states the size limit of an oversize image block in Ko', function () {
        $html = postNewsWithImageBlock($this, UploadedFile::fake()->image('big.jpg')->size(3000));

        expect($html)->toMatch('#<ul class="text-sm text-red-600[^"]*">\s*<li>Le fichier ne doit pas dépasser 2048 Ko\.</li>#u');
        expect($html)->not->toContain('validation.');
        expect(News::query()->count())->toBe(0);
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

describe('News advanced mode — HTTP request', function () {
    it('rejects a chapter-choice block', function () {
        $before = News::count();

        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), newsData([
                'mode' => 'advanced',
                'blocks_order' => 'b0',
                'blocks' => ['b0' => ['type' => 'chapter-choice', 'choices' => [['chapter_id' => 1]]]],
            ]))
            ->assertSessionHasErrors('blocks.b0.type');

        expect(News::count())->toBe($before);
    });
});

function soundPayload(array $block): array
{
    return newsData([
        'mode' => 'advanced',
        'blocks_order' => 'b0',
        'blocks' => ['b0' => array_merge(['type' => 'audio'], $block)],
    ]);
}

function mp3(int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->create('song.mp3', $kb, 'audio/mpeg');
}

describe('News advanced mode — sound blocks', function () {
    it('stores an uploaded sound on the public disk and renders a player', function () {
        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), soundPayload(['sound' => mp3()]))
            ->assertSessionHasNoErrors();

        $news = News::query()->latest('id')->firstOrFail();
        $block = $news->content_blocks[0];
        expect($block['type'])->toBe('audio');
        expect($block['path'])->toStartWith('news/')->toEndWith('.mp3');
        Storage::disk('public')->assertExists($block['path']);
        expect($news->content)->toContain('<audio');
    });

    it('keeps the sound caption', function () {
        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), soundPayload(['sound' => mp3(), 'caption' => '  Theme  ']))
            ->assertSessionHasNoErrors();

        $news = News::query()->latest('id')->firstOrFail();
        expect($news->content_blocks[0]['caption'])->toBe('Theme');
        expect($news->content)->toContain('Theme');
    });

    it('lets a moderator add a sound block', function () {
        $this->actingAs(moderator($this))
            ->post(route('news.admin.store'), soundPayload(['sound' => mp3()]))
            ->assertSessionHasNoErrors();

        expect(News::query()->count())->toBe(1);
    });

    it('rejects a plain user', function () {
        $this->actingAs(alice($this, [], true, [\App\Domains\Auth\Public\Api\Roles::USER]))
            ->post(route('news.admin.store'), soundPayload(['sound' => mp3()]))
            ->assertRedirect(route('dashboard'));

        expect(News::query()->count())->toBe(0);
    });

    it('rejects a non-mp3 sound', function () {
        app()->setLocale('fr');
        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), soundPayload(['sound' => UploadedFile::fake()->create('a.wav', 10, 'audio/wav')]))
            ->assertSessionHasErrors(['blocks.b0.sound' => __('news::admin.validation.audio_type')]);

        expect(News::query()->count())->toBe(0);
    });

    it('rejects a sound over 10 MB', function () {
        app()->setLocale('fr');
        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), soundPayload(['sound' => mp3(11000)]))
            ->assertSessionHasErrors(['blocks.b0.sound' => __('news::admin.validation.audio_max')]);

        expect(News::query()->count())->toBe(0);
    });

    it('rejects a sound block without a file', function () {
        app()->setLocale('fr');
        $this->actingAs(admin($this))
            ->post(route('news.admin.store'), soundPayload(['caption' => 'x']))
            ->assertSessionHasErrors(['blocks' => __('news::admin.validation.audio_required')]);

        expect(News::query()->count())->toBe(0);
    });

    it('keeps the stored sound on a round trip without re-upload', function () {
        $news = newsSvc()->create(soundPayload(['sound' => mp3()]));
        $path = $news->content_blocks[0]['path'];

        $this->actingAs(admin($this))
            ->put(route('news.admin.update', $news), soundPayload(['path' => $path, 'caption' => 'c']) + ['slug' => $news->slug])
            ->assertSessionHasNoErrors();

        expect($news->refresh()->content_blocks[0]['path'])->toBe($path);
    });

    it('rejects a sound removed without a replacement', function () {
        $news = newsSvc()->create(soundPayload(['sound' => mp3()]));
        $path = $news->content_blocks[0]['path'];

        $this->actingAs(admin($this))
            ->put(route('news.admin.update', $news), soundPayload(['path' => $path, 'sound_remove' => 'true']) + ['slug' => $news->slug])
            ->assertSessionHasErrors('blocks');

        expect($news->refresh()->content_blocks[0]['path'])->toBe($path);
    });

    it('replaces the sound with a new upload', function () {
        $news = newsSvc()->create(soundPayload(['sound' => mp3()]));
        $old = $news->content_blocks[0]['path'];

        $this->actingAs(admin($this))
            ->put(route('news.admin.update', $news), soundPayload(['path' => $old, 'sound_remove' => 'true', 'sound' => mp3()]) + ['slug' => $news->slug])
            ->assertSessionHasNoErrors();

        $new = $news->refresh()->content_blocks[0]['path'];
        expect($new)->not->toBe($old);
        Storage::disk('public')->assertExists($new);
    });

    it('rejects a sound path outside the news folder', function () {
        foreach (['fanart/1/x.mp3', 'news/x.jpg', 'news/../fanart/x.mp3'] as $path) {
            $this->actingAs(admin($this))
                ->post(route('news.admin.store'), soundPayload(['path' => $path]))
                ->assertSessionHasErrors('blocks');
        }

        expect(News::query()->count())->toBe(0);
    });

    it('refuses switching to simple mode while a sound block exists', function () {
        $news = newsSvc()->create(soundPayload(['sound' => mp3()]));

        $this->actingAs(admin($this))
            ->put(route('news.admin.update', $news), newsData(['slug' => $news->slug, 'mode' => 'simple', 'content' => '<p>x</p>']))
            ->assertSessionHasErrors('mode');

        expect($news->refresh()->content_blocks[0]['type'])->toBe('audio');
    });

    it('shows the sound player to a guest on a published news', function () {
        $news = newsSvc()->create(soundPayload(['sound' => mp3()]) + ['published_at' => now()->subMinute()]);
        $news->update(['status' => 'published']);

        auth()->logout();
        $this->get(route('news.show', ['slug' => $news->slug]))
            ->assertOk()
            ->assertSee('<audio controls', false);
    });
});

describe('NewsMediaUsageProvider — sounds', function () {
    it('claims sound block paths', function () {
        newsSvc()->create(soundPayload(['path' => 'news/abc.mp3']));

        $paths = iterator_to_array((new NewsMediaUsageProvider())->usedPaths(), false);

        expect($paths)->toContain('news/abc.mp3');
    });
});
