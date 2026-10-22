<?php

namespace App\Domains\Fanart\Private\Requests;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Foundation\Http\FormRequest;

/** Grant and revoke: authors of the story only (403 otherwise). */
class ManageFanartistsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $story = app(StoryResolver::class)->resolve((string) $this->route('slug'));

        return app(FanartPolicy::class)->manageFanartists($this->user()?->id, $story->id);
    }

    public function rules(): array
    {
        if ($this->isMethod('DELETE')) {
            return [];
        }

        return [
            'target_users' => ['required', 'array', 'min:1'],
            'target_users.*' => ['required', 'integer'],
        ];
    }
}
