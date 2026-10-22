<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/** Bot-facing endpoints (`discord.api` middleware) and the signed image URL the bot embeds. */
class DiscordFeedController
{
    public function __construct(
        private readonly FanartService $fanarts,
        private readonly MediaPublicApi $media,
    ) {
    }

    public function pending(Request $request): JsonResponse
    {
        $perPage = max(min((int) $request->query('perPage', 100), 100), 1);
        $page = max((int) $request->query('page', 1), 1);

        ['paginator' => $paginator, 'items' => $items] = $this->fanarts->discordPending($perPage, $page);

        return response()->json([
            'data' => $items,
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
                'hasMore' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function markSent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['present', 'array'],
            'ids.*' => ['integer'],
        ]);

        return response()->json([
            'success' => true,
            'markedCount' => $this->fanarts->markDiscordSent($validated['ids']),
        ]);
    }

    /** The 800w variant, reachable by signature only (the bot has no session). */
    public function image(int $fanartId): Response
    {
        $fanart = $this->fanarts->isDiscordEligible($fanartId) ? $this->fanarts->find($fanartId) : null;
        abort_if($fanart === null, 404);

        try {
            return $this->media->streamVariant($fanart->file_path, 800, 'webp', ['Cache-Control' => 'private, max-age=3600']);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }
}
