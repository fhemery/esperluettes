<?php

namespace App\Domains\Fanart\Private\Requests;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Fanartiste role request routes (403 otherwise): create needs `requestRole`,
 * withdraw is the requester only, accept / decline are the story's authors.
 */
class RoleRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $userId = $this->user()?->id;
        $story = app(StoryResolver::class)->resolve((string) $this->route('slug'));
        $policy = app(FanartPolicy::class);

        if ($this->route('request') === null) {
            return $policy->requestRole($userId, $story->id);
        }

        if ($this->isMethod('DELETE')) {
            $request = app(FanartistService::class)->findPending((int) $this->route('request'), $story->id);

            return $request->user_id === $userId;
        }

        return $policy->manageFanartists($userId, $story->id);
    }

    public function rules(): array
    {
        return [];
    }
}
