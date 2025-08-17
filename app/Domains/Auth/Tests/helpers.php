<?php

use App\Domains\Auth\Models\User;

function alice(array $attributes = []): User
{
    return User::factory()->create(array_merge(['name' => 'Alice'], $attributes));
}

function bob(array $attributes = []): User
{
    return User::factory()->create(array_merge(['name' => 'Bob'], $attributes));
}
