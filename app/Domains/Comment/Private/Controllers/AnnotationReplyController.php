<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Comment\Private\Requests\StoreAnnotationReplyRequest;
use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AnnotationReplyController extends Controller
{
    public function __construct(private readonly AnnotationPublicApi $api)
    {
    }

    public function store(StoreAnnotationReplyRequest $request, int $annotationId): JsonResponse
    {
        $reply = $this->api->reply($annotationId, (int) Auth::id(), (string) $request->validated('body'));

        return response()->json($reply->toArray(), 201);
    }

    public function destroy(int $replyId): Response
    {
        $this->api->deleteOwnReply($replyId, (int) Auth::id());

        return response()->noContent();
    }
}
