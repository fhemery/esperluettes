<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AnnotationController extends Controller
{
    public function __construct(private readonly AnnotationPublicApi $api)
    {
    }

    public function index(int $commentId): JsonResponse
    {
        return response()->json(
            $this->api->getForComment($commentId, (int) Auth::id())->toArray()
        );
    }
}
