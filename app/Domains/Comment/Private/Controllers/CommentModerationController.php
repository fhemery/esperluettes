<?php

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Comment\Private\Services\CommentService;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;

class CommentModerationController extends Controller
{
    public function __construct(
        private CommentService $service,
        private AuthPublicApi $authApi)
    {
    }

    public function emptyContent(int $commentId): RedirectResponse
    {
        $this->service->emptyContentByModeration($commentId);
        return redirect()->back()->with('success', __('comment::moderation.empty_content.success'));
    }

    public function delete(int $commentId): RedirectResponse
    {
        $this->service->deleteByModeration($commentId);
        return redirect()->back()->with('success', __('comment::moderation.delete.success'));
    }
}