<?php

use App\Domains\StoryRef\Private\Services\AudienceRefService;
use App\Domains\StoryRef\Private\Services\CopyrightRefService;
use App\Domains\StoryRef\Private\Services\FeedbackRefService;
use App\Domains\StoryRef\Private\Services\GenreRefService;
use App\Domains\StoryRef\Private\Services\StatusRefService;
use App\Domains\StoryRef\Private\Services\TriggerWarningRefService;
use App\Domains\StoryRef\Private\Services\TypeRefService;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\CopyrightDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackDto;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\StatusDto;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningDto;
use App\Domains\StoryRef\Public\Contracts\TypeDto;
use Illuminate\Support\Str;

/**
 * Create a StoryRef Genre through the underlying service for tests.
 */
function makeRefGenre(string $name, array $overrides = []): GenreDto
{
    /** @var GenreRefService $service */
    $service = app(GenreRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return GenreDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'description' => $overrides['description'] ?? null,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return GenreDto::fromModel($model);
}

/**
 * Create a StoryRef Audience through the underlying service for tests.
 */
function makeRefAudience(string $name, array $overrides = []): AudienceDto
{
    /** @var AudienceRefService $service */
    $service = app(AudienceRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return AudienceDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return AudienceDto::fromModel($model);
}

/**
 * Create a StoryRef Status through the underlying service for tests.
 */
function makeRefStatus(string $name, array $overrides = []): StatusDto
{
    /** @var StatusRefService $service */
    $service = app(StatusRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return StatusDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'description' => $overrides['description'] ?? null,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return StatusDto::fromModel($model);
}

/**
 * Create a StoryRef Feedback through the underlying service for tests.
 */
function makeRefFeedback(string $name, array $overrides = []): FeedbackDto
{
    /** @var FeedbackRefService $service */
    $service = app(FeedbackRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return FeedbackDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'description' => $overrides['description'] ?? null,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return FeedbackDto::fromModel($model);
}

/**
 * Create a StoryRef Type through the underlying service for tests.
 */
function makeRefType(string $name, array $overrides = []): TypeDto
{
    /** @var TypeRefService $service */
    $service = app(TypeRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return TypeDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return TypeDto::fromModel($model);
}

/**
 * Create a StoryRef TriggerWarning through the underlying service for tests.
 */
function makeRefTriggerWarning(string $name, array $overrides = []): TriggerWarningDto
{
    /** @var TriggerWarningRefService $service */
    $service = app(TriggerWarningRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return TriggerWarningDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'description' => $overrides['description'] ?? null,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return TriggerWarningDto::fromModel($model);
}

/**
 * Create a StoryRef Copyright through the underlying service for tests.
 */
function makeRefCopyright(string $name, array $overrides = []): CopyrightDto
{
    /** @var CopyrightRefService $service */
    $service = app(CopyrightRefService::class);

    $existing = $service->getOneBySlug($overrides['slug'] ?? Str::slug($name));
    if ($existing) {
        return CopyrightDto::fromModel($existing);
    }

    $model = $service->create([
        'slug' => $overrides['slug'] ?? Str::slug($name),
        'name' => $name,
        'description' => $overrides['description'] ?? null,
        'is_active' => $overrides['is_active'] ?? true,
        'order' => $overrides['order'] ?? null,
    ]);

    return CopyrightDto::fromModel($model);
}

/**
 * Default helper wrappers for common referentials, to ensure tests across domains
 * use the StoryRefPublicApi consistently.
 */
function defaultRefType(): TypeDto
{
    return makeRefType('Default type', ['slug' => 'default-type']);
}

function defaultRefAudience(): AudienceDto
{
    return makeRefAudience('DefaultAudience', ['slug' => 'default-audience']);
}

function defaultRefCopyright(): CopyrightDto
{
    return makeRefCopyright('DefaultCopyright', ['slug' => 'default-copyright']);
}

function defaultRefGenre(): GenreDto
{
    return makeRefGenre('DefaultGenre', ['slug' => 'default-genre']);
}

function defaultRefFeedback(): FeedbackDto
{
    return makeRefFeedback('DefaultFeedback', ['slug' => 'default-feedback']);
}

function defaultRefTriggerWarning(): TriggerWarningDto
{
    return makeRefTriggerWarning('DefaultTW', ['slug' => 'default-tw']);
}
