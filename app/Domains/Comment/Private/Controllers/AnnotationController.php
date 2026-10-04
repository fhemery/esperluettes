<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Comment\Private\Requests\SaveAnnotationChangesRequest;
use App\Domains\Comment\Private\Requests\SetAnnotationProcessedRequest;
use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use App\Domains\Comment\Public\Api\Contracts\AnnotationChangeSetDto;
use App\Domains\Comment\Public\Api\Contracts\AnnotationToCreateDto;
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

    public function save(SaveAnnotationChangesRequest $request, int $commentId): JsonResponse
    {
        $changes = new AnnotationChangeSetDto(
            adds: array_map(fn (array $add) => new AnnotationToCreateDto(
                body: (string) $add['body'],
                highlightedText: (string) $add['highlighted_text'],
                prefix: $add['prefix'] ?? null,
                suffix: $add['suffix'] ?? null,
                clientKey: (string) $add['key'],
            ), $request->validated('adds') ?? []),
            edits: collect($request->validated('edits') ?? [])
                ->mapWithKeys(fn (array $edit) => [(int) $edit['id'] => (string) $edit['body']])
                ->all(),
            deletes: array_map('intval', $request->validated('deletes') ?? []),
        );

        return response()->json(
            $this->api->saveChanges($commentId, (int) Auth::id(), $changes)->toArray()
        );
    }

    public function processed(SetAnnotationProcessedRequest $request, int $annotationId): JsonResponse
    {
        $value = $request->boolean('value');
        $this->api->setProcessed($annotationId, (int) Auth::id(), $value);

        return response()->json(['id' => $annotationId, 'is_processed' => $value]);
    }
}
