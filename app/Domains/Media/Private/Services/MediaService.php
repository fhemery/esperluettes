<?php

declare(strict_types=1);

namespace App\Domains\Media\Private\Services;

use App\Domains\Media\Public\Contracts\Dto\MediaPathDto;
use App\Domains\Media\Public\Contracts\Dto\MediaPathPageDto;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Path-addressed image domain: storage, variant URLs, reuse listing and GC.
 * An image's identity is its storage path; there is no id and no reference table.
 */
class MediaService
{
    public const DISK = 'public';

    /** Flat scopes that map 1:1 to a folder of the same name. */
    private const FLAT_SCOPES = ['news', 'faq', 'static-pages', 'profile', 'calendar'];

    public function __construct(
        private readonly ImageService $imageService,
        private readonly MediaUsageRegistry $registry,
    ) {}

    /**
     * Resolve a scope string to its base folder on the disk.
     * Per-author chapter scopes ("chapters/{userId}") are already folder paths.
     */
    public function folderFor(string $scope): string
    {
        if (in_array($scope, self::FLAT_SCOPES, true)) {
            return $scope;
        }
        if (preg_match('#^chapters/\d+$#', $scope) === 1) {
            return $scope;
        }
        throw new InvalidArgumentException("Unknown media scope: {$scope}");
    }

    /**
     * Store an uploaded image under the scope's folder and return its path.
     * Does not track usage — the path becomes "used" once the caller persists it.
     *
     * @param int[] $widths
     */
    public function store(string $scope, UploadedFile $file, array $widths = [400, 800]): string
    {
        return $this->imageService->process(self::DISK, $this->folderFor($scope), $file, $widths);
    }

    /**
     * Build a variant URL from an original path by naming convention.
     */
    public function variantUrl(string $path, int $width, string $format = 'webp'): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $rel = ($dir === '' || $dir === '.') ? "{$name}-{$width}w.{$format}" : "{$dir}/{$name}-{$width}w.{$format}";
        return asset('storage/' . $rel);
    }

    /**
     * List original images directly under a scope's folder (non-recursive),
     * excluding generated -<width>w variants. Paginated, newest first.
     */
    public function listByScope(string $scope, int $page = 1, int $perPage = 40): MediaPathPageDto
    {
        $page = max(1, $page);
        $originals = $this->originalsIn($this->folderFor($scope));

        // Newest first by mtime.
        usort($originals, fn (string $a, string $b) => $this->mtime($b) <=> $this->mtime($a));

        $offset = ($page - 1) * $perPage;
        $slice = array_slice($originals, $offset, $perPage);
        $items = array_map(
            fn (string $path) => new MediaPathDto($path, $this->variantUrl($path, 400, 'webp')),
            $slice,
        );

        return new MediaPathPageDto(
            items: $items,
            page: $page,
            hasMore: ($offset + $perPage) < count($originals),
        );
    }

    public function countUsages(string $path): int
    {
        return $this->registry->countUsages($path);
    }

    /**
     * Garbage-collect originals no provider claims and older than $days.
     *
     * Safety guard: a folder that holds originals but has zero claimed paths is
     * treated as an unclaimed scope (probable missing provider) and skipped, not
     * emptied. So GC scheduled before a consumer's provider exists is harmless.
     *
     * @return array{deleted:list<string>, skipped:list<string>}
     */
    public function gc(int $days = 7, bool $dryRun = false): array
    {
        $live = $this->registry->liveSet();
        $cutoff = now()->subDays($days)->getTimestamp();

        $deleted = [];
        $skipped = [];

        foreach ($this->managedFolders() as $folder) {
            $originals = $this->originalsIn($folder);
            if ($originals === []) {
                continue;
            }

            $folderIsClaimed = false;
            foreach ($live as $claimedPath => $_) {
                if (str_starts_with($claimedPath, $folder . '/')) {
                    $folderIsClaimed = true;
                    break;
                }
            }
            if (!$folderIsClaimed) {
                $skipped[] = $folder;
                continue;
            }

            foreach ($originals as $path) {
                if (isset($live[$path])) {
                    continue;
                }
                if ($this->mtime($path) >= $cutoff) {
                    continue; // within grace window
                }
                if (!$dryRun) {
                    $this->imageService->deleteWithVariants(self::DISK, $path);
                }
                $deleted[] = $path;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * All folders GC may sweep: the flat scopes plus every per-author chapter folder.
     *
     * @return list<string>
     */
    private function managedFolders(): array
    {
        $disk = Storage::disk(self::DISK);
        $folders = [];
        foreach (self::FLAT_SCOPES as $folder) {
            if ($disk->exists($folder)) {
                $folders[] = $folder;
            }
        }
        if ($disk->exists('chapters')) {
            foreach ($disk->directories('chapters') as $sub) {
                $folders[] = $sub; // e.g. "chapters/123"
            }
        }
        return $folders;
    }

    /**
     * Original images directly under a folder (non-recursive), variants excluded.
     *
     * @return list<string>
     */
    private function originalsIn(string $folder): array
    {
        $disk = Storage::disk(self::DISK);
        if (!$disk->exists($folder)) {
            return [];
        }
        $originals = [];
        foreach ($disk->files($folder) as $file) {
            $base = pathinfo($file, PATHINFO_BASENAME);
            if (preg_match('/-\d+w\.(jpg|jpeg|png|webp)$/i', $base) === 1) {
                continue; // a generated variant
            }
            if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $base) !== 1) {
                continue; // not an image
            }
            $originals[] = $file;
        }
        return $originals;
    }

    private function mtime(string $path): int
    {
        return (int) Storage::disk(self::DISK)->lastModified($path);
    }
}
