<?php

namespace App\Domains\Comment\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentListDto;
use Illuminate\Support\Facades\Auth;

class CommentList extends Component
{
    public CommentListDto $comments;
    public ?string $error = null;
    public bool $isGuest = false;
    public ?int $targetCommentId = null;

    public function __construct(
        private readonly AuthPublicApi $authApi,
        private readonly CommentPublicApi $api,
        public string $entityType,
        public int $entityId,
        public int $perPage = 5,
        public int $page = 1,
    ) {
        $this->isGuest = !Auth::check();

        try {
            $this->preloadCommentToEnableDeepLinking();
            
            // Normal loading if no deep link or validation failed
            if (!$this->targetCommentId) {
                // page <= 0 triggers lazy mode in PublicApi (config + total only)
                $this->comments = $this->api->getFor($this->entityType, $this->entityId, $this->page, $this->perPage);
            }
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

    private function preloadCommentToEnableDeepLinking()
    {
        try {
            if ($this->isGuest) {
                return;
            }
            // Get comment ID from query parameter
            $targetCommentId = null;

            // Check if we have a comment query parameter
            if (request()->has('comment')) {
                $targetCommentId = (int) request('comment');
            }

            // Store for JavaScript access
            $this->targetCommentId = $targetCommentId;

            // If we have a target comment, validate and pre-load pages
            if (!$this->targetCommentId) {
                return;
            }
            $targetComment = $this->api->getComment($this->targetCommentId, withChildren: false);

            // Validate entity match
            if (
                $targetComment->entityType !== $this->entityType
                || $targetComment->entityId !== $this->entityId
            ) {
                $this->targetCommentId = null; // Invalid, ignore
                return;
            }
            // Load pages incrementally until we find the target root comment
            $currentPage = 1;
            $allItems = [];
            $found = false;

            while (!$found) {
                $pageDto = $this->api->getFor($this->entityType, $this->entityId, $currentPage, $this->perPage);
                $allItems = array_merge($allItems, $pageDto->items);

                // Check if root comment is in this page
                foreach ($pageDto->items as $item) {
                    if ($item->id === $this->targetCommentId) {
                        $found = true;
                        break;
                    }
                    if ($item->children) {
                        foreach ($item->children as $child) {
                            if ($child->id === $this->targetCommentId) {
                                $found = true;
                                break;
                            }
                        }
                    }
                }

                // Stop if we found the comment or there are no more pages
                if ($found || !$pageDto->config || count($pageDto->items) < $this->perPage) {
                    break;
                }

                $currentPage++;
            }

            // Create merged DTO with all loaded items
            $this->comments = new CommentListDto(
                entityType: $this->entityType,
                entityId: $this->entityId,
                page: $currentPage, // Last page loaded
                perPage: $this->perPage,
                total: $pageDto->total ?? count($allItems),
                items: $allItems,
                config: $pageDto->config,
            );
        } catch (\Throwable $e) {
            // Comment not found or access denied, fall back to normal loading
            $this->targetCommentId = null;
        }
    }
}
