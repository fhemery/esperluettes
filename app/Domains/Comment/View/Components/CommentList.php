<?php

namespace App\Domains\Comment\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentListDto;

class CommentList extends Component
{
    public CommentListDto $comments;
    public ?string $error = null;
    public bool $isGuest = false;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public int $perPage = 20
    ) {
        $api = app(CommentPublicApi::class);
        $this->isGuest = !auth()->check();
        try {
            $this->comments = $api->getFor($this->entityType, $this->entityId, 1, $this->perPage);
        } catch (\Throwable $e) {
            // If listing is not allowed (unauthenticated or unauthorized), mark error and provide an empty list
            $this->error = 'not_allowed';
            // Provide a safe empty list object
            $this->comments = CommentListDto::empty(
                entityType: $this->entityType,
                entityId: (string) $this->entityId,
                page: 1,
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
        ]);
    }
}
