<?php

namespace App\Domains\Comment\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentListDto;

class CommentList extends Component
{
    public CommentListDto $comments;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public int $perPage = 20
    ) {
        $api = app(CommentPublicApi::class);
        $this->comments = $api->getFor($this->entityType, $this->entityId, 1, $this->perPage);
    }

    public function render(): ViewContract
    {
        return view('comment::components.comment-list', [
            'list' => $this->comments,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
        ]);
    }
}
