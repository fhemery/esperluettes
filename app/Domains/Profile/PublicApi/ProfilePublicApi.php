<?php

namespace App\Domains\Profile\PublicApi;

use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Profile\Models\Profile;
use Illuminate\Support\Facades\Cache;

class ProfilePublicApi implements ProfilePublicApiContract
{
    public function getPublicProfile(int $userId): ?ProfileDto
    {
        $results = $this->getPublicProfiles([$userId]);
        return $results[$userId] ?? null;
    }

    public function getPublicProfiles(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return [];
        }

        $results = [];
        $missing = [];
        foreach ($userIds as $id) {
            $key = "profile_public_{$id}";
            if (Cache::has($key)) {
                $results[$id] = Cache::get($key);
            } else {
                $missing[] = $id;
            }
        }

        if (!empty($missing)) {
            $profiles = Profile::whereIn('user_id', $missing)->with('user')->get();
            foreach ($missing as $id) {
                $profile = $profiles->firstWhere('user_id', $id);
                $dto = $profile ? new ProfileDto(
                    user_id: $profile->user_id,
                    display_name: (string) ($profile->display_name ?? ''),
                    slug: (string) ($profile->slug ?? ''),
                    avatar_url: (string) $profile->profile_picture_url,
                ) : null;
                Cache::put("profile_public_{$id}", $dto, now()->addMinutes(10));
                $results[$id] = $dto;
            }
        }

        return $results;
    }
}
