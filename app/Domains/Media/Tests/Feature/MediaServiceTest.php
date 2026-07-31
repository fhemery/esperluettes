<?php

use App\Domains\Media\Private\Services\MediaService;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// RefreshDatabase: registered consumer usage-providers (FAQ, News) query their
// tables during gc()/countUsages(), so the schema must exist.
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

/** A provider returning a fixed list of used paths (occurrences included). */
function fakeProvider(array $paths): MediaUsageProvider
{
    return new class($paths) implements MediaUsageProvider {
        public function __construct(private array $paths) {}
        public function usedPaths(): iterable { return $this->paths; }
    };
}

describe('folderFor', function () {
    it('maps flat scopes to same-named folders', function () {
        $svc = app(MediaService::class);
        expect($svc->folderFor('news'))->toBe('news');
        expect($svc->folderFor('faq'))->toBe('faq');
        expect($svc->folderFor('static-pages'))->toBe('static-pages');
        expect($svc->folderFor('activities'))->toBe('activities');
    });

    it('rejects the phantom scopes that never matched a folder', function () {
        $svc = app(MediaService::class);
        expect(fn () => $svc->folderFor('calendar'))->toThrow(InvalidArgumentException::class);
        expect(fn () => $svc->folderFor('profile'))->toThrow(InvalidArgumentException::class);
    });

    it('returns per-author chapter scope as-is', function () {
        expect(app(MediaService::class)->folderFor('chapters/42'))->toBe('chapters/42');
    });

    it('throws on an unknown scope', function () {
        expect(fn () => app(MediaService::class)->folderFor('bogus'))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('store', function () {
    it('stores an upload under the scope folder and generates variants', function () {
        $path = app(MediaPublicApi::class)->store('news', UploadedFile::fake()->image('photo.jpg', 1200, 800));

        expect($path)->toStartWith('news/');
        Storage::disk('public')->assertExists($path);

        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        Storage::disk('public')->assertExists("{$dir}/{$name}-400w.webp");
        Storage::disk('public')->assertExists("{$dir}/{$name}-800w.jpg");
    });
});

describe('saveSquareJpg', function () {
    it('writes at the given path on the managed disk as a square JPEG, with no variants', function () {
        $path = app(MediaPublicApi::class)->saveSquareJpg(
            'profile_pictures/7_1234567890.jpg',
            UploadedFile::fake()->image('avatar.png', 640, 480),
        );

        expect($path)->toBe('profile_pictures/7_1234567890.jpg');
        Storage::disk('public')->assertExists($path);

        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect($width)->toBe(200);
        expect($height)->toBe(200);
        expect($type)->toBe(IMAGETYPE_JPEG);

        // No responsive variant is generated next to it: this path is not managed.
        expect(Storage::disk('public')->files('profile_pictures'))
            ->toBe(['profile_pictures/7_1234567890.jpg']);
    });

    it('honours a custom size', function () {
        $path = app(MediaPublicApi::class)->saveSquareJpg(
            'profile_pictures/8.jpg',
            UploadedFile::fake()->image('avatar.jpg', 300, 300),
            size: 64,
        );

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect($width)->toBe(64);
        expect($height)->toBe(64);
    });
});

describe('variantUrl', function () {
    it('builds a variant URL by naming convention', function () {
        $url = app(MediaPublicApi::class)->variantUrl('news/sep-abc.jpg', 800, 'webp');
        expect($url)->toContain('storage/news/sep-abc-800w.webp');
    });

    it('handles nested (dated) paths of pre-migration images', function () {
        $api = app(MediaPublicApi::class);
        $path = 'news/2025/10/01K7E6PNMNHWFQ400B1T8SCF2Z.jpg';

        expect($api->variantUrl($path, 400, 'webp'))
            ->toContain('storage/news/2025/10/01K7E6PNMNHWFQ400B1T8SCF2Z-400w.webp');
        expect($api->variantUrl($path, 800, 'jpg'))
            ->toContain('storage/news/2025/10/01K7E6PNMNHWFQ400B1T8SCF2Z-800w.jpg');
    });
});

describe('listByScope', function () {
    it('lists originals under the scope, excluding variants', function () {
        Storage::disk('public')->put('news/a.jpg', 'x');
        Storage::disk('public')->put('news/a-400w.jpg', 'x');
        Storage::disk('public')->put('news/a-800w.webp', 'x');
        Storage::disk('public')->put('news/b.png', 'x');
        Storage::disk('public')->put('news/notes.txt', 'x');

        $page = app(MediaPublicApi::class)->listByScope('news');
        $paths = collect($page->items)->pluck('path')->all();

        expect($paths)->toHaveCount(2);
        expect($paths)->toContain('news/a.jpg');
        expect($paths)->toContain('news/b.png');
    });

    it('paginates and reports hasMore', function () {
        for ($i = 0; $i < 45; $i++) {
            Storage::disk('public')->put("news/img{$i}.jpg", 'x');
        }
        $page1 = app(MediaPublicApi::class)->listByScope('news', 1, 40);
        expect($page1->items)->toHaveCount(40);
        expect($page1->hasMore)->toBeTrue();

        $page2 = app(MediaPublicApi::class)->listByScope('news', 2, 40);
        expect($page2->items)->toHaveCount(5);
        expect($page2->hasMore)->toBeFalse();
    });
});

describe('countUsages', function () {
    it('sums occurrences across providers, including repeats', function () {
        $registry = app(MediaUsageRegistry::class);
        $registry->register(fakeProvider(['news/sep.jpg', 'news/sep.jpg', 'news/other.jpg']));
        $registry->register(fakeProvider(['news/sep.jpg']));

        expect(app(MediaPublicApi::class)->countUsages('news/sep.jpg'))->toBe(3);
        expect(app(MediaPublicApi::class)->countUsages('news/other.jpg'))->toBe(1);
        expect(app(MediaPublicApi::class)->countUsages('news/none.jpg'))->toBe(0);
    });
});

describe('gc', function () {
    it('deletes unclaimed files (with variants) past the grace window', function () {
        Storage::disk('public')->put('news/keep.jpg', 'x');
        Storage::disk('public')->put('news/gone.jpg', 'x');
        Storage::disk('public')->put('news/gone-400w.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['news/keep.jpg']));

        // days=-1 forces every file past the grace window for the test.
        $result = app(MediaService::class)->gc(-1);

        expect($result['deleted'])->toContain('news/gone.jpg');
        Storage::disk('public')->assertMissing('news/gone.jpg');
        Storage::disk('public')->assertMissing('news/gone-400w.jpg');
        Storage::disk('public')->assertExists('news/keep.jpg');
    });

    it('spares files within the grace window', function () {
        Storage::disk('public')->put('news/keep.jpg', 'x');
        Storage::disk('public')->put('news/fresh.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['news/keep.jpg']));

        $result = app(MediaService::class)->gc(7); // fresh files are < 7 days old

        expect($result['deleted'])->toBeEmpty();
        Storage::disk('public')->assertExists('news/fresh.jpg');
    });

    it('skips a whole scope with no registered provider (missing-provider guard)', function () {
        Storage::disk('public')->put('news/orphan.jpg', 'x');
        // No provider registered → the folder is unclaimed → skipped, not emptied.

        $result = app(MediaService::class)->gc(-1);

        expect($result['skipped'])->toContain('news');
        expect($result['deleted'])->toBeEmpty();
        Storage::disk('public')->assertExists('news/orphan.jpg');
    });

    it('never descends into dated subfolders of the activities scope', function () {
        Storage::disk('public')->put('activities/2026/07/legacy.jpg', 'x');
        Storage::disk('public')->put('activities/2026/07/legacy-400w.webp', 'x');
        // No provider claims anything.

        $result = app(MediaService::class)->gc(-1);

        expect($result['deleted'])->toBeEmpty();
        expect($result['skipped'])->not->toContain('activities');
        Storage::disk('public')->assertExists('activities/2026/07/legacy.jpg');
        Storage::disk('public')->assertExists('activities/2026/07/legacy-400w.webp');
    });

    it('skips the activities scope when a flat original has no provider', function () {
        Storage::disk('public')->put('activities/orphan.jpg', 'x');

        $result = app(MediaService::class)->gc(-1);

        expect($result['skipped'])->toContain('activities');
        expect($result['deleted'])->toBeEmpty();
        Storage::disk('public')->assertExists('activities/orphan.jpg');
    });

    it('garbage collects an unclaimed private gift image past the grace window', function () {
        Storage::disk('private')->put('secret-gift/7/keep.jpg', 'x');
        Storage::disk('private')->put('secret-gift/9/gone.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['secret-gift/7/keep.jpg']));

        $result = app(MediaService::class)->gc(-1);

        expect($result['deleted'])->toContain('secret-gift/9/gone.jpg');
        Storage::disk('private')->assertMissing('secret-gift/9/gone.jpg');
    });

    it('keeps a claimed private image', function () {
        Storage::disk('private')->put('secret-gift/7/keep.jpg', 'x');
        Storage::disk('private')->put('secret-gift/7/gone.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['secret-gift/7/keep.jpg']));

        $result = app(MediaService::class)->gc(-1);

        expect($result['deleted'])->not->toContain('secret-gift/7/keep.jpg');
        Storage::disk('private')->assertExists('secret-gift/7/keep.jpg');
    });

    it('keeps a private image inside the grace window', function () {
        Storage::disk('private')->put('secret-gift/7/keep.jpg', 'x');
        Storage::disk('private')->put('secret-gift/7/fresh.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['secret-gift/7/keep.jpg']));

        $result = app(MediaService::class)->gc(7);

        expect($result['deleted'])->toBeEmpty();
        Storage::disk('private')->assertExists('secret-gift/7/fresh.jpg');
    });

    it('skips the private root when no provider claims anything under it', function () {
        Storage::disk('private')->put('secret-gift/7/orphan.jpg', 'x');

        $result = app(MediaService::class)->gc(-1);

        expect($result['skipped'])->toContain('secret-gift');
        expect($result['deleted'])->toBeEmpty();
        Storage::disk('private')->assertExists('secret-gift/7/orphan.jpg');
    });

    it('collects an orphan in an activity folder whose other gifts are all gone', function () {
        // Activity 7 has no claimed path at all (a re-shuffle wiped its
        // assignments); activity 9 still does. The zero-claim guard applies at
        // the secret-gift root, so 7's orphan is still collectable.
        Storage::disk('private')->put('secret-gift/7/orphan.jpg', 'x');
        Storage::disk('private')->put('secret-gift/9/keep.jpg', 'x');
        app(MediaUsageRegistry::class)->register(fakeProvider(['secret-gift/9/keep.jpg']));

        $result = app(MediaService::class)->gc(-1);

        expect($result['deleted'])->toContain('secret-gift/7/orphan.jpg');
        Storage::disk('private')->assertMissing('secret-gift/7/orphan.jpg');
        Storage::disk('private')->assertExists('secret-gift/9/keep.jpg');
    });
});
