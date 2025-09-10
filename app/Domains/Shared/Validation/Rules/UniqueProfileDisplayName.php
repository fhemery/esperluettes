<?php

namespace App\Domains\Shared\Validation\Rules;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Support\SimpleSlug;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueProfileDisplayName implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_string($value)) {
            return; // other validators will catch type issues
        }
        $name = trim($value);
        if ($name === '') {
            return; // let required/string rules handle empties
        }

        /** @var ProfilePublicApi $profiles */
        $profiles = app(ProfilePublicApi::class);

        // Check slug collision
        $slug = SimpleSlug::normalize($name);
        $bySlug = $profiles->getPublicProfileBySlug($slug);
        if ($bySlug && $bySlug->user_id !== $this->ignoreUserId) {
            $fail(__('validation.unique_profile_display_name'));
            return;
        }
    }
}
