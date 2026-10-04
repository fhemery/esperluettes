<?php

namespace App\Domains\Comment\Public\View\Components;

use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Alpine root around an annotatable article, hosting the in-entity annotation
 * display. For a viewer with no in-entity role (guest, unsupported entity type)
 * it renders its slot and nothing else.
 */
class AnnotationOverlayComponent extends Component
{
    /** 'author' | 'commenter' | null */
    public ?string $inChapterRole = null;
    public ?int $userId = null;
    public string $inEntityUrl = '';

    public function __construct(
        private readonly CommentPolicyRegistry $policies,
        public string $entityType,
        public int $entityId,
        public ?int $rootCommentId = null,
        public string $annotationMode = 'draft',
    ) {
        $viewerId = Auth::id();
        if ($viewerId === null || !$this->policies->supportsAnnotations($this->entityType)) {
            return;
        }

        $this->userId = (int) $viewerId;
        if ($this->policies->canMarkAsProcessed($this->entityType, $this->entityId, $this->userId)) {
            $this->inChapterRole = 'author';
        } elseif ($this->policies->canAnnotate($this->entityType, $this->entityId, $this->userId)) {
            $this->inChapterRole = 'commenter';
        }

        if ($this->inChapterRole !== null) {
            $this->inEntityUrl = route('comments.annotations.in-entity');
        }
    }

    public function render(): ViewContract
    {
        return view('comment::components.annotation-overlay');
    }
}
