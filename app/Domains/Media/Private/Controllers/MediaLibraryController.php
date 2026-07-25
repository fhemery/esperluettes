<?php

declare(strict_types=1);

namespace App\Domains\Media\Private\Controllers;

use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MediaLibraryController
{
    public function __construct(
        private readonly MediaPublicApi $media,
    ) {}

    /**
     * List reusable images under a scope for the picker.
     * GET /media/library?scope=news&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $scope = (string) $request->query('scope', '');
        $page = max(1, (int) $request->query('page', 1));

        try {
            // folderFor validates the scope; unknown scopes are rejected.
            $this->media->folderFor($scope);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unknown scope'], 422);
        }

        return response()->json($this->media->listByScope($scope, $page)->toArray());
    }
}
