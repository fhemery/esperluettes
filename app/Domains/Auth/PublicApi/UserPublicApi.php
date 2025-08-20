<?php

namespace App\Domains\Auth\PublicApi;

use App\Domains\Auth\PublicApi\Dto\RoleDto;

interface UserPublicApi
{
    /**
     * Return roles for each user id.
     *
     * @param array<int,int> $userIds
     * @return array<int,array<int,RoleDto>> key: user_id, value: list of RoleDto
     */
    public function getRolesByUserIds(array $userIds): array;
}
