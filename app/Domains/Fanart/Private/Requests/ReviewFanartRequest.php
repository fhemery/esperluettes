<?php

namespace App\Domains\Fanart\Private\Requests;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Http\FormRequest;

class ReviewFanartRequest extends FormRequest
{
    private bool $resolved = false;

    private ?Fanart $fanart = null;

    /** Null when the fanart is gone: a co-author already declined it. */
    public function fanart(): ?Fanart
    {
        if (!$this->resolved) {
            $this->fanart = app(FanartService::class)->find((int) $this->route('fanartId'));
            $this->resolved = true;
        }

        return $this->fanart;
    }

    /**
     * Only an author of the story may decide. A fanart that is no longer
     * pending still reaches the controller for its author, who is told it
     * was already decided; the mode and visibility are deliberately not
     * re-checked (a pending fanart outlives them).
     */
    public function authorize(): bool
    {
        $fanart = $this->fanart();
        $userId = $this->user()?->id;

        if ($fanart === null) {
            return true;
        }

        if ($fanart->status !== FanartStatus::Pending) {
            return app(StoryPublicApi::class)->isAuthor($userId, $fanart->story_id);
        }

        return app(FanartPolicy::class)->review($userId, $fanart);
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => __('fanart::ui.validation.comment_required'),
            'comment.max' => __('fanart::ui.validation.comment_max'),
        ];
    }
}
