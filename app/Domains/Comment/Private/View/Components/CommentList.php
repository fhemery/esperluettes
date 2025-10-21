<?php

namespace App\Domains\Comment\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentListDto;

class CommentList extends Component
{
    public CommentListDto $comments;
    public ?string $error = null;
    public bool $isGuest = false;

    public function __construct(
        private AuthPublicApi $authApi,
        public string $entityType,
        public int $entityId,
        public int $perPage = 5,
        public int $page = 1,
    ) {
        $api = app(CommentPublicApi::class);
        $this->isGuest = !auth()->check();
        try {
            // page <= 0 triggers lazy mode in PublicApi (config + total only)
            $this->comments = $api->getFor($this->entityType, $this->entityId, $this->page, $this->perPage);
        } catch (\Throwable $e) {
            // If listing is not allowed (unauthenticated or unauthorized), mark error and provide an empty list
            $this->error = 'not_allowed';
            // Provide a safe empty list object
            $this->comments = CommentListDto::empty(
                entityType: $this->entityType,
                entityId: $this->entityId,
                page: $this->page > 0 ? $this->page : 0,
                perPage: $this->perPage,
            );
        }
    }

    public function render(): ViewContract
    {
        return view('comment::components.comment-list', [
            'list' => $this->comments,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'error' => $this->error,
            'isGuest' => $this->isGuest,
            'isModerator' => $this->authApi->hasAnyRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]),
        ]);
    }
}
