<?php

use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create a Story and attach the given author as 'author' collaborator.
 */
function createStoryForAuthor(int $authorId, array $attributes = []): Story
{
    $title = $attributes['title'] ?? 'Untitled Story';
    $slugBase = Story::generateSlugBase($title);

    $story = new Story([
        'created_by_user_id' => $authorId, // audit only
        'title' => $title,
        'slug' => $attributes['slug'] ?? $slugBase,
        // Column is NOT NULL in schema; default to empty string in tests
        'description' => $attributes['description'] ?? '',
        'visibility' => $attributes['visibility'] ?? Story::VIS_PUBLIC,
        'last_chapter_published_at' => $attributes['last_chapter_published_at'] ?? null,
        'story_ref_type_id' => $attributes['story_ref_type_id'] ?? defaultStoryType()->id,
        'story_ref_audience_id' => $attributes['story_ref_audience_id'] ?? defaultAudience()->id,
        'story_ref_copyright_id' => $attributes['story_ref_copyright_id'] ?? defaultCopyright()->id,
        'story_ref_status_id' => $attributes['story_ref_status_id'] ?? null,
    ]);
    $story->save();

    // Attach default genre(s) if provided in attributes, else attach one default to satisfy relational expectations in some tests
    $genreIds = $attributes['story_ref_genre_ids'] ?? [$attributes['story_ref_genre_id'] ?? null];
    $genreIds = array_values(array_filter(array_map(fn($v) => $v ? (int)$v : null, (array)$genreIds)));
    if (empty($genreIds)) {
        $genreIds = [defaultGenre()->id];
    }
    $story->genres()->sync($genreIds);

    // Attach default trigger warning(s) if provided in attributes, else attach one default to satisfy relational expectations in some tests
    $twIds = $attributes['story_ref_trigger_warning_ids'] ?? [$attributes['story_ref_trigger_warning_ids'] ?? null];
    $twIds = array_values(array_filter(array_map(fn($v) => $v ? (int)$v : null, (array)$twIds)));
    if (!empty($twIds)) {
        $story->triggerWarnings()->sync($twIds);
    }

    // Ensure slug ends with id suffix (same behavior as service/store)
    if (!str_ends_with($story->slug, '-' . $story->id)) {
        $story->slug = $slugBase . '-' . $story->id;
        $story->save();
    }

    // Attach author to pivot
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $authorId,
        'role' => 'author',
        'invited_by_user_id' => $authorId,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    return $story;
}

function publicStory(string $title, int $authorId, array $attributes = []): Story
{
    return createStoryForAuthor($authorId, array_merge(['title' => $title, 'visibility' => Story::VIS_PUBLIC], $attributes));
}

function privateStory(string $title, int $authorId, array $attributes = []): Story
{
    return createStoryForAuthor($authorId, array_merge(['title' => $title, 'visibility' => Story::VIS_PRIVATE], $attributes));
}

function communityStory(string $title, int $authorId, array $attributes = []): Story
{
    return createStoryForAuthor($authorId, array_merge(['title' => $title, 'visibility' => Story::VIS_COMMUNITY], $attributes));
}

/**
 * Ensure a Story Type exists for tests and return it.
 */
function makeStoryType(string $name): \App\Domains\StoryRef\Models\StoryRefType
{
    // Use service to auto-generate slug and defaults
    return app(\App\Domains\StoryRef\Services\TypeService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultStoryType(): \App\Domains\StoryRef\Models\StoryRefType
{
    return \App\Domains\StoryRef\Models\StoryRefType::firstOrCreate([
        'name' => 'Default type',
        'slug' => 'default-type',
        'is_active' => true]);
}

function defaultAudience(): \App\Domains\StoryRef\Models\StoryRefAudience
{
    return \App\Domains\StoryRef\Models\StoryRefAudience::firstOrCreate([
        'name' => 'DefaultAudience',
        'slug' => 'default-audience',
        'is_active' => true]);
}

/**
 * Ensure a Story Audience exists for tests and return it.
 */
function makeAudience(string $name): \App\Domains\StoryRef\Models\StoryRefAudience
{
    return app(\App\Domains\StoryRef\Services\AudienceService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultCopyright(): \App\Domains\StoryRef\Models\StoryRefCopyright
{
    return \App\Domains\StoryRef\Models\StoryRefCopyright::firstOrCreate([
        'name' => 'DefaultCopyright',
        'slug' => 'default-copyright',
        'is_active' => true]);
}

function makeCopyright(string $name): \App\Domains\StoryRef\Models\StoryRefCopyright
{
    return app(\App\Domains\StoryRef\Services\CopyrightService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultGenre(): \App\Domains\StoryRef\Models\StoryRefGenre
{
    return \App\Domains\StoryRef\Models\StoryRefGenre::firstOrCreate([
        'name' => 'DefaultGenre',
        'slug' => 'default-genre',
        'is_active' => true,
    ]);
}

function makeGenre(string $name): \App\Domains\StoryRef\Models\StoryRefGenre
{
    return app(\App\Domains\StoryRef\Services\GenreService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function makeStatus(string $name): \App\Domains\StoryRef\Models\StoryRefStatus
{
    return app(\App\Domains\StoryRef\Services\StatusService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultTriggerWarning(): \App\Domains\StoryRef\Models\StoryRefTriggerWarning
{
    return \App\Domains\StoryRef\Models\StoryRefTriggerWarning::firstOrCreate([
        'name' => 'Violence',
        'slug' => 'violence',
        'is_active' => true,
    ]);
}

function makeTriggerWarning(string $name): \App\Domains\StoryRef\Models\StoryRefTriggerWarning
{
    return app(\App\Domains\StoryRef\Services\TriggerWarningService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

/**
 * Build a valid payload for story create/update; override any field to test specific validation scenarios.
 */
function validStoryPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Valid',
        'description' => null,
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
        'story_ref_audience_id' => defaultAudience()->id,
        'story_ref_copyright_id' => defaultCopyright()->id,
        'story_ref_genre_ids' => [defaultGenre()->id],
    ], $overrides);
}
