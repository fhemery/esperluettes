<?php

declare(strict_types=1);

namespace App\Domains\Media\Private\Services;

use App\Domains\Media\Public\Contracts\Dto\MediaPathDto;
use App\Domains\Media\Public\Contracts\Dto\MediaPathPageDto;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Path-addressed image domain: storage, variant URLs, reuse listing and GC.
 * An image's identity is its storage path; there is no id and no reference table.
 */
class MediaService
{
    public const DISK = 'public';
    public const PRIVATE_DISK = 'private';

    /** Flat scopes that map 1:1 to a folder of the same name. */
    private const FLAT_SCOPES = ['news', 'faq', 'static-pages', 'activities'];

    /**
     * Scope roots living on the private disk: no public URL, no variants, and
     * bytes reachable only by streaming through the owning domain's own gate.
     */
    private const PRIVATE_SCOPE_ROOTS = ['secret-gift'];

    public function __construct(
        private readonly ImageService $imageService,
        private readonly MediaUsageRegistry $registry,
    ) {}

    /**
     * Resolve a scope string to its base folder on the disk.
     * Per-author chapter scopes ("chapters/{userId}") are already folder paths,
     * as are per-activity private scopes ("secret-gift/{activityId}").
     */
    public function folderFor(string $scope): string
    {
        if (in_array($scope, self::FLAT_SCOPES, true)) {
            return $scope;
        }
        if (preg_match('#^chapters/\d+$#', $scope) === 1) {
            return $scope;
        }
        if (preg_match('#^secret-gift/\d+$#', $scope) === 1) {
            return $scope;
        }
        throw new InvalidArgumentException("Unknown media scope: {$scope}");
    }

    public function isPrivateScope(string $scope): bool
    {
        return $this->isPrivatePath($scope);
    }

    /** A path is private when its first segment is a private scope root. */
    public function isPrivatePath(string $path): bool
    {
        $root = explode('/', ltrim($path, '/'))[0];
        return in_array($root, self::PRIVATE_SCOPE_ROOTS, true);
    }

    public function diskFor(string $scopeOrPath): string
    {
        return $this->isPrivatePath($scopeOrPath) ? self::PRIVATE_DISK : self::DISK;
    }

    /**
     * Store an uploaded image under the scope's folder and return its path.
     * Does not track usage — the path becomes "used" once the caller persists it.
     *
     * @param int[] $widths
     */
    public function store(string $scope, UploadedFile $file, array $widths = [400, 800]): string
    {
        if ($this->isPrivateScope($scope)) {
            throw new InvalidArgumentException("Private scope must go through storePrivate(): {$scope}");
        }
        return $this->imageService->process(self::DISK, $this->folderFor($scope), $file, $widths);
    }

    /**
     * Store an uploaded image on the private disk; returns its path.
     * Defaults to the original only — private images have no variants because
     * nothing can build a URL for them.
     *
     * @param int[] $widths
     */
    public function storePrivate(string $scope, UploadedFile $file, array $widths = []): string
    {
        if (!$this->isPrivateScope($scope)) {
            throw new InvalidArgumentException("Not a private media scope: {$scope}");
        }
        return $this->imageService->process(self::PRIVATE_DISK, $this->folderFor($scope), $file, $widths);
    }

    /**
     * Stream a stored file back, on whichever disk its path resolves to.
     * Performs **no** authorization: the caller has already decided the
     * requester may see these bytes.
     */
    public function stream(string $path, array $headers = []): StreamedResponse
    {
        return Storage::disk($this->diskFor($path))->response($path, null, $headers);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->diskFor($path))->exists($path);
    }

    /**
     * Save a square-cropped JPEG at a caller-chosen path on the managed disk.
     * Deliberately non-scoped: no variants, no GC — see MediaPublicApi.
     */
    public function saveSquareJpg(string $targetPath, UploadedFile $file, int $size = 200, int $quality = 85): string
    {
        return $this->imageService->saveSquareJpg(self::DISK, $targetPath, $file, $size, $quality);
    }

    /**
     * Build a variant URL from an original path by naming convention.
     * Throws for a private path: it has neither variants nor a public URL.
     */
    public function variantUrl(string $path, int $width, string $format = 'webp'): string
    {
        if ($this->isPrivatePath($path)) {
            throw new InvalidArgumentException("Private media path has no public URL: {$path}");
        }
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $rel = ($dir === '' || $dir === '.') ? "{$name}-{$width}w.{$format}" : "{$dir}/{$name}-{$width}w.{$format}";
        return asset('storage/' . $rel);
    }

    /**
     * List original images directly under a scope's folder (non-recursive),
     * excluding generated -<width>w variants. Paginated, newest first.
     * Private scopes are rejected: the picker would hand out unusable URLs.
     */
    public function listByScope(string $scope, int $page = 1, int $perPage = 40): MediaPathPageDto
    {
        if ($this->isPrivateScope($scope)) {
            throw new InvalidArgumentException("Private scope is not reusable: {$scope}");
        }

        $page = max(1, $page);
        $originals = $this->originalsIn(self::DISK, $this->folderFor($scope));

        // Newest first by mtime.
        usort($originals, fn (string $a, string $b) => $this->mtime(self::DISK, $b) <=> $this->mtime(self::DISK, $a));

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
     * Whether responsive variants were generated for this original (i.e. it was
     * not stored "keep original"). Used to decide raw vs responsive rendering
     * for a reused image, tableless.
     */
    public function hasVariants(string $path): bool
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $rel = ($dir === '' || $dir === '.') ? "{$name}-800w.webp" : "{$dir}/{$name}-800w.webp";
        return Storage::disk(self::DISK)->exists($rel);
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

        foreach ([self::DISK, self::PRIVATE_DISK] as $disk) {
            foreach ($this->managedFolders($disk) as $folder) {
                $originals = $this->originalsIn($disk, $folder);
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
                    if ($this->mtime($disk, $path) >= $cutoff) {
                        continue; // within grace window
                    }
                    if (!$dryRun) {
                        $this->imageService->deleteWithVariants($disk, $path);
                    }
                    $deleted[] = $path;
                }
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * All folders GC may sweep on a disk: on `public` the flat scopes plus every
     * per-author chapter folder; on `private` the scope roots themselves.
     *
     * A private root is one managed folder, not one per activity subfolder: the
     * zero-claim guard must sit where the provider does, or an activity whose
     * gifts were all removed would become permanently unsweepable.
     *
     * @return list<string>
     */
    private function managedFolders(string $disk): array
    {
        $fs = Storage::disk($disk);
        $folders = [];

        if ($disk === self::PRIVATE_DISK) {
            foreach (self::PRIVATE_SCOPE_ROOTS as $root) {
                if ($fs->exists($root)) {
                    $folders[] = $root;
                }
            }
            return $folders;
        }

        foreach (self::FLAT_SCOPES as $folder) {
            if ($fs->exists($folder)) {
                $folders[] = $folder;
            }
        }
        if ($fs->exists('chapters')) {
            foreach ($fs->directories('chapters') as $sub) {
                $folders[] = $sub; // e.g. "chapters/123"
            }
        }
        return $folders;
    }

    /**
     * Original images under a folder, variants excluded. Non-recursive on the
     * public disk; recursive under a private root, whose images live one level
     * down in per-scope subfolders.
     *
     * @return list<string>
     */
    private function originalsIn(string $disk, string $folder): array
    {
        $fs = Storage::disk($disk);
        if (!$fs->exists($folder)) {
            return [];
        }
        $files = $disk === self::PRIVATE_DISK ? $fs->allFiles($folder) : $fs->files($folder);
        $originals = [];
        foreach ($files as $file) {
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

    private function mtime(string $disk, string $path): int
    {
        return (int) Storage::disk($disk)->lastModified($path);
    }
}
