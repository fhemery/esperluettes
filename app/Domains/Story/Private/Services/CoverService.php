<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class CoverService
{
    private const CUSTOM_COVER_DISK = 'public';
    private const CUSTOM_COVER_SMALL_WIDTH = 300;
    private const CUSTOM_COVER_SMALL_HEIGHT = 400;
    private const CUSTOM_COVER_HD_WIDTH = 800;
    private const CUSTOM_COVER_HD_HEIGHT = 1067;

    /**
     * Get the cover URL (small/standard version) for a story.
     */
    public function getCoverUrl(Story $story): string
    {
        return match ($story->cover_type) {
            Story::COVER_THEMED => $this->themedCoverUrl($story->cover_data),
            Story::COVER_CUSTOM => $this->customCoverUrl($story),
            default => asset('images/story/default-cover.svg'),
        };
    }

    /**
     * Get the HD cover URL for a story. Returns null when no HD version exists (e.g. default SVG).
     */
    public function getCoverHdUrl(Story $story): ?string
    {
        return match ($story->cover_type) {
            Story::COVER_THEMED => $this->themedCoverHdUrl($story->cover_data),
            Story::COVER_CUSTOM => $this->customCoverHdUrl($story),
            default => null,
        };
    }

    /**
     * Whether this cover type supports a lightbox (HD zoom on click).
     */
    public function isClickable(Story $story): bool
    {
        return match ($story->cover_type) {
            Story::COVER_DEFAULT => false,
            default => true,
        };
    }

    /**
     * Whether the story has a custom cover file on disk.
     */
    public function hasCustomCover(Story $story): bool
    {
        return Storage::disk(self::CUSTOM_COVER_DISK)->exists($this->customCoverPath($story));
    }

    /**
     * Get the public URL for the small custom cover (300px). Returns null if no file exists.
     */
    public function getCustomCoverUrl(Story $story): ?string
    {
        if (!$this->hasCustomCover($story)) {
            return null;
        }
        return Storage::disk(self::CUSTOM_COVER_DISK)->url($this->customCoverPath($story));
    }

    /**
     * Upload, resize and store a custom cover for the given story.
     * Generates both small (300px) and HD (900px) versions as JPG.
     */
    public function uploadCustomCover(Story $story, UploadedFile $file): void
    {
        $folder = $this->customCoverFolder($story);

        if (!Storage::disk(self::CUSTOM_COVER_DISK)->exists($folder)) {
            Storage::disk(self::CUSTOM_COVER_DISK)->makeDirectory($folder);
        }

        $sourcePath = $file->getRealPath();

        $small = Image::read($sourcePath)->cover(self::CUSTOM_COVER_SMALL_WIDTH, self::CUSTOM_COVER_SMALL_HEIGHT);
        Storage::disk(self::CUSTOM_COVER_DISK)->put(
            $this->customCoverPath($story),
            (string) $small->encodeByExtension('jpg', quality: 85)
        );

        $hd = Image::read($sourcePath)->cover(self::CUSTOM_COVER_HD_WIDTH, self::CUSTOM_COVER_HD_HEIGHT);
        Storage::disk(self::CUSTOM_COVER_DISK)->put(
            $this->customCoverHdPath($story),
            (string) $hd->encodeByExtension('jpg', quality: 85)
        );
    }

    /**
     * Delete custom cover files for the given story.
     */
    public function deleteCustomCover(Story $story): void
    {
        $disk = Storage::disk(self::CUSTOM_COVER_DISK);

        foreach ([$this->customCoverPath($story), $this->customCoverHdPath($story)] as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    /**
     * Get the themed cover URL for a given genre slug.
     */
    public function themedCoverUrl(?string $genreSlug): string
    {
        if (!$genreSlug) {
            return asset('images/story/default-cover.svg');
        }
        return asset("images/story/{$genreSlug}.jpg");
    }

    /**
     * Get the themed HD cover URL for a given genre slug.
     */
    public function themedCoverHdUrl(?string $genreSlug): ?string
    {
        if (!$genreSlug) {
            return null;
        }
        return asset("images/story/{$genreSlug}-hd.jpg");
    }

    private function customCoverFolder(Story $story): string
    {
        return 'covers/' . $story->id;
    }

    private function customCoverPath(Story $story): string
    {
        return $this->customCoverFolder($story) . '/cover.jpg';
    }

    private function customCoverHdPath(Story $story): string
    {
        return $this->customCoverFolder($story) . '/cover-hd.jpg';
    }

    private function customCoverUrl(Story $story): string
    {
        if ($this->hasCustomCover($story)) {
            return Storage::disk(self::CUSTOM_COVER_DISK)->url($this->customCoverPath($story));
        }
        return asset('images/story/default-cover.svg');
    }

    private function customCoverHdUrl(Story $story): ?string
    {
        $hdPath = $this->customCoverHdPath($story);
        if (Storage::disk(self::CUSTOM_COVER_DISK)->exists($hdPath)) {
            return Storage::disk(self::CUSTOM_COVER_DISK)->url($hdPath);
        }
        return null;
    }

    /**
     * Return genres that have a themed cover available.
     * Checks both has_cover flag AND file existence on disk.
     *
     * @param int[] $genreIds  Restrict to these genre IDs (e.g. the story's current genres)
     * @return array<int, array{id:int,slug:string,name:string}>
     */
    public function getAvailableThemedCovers(array $genreIds = []): array
    {
        /** @var StoryRefPublicApi $storyRefs */
        $storyRefs = app(StoryRefPublicApi::class);
        $allGenres = $storyRefs->getAllGenres(new StoryRefFilterDto(activeOnly: true));

        return $allGenres
            ->filter(function (GenreDto $g) use ($genreIds) {
                if (!$g->has_cover) {
                    return false;
                }
                if (!empty($genreIds) && !in_array($g->id, $genreIds, false)) {
                    return false;
                }
                return file_exists(public_path("images/story/{$g->slug}.jpg"));
            })
            ->map(fn (GenreDto $g) => ['id' => $g->id, 'slug' => $g->slug, 'name' => $g->name])
            ->values()
            ->all();
    }
}
