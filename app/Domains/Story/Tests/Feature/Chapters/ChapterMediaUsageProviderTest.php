<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Support\ChapterMediaUsageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->author = alice($this);
    $this->story = createStoryForAuthor($this->author->id, ['title' => 'Provider Story']);
    $this->folder = 'chapters/' . $this->author->id;
});

function makeChapter(int $storyId, array $attributes = []): Chapter
{
    static $n = 0;
    $n++;

    return Chapter::create(array_merge([
        'story_id' => $storyId,
        'title' => 'Chapter ' . $n,
        'slug' => 'chapter-' . $n,
        'content' => '<p>x</p>',
        'sort_order' => $n,
        'status' => Chapter::STATUS_PUBLISHED,
    ], $attributes));
}

/** @return list<string> */
function reportedPaths(): array
{
    return iterator_to_array((new ChapterMediaUsageProvider())->usedPaths(), false);
}

function imageBlock(string $path): array
{
    return ['type' => 'image', 'path' => $path, 'alt' => 'A'];
}

describe('ChapterMediaUsageProvider', function () {
    it('reports paths from published, draft and scheduled chapters', function () {
        makeChapter($this->story->id, [
            'status' => Chapter::STATUS_PUBLISHED,
            'content_blocks' => [imageBlock($this->folder . '/published.jpg')],
        ]);
        makeChapter($this->story->id, [
            'status' => Chapter::STATUS_NOT_PUBLISHED,
            'content_blocks' => [imageBlock($this->folder . '/draft.jpg')],
        ]);
        makeChapter($this->story->id, [
            'status' => Chapter::STATUS_NOT_PUBLISHED,
            'publish_at' => now()->addDay(),
            'content_blocks' => [imageBlock($this->folder . '/scheduled.jpg')],
        ]);

        expect(reportedPaths())->toContain(
            $this->folder . '/published.jpg',
            $this->folder . '/draft.jpg',
            $this->folder . '/scheduled.jpg',
        );
    });

    it('reports paths from soft deleted chapters', function () {
        $chapter = makeChapter($this->story->id, [
            'content_blocks' => [imageBlock($this->folder . '/trashed.jpg')],
        ]);
        $chapter->delete();

        expect($chapter->trashed())->toBeTrue();
        expect(reportedPaths())->toContain($this->folder . '/trashed.jpg');
    });

    it('reports a repeated image for each occurrence', function () {
        makeChapter($this->story->id, [
            'content_blocks' => [
                imageBlock($this->folder . '/shared.jpg'),
                ['type' => 'text', 'html' => '<p>Entre les deux</p>'],
                imageBlock($this->folder . '/shared.jpg'),
            ],
        ]);
        makeChapter($this->story->id, [
            'content_blocks' => [imageBlock($this->folder . '/shared.jpg')],
        ]);

        expect(array_count_values(reportedPaths())[$this->folder . '/shared.jpg'])->toBe(3);
    });

    it('ignores text blocks and blocks without a path', function () {
        makeChapter($this->story->id, [
            'content_blocks' => [
                ['type' => 'text', 'html' => '<p>Du texte</p>'],
                ['type' => 'image', 'alt' => 'Sans chemin'],
                ['type' => 'image', 'path' => '', 'alt' => 'Chemin vide'],
                imageBlock($this->folder . '/real.jpg'),
            ],
        ]);

        expect(reportedPaths())->toBe([$this->folder . '/real.jpg']);
    });

    it('ignores simple mode chapters without blocks', function () {
        makeChapter($this->story->id, ['content_blocks' => null]);

        expect(reportedPaths())->toBe([]);
    });
});

describe('media:gc with chapter images', function () {
    it('keeps live chapter images and sweeps orphans', function () {
        Storage::disk('public')->put($this->folder . '/live.jpg', 'x');
        Storage::disk('public')->put($this->folder . '/live-400w.jpg', 'x');
        Storage::disk('public')->put($this->folder . '/orphan.jpg', 'x');
        Storage::disk('public')->put($this->folder . '/orphan-400w.jpg', 'x');

        makeChapter($this->story->id, [
            'content_blocks' => [imageBlock($this->folder . '/live.jpg')],
        ]);

        // days=-1 forces every file past the grace window for the test.
        $this->artisan('media:gc', ['--days' => -1])->assertExitCode(0);

        Storage::disk('public')->assertExists($this->folder . '/live.jpg');
        Storage::disk('public')->assertExists($this->folder . '/live-400w.jpg');
        Storage::disk('public')->assertMissing($this->folder . '/orphan.jpg');
        Storage::disk('public')->assertMissing($this->folder . '/orphan-400w.jpg');
    });

    it('leaves a soft deleted chapters image alone', function () {
        Storage::disk('public')->put($this->folder . '/trashed.jpg', 'x');
        Storage::disk('public')->put($this->folder . '/live.jpg', 'x');

        makeChapter($this->story->id, [
            'content_blocks' => [imageBlock($this->folder . '/live.jpg')],
        ]);
        $chapter = makeChapter($this->story->id, [
            'content_blocks' => [imageBlock($this->folder . '/trashed.jpg')],
        ]);
        $chapter->delete();

        $this->artisan('media:gc', ['--days' => -1])->assertExitCode(0);

        Storage::disk('public')->assertExists($this->folder . '/trashed.jpg');
        Storage::disk('public')->assertExists($this->folder . '/live.jpg');
    });
});
