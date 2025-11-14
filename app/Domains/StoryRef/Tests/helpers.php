<?php

use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\GenreWriteDto;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\AudienceWriteDto;
use App\Domains\StoryRef\Public\Contracts\StatusDto;
use App\Domains\StoryRef\Public\Contracts\StatusWriteDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackWriteDto;
use App\Domains\StoryRef\Public\Contracts\TypeDto;
use App\Domains\StoryRef\Public\Contracts\TypeWriteDto;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningDto;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningWriteDto;
use App\Domains\StoryRef\Public\Contracts\CopyrightDto;
use App\Domains\StoryRef\Public\Contracts\CopyrightWriteDto;
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

/**
 * Create a StoryRef Status through the public API for tests.
 */
function makeRefStatus(TestCase $t, string $name, array $overrides = []): StatusDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new StatusWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        description: $overrides['description'] ?? null,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createStatus($write);
}

/**
 * Create a StoryRef Feedback through the public API for tests.
 */
function makeRefFeedback(TestCase $t, string $name, array $overrides = []): FeedbackDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new FeedbackWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        description: $overrides['description'] ?? null,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createFeedback($write);
}

/**
 * Create a StoryRef Type through the public API for tests.
 */
function makeRefType(TestCase $t, string $name, array $overrides = []): TypeDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new TypeWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createType($write);
}

/**
 * Create a StoryRef TriggerWarning through the public API for tests.
 */
function makeRefTriggerWarning(TestCase $t, string $name, array $overrides = []): TriggerWarningDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new TriggerWarningWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        description: $overrides['description'] ?? null,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createTriggerWarning($write);
}

/**
 * Create a StoryRef Copyright through the public API for tests.
 */
function makeRefCopyright(TestCase $t, string $name, array $overrides = []): CopyrightDto
{
    $admin = admin($t);
    $t->actingAs($admin);

    /** @var StoryRefPublicApi $api */
    $api = app(StoryRefPublicApi::class);

    $write = new CopyrightWriteDto(
        slug: $overrides['slug'] ?? Str::slug($name),
        name: $name,
        description: $overrides['description'] ?? null,
        is_active: $overrides['is_active'] ?? true,
        order: $overrides['order'] ?? null,
    );

    return $api->createCopyright($write);
}
