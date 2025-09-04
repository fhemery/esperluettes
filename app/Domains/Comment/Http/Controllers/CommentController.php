<?php

namespace App\Domains\Comment\Http\Controllers;

use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function __construct(private CommentPublicApi $api)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        try {
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

            // Redirect back to the previous page's path with a #comments anchor.
            // We avoid relying on the referrer's fragment since browsers don't send it.
            // Build a relative URL like ./path#comments as expected by tests.
            $previous = url()->previous(); // e.g. http://localhost/default/123?param=1#frag
            $base = preg_replace('/#.*/', '', $previous ?? ''); // strip fragment if any
            $path = parse_url($base, PHP_URL_PATH) ?: '/';
            $query = parse_url($base, PHP_URL_QUERY) ?: null;
            $relative = './' . ltrim($path, '/');
            $qs = $query ? ('?' . $query) : '';

            return redirect()->to($relative . $qs . '#comments')
                ->with('status', __('comment::comments.posted'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
        
    }
}
