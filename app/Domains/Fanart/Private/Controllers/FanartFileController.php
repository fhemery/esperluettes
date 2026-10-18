<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class FanartFileController
{
    private const THUMB_WIDTHS = [400, 800];
    private const CACHE_HEADERS = ['Cache-Control' => 'private, max-age=3600'];

    public function __construct(
        private readonly FanartPolicy $policy,
        private readonly MediaPublicApi $media,
    ) {
    }

    public function original(Fanart $fanart): Response
    {
        $this->authorizeStream($fanart);

        if (!$this->media->exists($fanart->file_path)) {
            abort(404);
        }

        return $this->media->stream($fanart->file_path, self::CACHE_HEADERS);
    }

    public function thumb(Fanart $fanart, int $width): Response
    {
        $this->authorizeStream($fanart);

        if ($fanart->kind !== FanartKind::Image || !in_array($width, self::THUMB_WIDTHS, true)) {
            abort(404);
        }

        try {
            return $this->media->streamVariant($fanart->file_path, $width, 'webp', self::CACHE_HEADERS);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }

    private function authorizeStream(Fanart $fanart): void
    {
        if (!$this->policy->streamFile(Auth::id(), $fanart)) {
            abort(403);
        }
    }
}
