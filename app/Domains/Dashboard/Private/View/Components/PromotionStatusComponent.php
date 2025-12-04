<?php

namespace App\Domains\Dashboard\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Dto\PromotionEligibilityDto;
use App\Domains\Auth\Public\Api\Dto\PromotionStatusDto;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class PromotionStatusComponent extends Component
{
    public ?PromotionEligibilityDto $eligibility = null;
    public ?PromotionStatusDto $status = null;
    public ?string $error = null;
    public bool $showSuccess = false;

    public function __construct(
        private readonly AuthPublicApi $authApi,
        private readonly CommentPublicApi $commentApi,
    ) {
        $this->loadData();
    }

    private function loadData(): void
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->error = __('dashboard::promotion.errors.not_authenticated');
                return;
            }

            // Get comment count from Comment domain
            $commentCount = $this->commentApi->countRootCommentsByUser('chapter', $userId);

            // Get eligibility info from Auth domain
            $this->eligibility = $this->authApi->canRequestPromotion($userId, $commentCount);

            // Get current promotion status
            $this->status = $this->authApi->getPromotionStatus($userId);
        } catch (\Throwable $e) {
            $this->error = __('dashboard::promotion.errors.data_unavailable');
        }
    }

    public function render(): ViewContract
    {
        return view('dashboard::components.promotion-status', [
            'eligibility' => $this->eligibility,
            'status' => $this->status,
            'error' => $this->error,
            'showSuccess' => $this->showSuccess,
        ]);
    }
}
