<?php
declare(strict_types=1);

namespace App\Domains\Comment\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Private\Requests\UpdateCommentRequest;
use App\Domains\Comment\Private\Requests\StoreCommentRequest;
use App\Domains\Comment\Private\Requests\CommentFragmentRequest;
use App\Domains\Shared\Http\BackToCommentsRedirector;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function __construct(
        private CommentPublicApi $api, 
        private AuthPublicApi $authApi)
    {
    }

    public function store(StoreCommentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
    
            $dto = new CommentToCreateDto(
                entityType: $data['entity_type'],
                entityId: $data['entity_id'],
                body: $data['body'],
                parentCommentId: $data['parent_comment_id'],
            );
    
            $commentId = $this->api->create($dto);

            // Build the redirect URL and add comment query parameter before fragment
            $redirectUrl = BackToCommentsRedirector::build();
            // Remove #comments fragment and add it back after the comment parameter
            $redirectUrl = str_replace('#comments', '', $redirectUrl);
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'comment=' . $commentId . '#comments';

            return redirect()->to($redirectUrl)
                ->with('status', __('comment::comments.posted'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
        
    }

    public function update(UpdateCommentRequest $request, int $commentId): RedirectResponse
    {
        try {
            $data = $request->validated();
            $this->api->edit($commentId, $data['body']);

            return redirect()->to(BackToCommentsRedirector::build())
                ->with('status', __('comment::comments.updated'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Lazy load comments for a given entity type and id.
     */
    public function items(CommentFragmentRequest $request): Response
    {
        $data = $request->validated();

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 20);

        try {
            $list = $this->api->getFor($data['entity_type'], (int) $data['entity_id'], $page, $perPage);
        } catch (UnauthorizedException $e) {
            throw new HttpResponseException(response('', 401));
        }

        $total = $list->total;
        $lastPage = (int) max(1, (int) ceil($total / max(1, $list->perPage)));
        $nextPage = $page < $lastPage ? $page + 1 : null;

        $html = view('comment::fragments.items', [
            'items' => $list->items,
            'config' => $list->config,
            'isModerator' => $this->authApi->hasAnyRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]),
        ])->render();

        $response = new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
        if ($nextPage) {
            $response->headers->set('X-Next-Page', (string) $nextPage);
        }
        return $response;
    }
}
