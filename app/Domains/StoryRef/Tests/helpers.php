<?php

use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\GenreWriteDto;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\AudienceWriteDto;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Create a StoryRef Genre through the public API for tests.
 */
function makeRefGenre(TestCase $t, string $name, array $overrides = []): GenreDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new GenreWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        description: $overrides['description'] ?? null,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createGenre($write);
}

/**
 * Create a StoryRef Audience through the public API for tests.
 */
function makeRefAudience(TestCase $t, string $name, array $overrides = []): AudienceDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new AudienceWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createAudience($write);
}
