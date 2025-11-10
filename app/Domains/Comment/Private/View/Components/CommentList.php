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
    public ?int $targetCommentId = null;

    public function __construct(
        private AuthPublicApi $authApi,
        public string $entityType,
        public int $entityId,
        public int $perPage = 5,
        public int $page = 1,
        public ?string $fragment = null,
    ) {
        $api = app(CommentPublicApi::class);
        $this->isGuest = !auth()->check();
        
        try {
            // Get comment ID from query parameter or provided fragment
            $targetCommentId = null;
            
            // First check if we have a comment query parameter
            if (request()->has('comment')) {
                $targetCommentId = (int) request('comment');
            }
            // Fall back to provided fragment (for testing)
            else if ($fragment && preg_match('/^comment-(\d+)$/', $fragment, $matches)) {
                $targetCommentId = (int) $matches[1];
            }
            
            // Store for JavaScript access
            $this->targetCommentId = $targetCommentId;

            // If we have a target comment, validate and pre-load pages
            if ($targetCommentId) {
                try {
                    $targetComment = $api->getComment($targetCommentId, withChildren: false);
                    
                    // Validate entity match
                    if ($targetComment->entityType !== $this->entityType 
                        || $targetComment->entityId !== $this->entityId) {
                        $targetCommentId = null; // Invalid, ignore
                    } else {                       
                        // Load pages incrementally until we find the target root comment
                        $currentPage = 1;
                        $allItems = [];
                        $found = false;
                        
                        while (!$found) {
                            $pageDto = $api->getFor($this->entityType, $this->entityId, $currentPage, $this->perPage);
                            $allItems = array_merge($allItems, $pageDto->items);
                            
                            // Check if root comment is in this page
                            foreach ($pageDto->items as $item) {
                                if ($item->id === $targetCommentId) {
                                    $found = true;
                                    break;
                                }
                                if ($item->children) {
                                    foreach ($item->children as $child) {
                                        if ($child->id === $targetCommentId) {
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
                    }
                } catch (\Throwable $e) {
                    // Comment not found or access denied, fall back to normal loading
                    $targetCommentId = null;
                }
            }

            // Normal loading if no deep link or validation failed
            if (!$targetCommentId) {
                // page <= 0 triggers lazy mode in PublicApi (config + total only)
                $this->comments = $api->getFor($this->entityType, $this->entityId, $this->page, $this->perPage);
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
}
