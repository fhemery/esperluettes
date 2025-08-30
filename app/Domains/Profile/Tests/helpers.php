<?php

use App\Domains\Shared\Contracts\ProfilePublicApi;

if (!function_exists('profileSlugFromApi')) {
    function profileSlugFromApi(int $userId): string
    {
        /** @var ProfilePublicApi $api */
        $api = app(ProfilePublicApi::class);
        $dto = $api->getPublicProfile($userId);
        if ($dto === null) {
            throw new RuntimeException('Profile not found for user id ' . $userId);
        }
        return (string) $dto->slug;
    }
}
