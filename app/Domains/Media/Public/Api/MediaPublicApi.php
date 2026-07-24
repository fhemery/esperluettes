<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Api;

use App\Domains\Media\Private\Services\MediaService;
use App\Domains\Media\Public\Contracts\Dto\MediaPathPageDto;
use Illuminate\Http\UploadedFile;

/**
 * Sole entry point other domains use for managed images.
 * Images are addressed by storage path — no ids, no reference table.
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
     * List original images under a scope for the reuse picker (non-recursive).
     */
    public function listByScope(string $scope, int $page = 1, int $perPage = 40): MediaPathPageDto
    {
        return $this->media->listByScope($scope, $page, $perPage);
    }

    /**
     * Absolute URL of a responsive variant for a stored path.
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
}
