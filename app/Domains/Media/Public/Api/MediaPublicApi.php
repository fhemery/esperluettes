<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Api;

use App\Domains\Media\Private\Services\MediaService;
use App\Domains\Media\Public\Contracts\Dto\MediaPathPageDto;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sole entry point other domains use for managed images and private files.
 * Media is addressed by storage path — no ids, no reference table.
 *
 * Two halves: public images have variants and URLs (raw public files from
 * storePublicFile have a URL but no variants); private images
 * (storePrivate, optionally with variants) and raw private files
 * (storePrivateFile) have no URL and are only ever streamed back by the domain
 * that owns their visibility rules.
 */
class MediaPublicApi
{
    public function __construct(
        private readonly MediaService $media,
    ) {}

    /**
     * Store an uploaded image under the scope's folder; returns its stored path.
     *
     * @param int[] $widths
     */
    public function store(string $scope, UploadedFile $file, array $widths = [400, 800]): string
    {
        return $this->media->store($scope, $file, $widths);
    }

    /**
     * Store an uploaded image on the private disk; returns its stored path.
     *
     * A private image has **no URL**: nothing can link to it. The owning domain
     * serves it by calling stream() (original) or streamVariant() (opt-in
     * variants) from behind its own authorization check.
     *
     * @param int[] $widths Empty by default — the original only. e.g. [400, 800]
     *                      writes private -400w/-800w jpg + webp variants.
     */
    public function storePrivate(string $scope, UploadedFile $file, array $widths = []): string
    {
        return $this->media->storePrivate($scope, $file, $widths);
    }

    /**
     * Store an uploaded file's raw bytes on the private disk; returns its
     * stored path (extension guessed from the MIME type, not the client name).
     *
     * No processing, no URL, no variants and **no content validation** — the
     * caller validates the upload. Served back via stream(); collected by GC
     * like any private file once no provider claims it.
     */
    public function storePrivateFile(string $scope, UploadedFile $file): string
    {
        return $this->media->storePrivateFile($scope, $file);
    }

    /**
     * Store an uploaded file's raw bytes on the public disk under a public
     * scope; returns its stored path (extension guessed from the MIME type, not
     * the client name). Throws for a private scope.
     *
     * No processing, no variants and **no content validation** — the caller
     * validates the upload. URL via originalUrl(). GC sweeps only `.mp3` among
     * non-image public files, so a consumer storing one must claim it in its
     * MediaUsageProvider; any other extension would never be collected.
     */
    public function storePublicFile(string $scope, UploadedFile $file): string
    {
        return $this->media->storePublicFile($scope, $file);
    }

    /**
     * Stream a stored file back, resolving its disk from its path.
     *
     * Performs **no** authorization — the caller must already have decided the
     * requester may see these bytes. Supplied headers win over the defaults
     * (`Content-Type`, inline `Content-Disposition`).
     *
     * Private paths come back as a `BinaryFileResponse` that honours `Range`
     * (206, `Content-Range`, `Accept-Ranges: bytes`) once prepared by the
     * router — so audio/video seeking works without consumer code.
     */
    public function stream(string $path, array $headers = []): Response
    {
        return $this->media->stream($path, $headers);
    }

    /**
     * Stream a generated variant (`{name}-{width}w.{format}`) of a **private**
     * original stored with widths via storePrivate().
     *
     * Performs **no** authorization — the caller gates it exactly as for
     * stream(). Throws InvalidArgumentException for a public path (public
     * variants have URLs: use variantUrl()) and for a variant that was not
     * generated; the caller turns the latter into a 404.
     */
    public function streamVariant(string $path, int $width, string $format = 'webp', array $headers = []): Response
    {
        return $this->media->streamVariant($path, $width, $format, $headers);
    }

    /**
     * Copy a stored original (public or private) into a **private** scope under
     * a fresh random name keeping its extension; returns the new path. The
     * source is untouched and variants are not copied.
     *
     * Performs **no** authorization. The copy is an ordinary private file: the
     * caller must claim it through a MediaUsageProvider or GC collects it.
     */
    public function copyPrivate(string $path, string $targetScope): string
    {
        return $this->media->copyPrivate($path, $targetScope);
    }

    /**
     * Whether a stored file is present on the disk its path resolves to.
     */
    public function exists(string $path): bool
    {
        return $this->media->exists($path);
    }

    /**
     * Save a square-cropped JPEG at a caller-chosen path on the managed disk.
     *
     * The one deliberate exception to the scope invariant: the caller owns the
     * path *and* the file's lifecycle. Nothing is stored under a managed scope,
     * no responsive variants are generated, and the file is invisible to the
     * `media:gc` sweep — the caller must delete it itself. Profile avatars are
     * the only use case; anything garbage-collectable belongs in store().
     */
    public function saveSquareJpg(string $targetPath, UploadedFile $file, int $size = 200, int $quality = 85): string
    {
        return $this->media->saveSquareJpg($targetPath, $file, $size, $quality);
    }

    /**
     * List original images under a scope for the reuse picker (non-recursive).
     */
    public function listByScope(string $scope, int $page = 1, int $perPage = 40): MediaPathPageDto
    {
        return $this->media->listByScope($scope, $page, $perPage);
    }

    /**
     * Absolute URL of a responsive variant for a stored path.
     * Throws for a private path — it has no variants and no public URL.
     */
    public function variantUrl(string $path, int $width, string $format = 'webp'): string
    {
        return $this->media->variantUrl($path, $width, $format);
    }

    /**
     * Resolve a scope string to its base folder (validates the scope).
     */
    public function folderFor(string $scope): string
    {
        return $this->media->folderFor($scope);
    }

    /**
     * How many times a path is currently referenced across the whole app.
     */
    public function countUsages(string $path): int
    {
        return $this->media->countUsages($path);
    }

    /**
     * Whether responsive variants exist for a stored original (false for images
     * stored "keep original"). Lets a renderer choose raw vs responsive markup.
     */
    public function hasVariants(string $path): bool
    {
        return $this->media->hasVariants($path);
    }

    /**
     * Absolute URL of the stored original file (no resizing).
     * Throws for a private path: the bytes are not web-reachable, so a
     * /storage/… URL would be a broken image at best.
     */
    public function originalUrl(string $path): string
    {
        if ($this->media->isPrivatePath($path)) {
            throw new InvalidArgumentException("Private media path has no public URL: {$path}");
        }
        return asset('storage/' . ltrim($path, '/'));
    }
}
