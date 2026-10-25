<?php

declare(strict_types=1);

namespace App\Domains\Media\Private\Services;

use App\Domains\Media\Public\Contracts\Dto\MediaPathDto;
use App\Domains\Media\Public\Contracts\Dto\MediaPathPageDto;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
     * Scope roots living on the private disk: no public URL, and bytes (original
     * or opt-in variants) reachable only by streaming through the owning
     * domain's own gate.
     */
    private const PRIVATE_SCOPE_ROOTS = ['secret-gift', 'fanart', 'moderation'];

    /** Generated variant file name: {name}-{width}w.{ext}. */
    private const VARIANT_PATTERN = '/-\d+w\.(jpg|jpeg|png|webp)$/i';

    /** Public-disk originals the reuse picker lists: images only. */
    private const PUBLIC_IMAGE_PATTERN = '/\.(jpg|jpeg|png|webp)$/i';

    /** Public-disk originals GC sweeps: images plus raw sounds from storePublicFile(). */
    private const PUBLIC_GC_PATTERN = '/\.(jpg|jpeg|png|webp|mp3)$/i';

    public function __construct(
        private readonly ImageService $imageService,
        private readonly MediaUsageRegistry $registry,
    ) {}

    /**
     * Resolve a scope string to its base folder on the disk.
     * Per-author chapter scopes ("chapters/{userId}") are already folder paths,
     * as are the private scopes ("secret-gift/{activityId}", "fanart/{storyId}",
     * "moderation/{topicKey}").
     */
    public function folderFor(string $scope): string
    {
        if (in_array($scope, self::FLAT_SCOPES, true)) {
            return $scope;
        }
        if (preg_match('#^chapters/\d+$#', $scope) === 1) {
            return $scope;
        }
        if (preg_match('#^(secret-gift|fanart)/\d+$#', $scope) === 1) {
            return $scope;
        }
        if (preg_match('#^moderation/[a-z0-9_-]+$#', $scope) === 1) {
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
     * Defaults to the original only; pass widths to opt into private variants,
     * which are served by streamVariant() behind the caller's own gate.
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
     * Store an uploaded file's raw bytes on the private disk; returns its path.
     * No processing and no content validation. The extension comes from the
     * guessed MIME type (hashName()), never from the client file name.
     */
    public function storePrivateFile(string $scope, UploadedFile $file): string
    {
        if (!$this->isPrivateScope($scope)) {
            throw new InvalidArgumentException("Not a private media scope: {$scope}");
        }
        return Storage::disk(self::PRIVATE_DISK)->putFileAs($this->folderFor($scope), $file, $file->hashName());
    }

    /**
     * Store an uploaded file's raw bytes on the public disk; returns its path.
     * The public twin of storePrivateFile(): no processing, no variants and no
     * content validation. The extension comes from the guessed MIME type.
     * GC only sweeps image and .mp3 originals on the public disk.
     */
    public function storePublicFile(string $scope, UploadedFile $file): string
    {
        if ($this->isPrivateScope($scope)) {
            throw new InvalidArgumentException("Private scope must go through storePrivateFile(): {$scope}");
        }
        return Storage::disk(self::DISK)->putFileAs($this->folderFor($scope), $file, $file->hashName());
    }

    /**
     * Stream a stored file back, on whichever disk its path resolves to.
     * Performs **no** authorization: the caller has already decided the
     * requester may see these bytes.
     *
     * Private paths come back as a BinaryFileResponse, which answers Range
     * requests once the router prepares it. Its defaults are set here because
     * prepare() is what would otherwise fill them, and a direct call skips it.
     * Requires the private disk to be local (it needs an absolute path).
     */
    public function stream(string $path, array $headers = []): Response
    {
        if (!$this->isPrivatePath($path)) {
            return Storage::disk(self::DISK)->response($path, null, $headers);
        }

        return $this->privateFileResponse($path, $headers);
    }

    /**
     * Stream a generated variant of a **private** original, inline.
     * Performs **no** authorization, like stream(). Throws for a public path
     * (public variants have URLs) and for a variant that does not exist.
     */
    public function streamVariant(string $path, int $width, string $format = 'webp', array $headers = []): Response
    {
        if (!$this->isPrivatePath($path)) {
            throw new InvalidArgumentException("Not a private media path: {$path}");
        }
        $variant = $this->variantPath($path, $width, $format);
        if (!Storage::disk(self::PRIVATE_DISK)->exists($variant)) {
            throw new InvalidArgumentException("Missing media variant: {$variant}");
        }

        return $this->privateFileResponse($variant, $headers);
    }

    /**
     * Copy a stored original (public or private) into a private scope under a
     * fresh random name, keeping its extension; returns the new path.
     * Variants are not copied.
     */
    public function copyPrivate(string $path, string $targetScope): string
    {
        if (!$this->isPrivateScope($targetScope)) {
            throw new InvalidArgumentException("Not a private media scope: {$targetScope}");
        }
        $folder = $this->folderFor($targetScope);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $target = $folder . '/' . Str::random(40) . ($ext !== '' ? '.' . $ext : '');

        $stream = Storage::disk($this->diskFor($path))->readStream($path);
        if ($stream === null) {
            throw new InvalidArgumentException("Media file not found: {$path}");
        }
        try {
            Storage::disk(self::PRIVATE_DISK)->writeStream($target, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $target;
    }

    /**
     * BinaryFileResponse for a private-disk file. Defaults are set here because
     * prepare() is what would otherwise fill them, and a direct call skips it.
     */
    private function privateFileResponse(string $path, array $headers): Response
    {
        $disk = Storage::disk(self::PRIVATE_DISK);
        $response = new BinaryFileResponse($disk->path($path), 200, [], false);
        $response->headers->set('Content-Type', $disk->mimeType($path) ?: 'application/octet-stream');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($path));
        $response->headers->add($headers);

        return $response;
    }

    /** Variant path by naming convention: {dir}/{name}-{width}w.{format}. */
    private function variantPath(string $path, int $width, string $format): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        return ($dir === '' || $dir === '.') ? "{$name}-{$width}w.{$format}" : "{$dir}/{$name}-{$width}w.{$format}";
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
        return asset('storage/' . $this->variantPath($path, $width, $format));
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
        $originals = $this->originalsIn(self::DISK, $this->folderFor($scope), self::PUBLIC_IMAGE_PATTERN);

        // Newest first by mtime.
        usort($originals, fn (string $a, string $b) => $this->mtime(self::DISK, $b) <=> $this->mtime(self::DISK, $a));

        $offset = ($page - 1) * $perPage;
        $slice = array_slice($originals, $offset, $perPage);
        $items = array_map(
            function (string $path): MediaPathDto {
                // Keep-original uploads have no -400w/-800w files; pointing the
                // picker at a missing variant yields blank thumbs. Fall back to
                // the original so selection still shows a reachable preview.
                $url = $this->hasVariants($path)
                    ? $this->variantUrl($path, 400, 'webp')
                    : asset('storage/' . ltrim($path, '/'));

                return new MediaPathDto($path, $url);
            },
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
        return Storage::disk($this->diskFor($path))->exists($this->variantPath($path, 800, 'webp'));
    }

    /**
     * Garbage-collect originals no provider claims and older than $days.
     * On the private disk every file under a root except -<width>w variants
     * counts as an original; variants go with their original.
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
                $originals = $this->originalsIn($disk, $folder, self::PUBLIC_GC_PATTERN);
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
     * Originals under a folder. On the public disk: files matching
     * $publicPattern, variants excluded, non-recursive — callers pick the
     * pattern explicitly, PUBLIC_IMAGE_PATTERN for the reuse picker (which must
     * never offer a sound) and PUBLIC_GC_PATTERN for gc(). On the private disk
     * the pattern is ignored: every file (images or not) except generated
     * variants, recursively — private files live one level down in per-scope
     * subfolders. Variants are removed with their original by
     * deleteWithVariants().
     *
     * @return list<string>
     */
    private function originalsIn(string $disk, string $folder, string $publicPattern): array
    {
        $fs = Storage::disk($disk);
        if (!$fs->exists($folder)) {
            return [];
        }
        if ($disk === self::PRIVATE_DISK) {
            return array_values(array_filter(
                $fs->allFiles($folder),
                fn (string $file) => preg_match(self::VARIANT_PATTERN, basename($file)) !== 1,
            ));
        }
        $originals = [];
        foreach ($fs->files($folder) as $file) {
            $base = pathinfo($file, PATHINFO_BASENAME);
            if (preg_match(self::VARIANT_PATTERN, $base) === 1) {
                continue; // a generated variant
            }
            if (preg_match($publicPattern, $base) !== 1) {
                continue; // not a managed type for this caller
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
