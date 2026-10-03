<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AnnotationModerationController extends Controller
{
    public function __construct(private readonly AnnotationPublicApi $api)
    {
    }

    public function delete(int $annotationId): Response
    {
        $this->api->moderatorDelete($annotationId, (int) Auth::id());

        return response()->noContent();
    }
}
