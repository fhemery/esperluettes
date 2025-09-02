<?php

namespace App\Domains\Comment\Http\Controllers;

use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommentController extends Controller
{
    public function __construct(private CommentPublicApi $api)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'body' => ['required', 'string'],
            'parent_comment_id' => ['nullable', 'integer'],
        ]);

        $dto = new CommentToCreateDto(
            entityType: $data['entity_type'],
            entityId: (int) $data['entity_id'],
            body: $data['body'],
            parentCommentId: $data['parent_comment_id'] ?? null,
        );

        $this->api->create($dto);

        return back()->with('status', 'comment-posted');
    }
}
