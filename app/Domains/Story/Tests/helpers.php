<?php

use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;

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
    ]);
    $story->save();

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
        'name' => 'FirstStoryType',
        'slug' => 'first-story-type',
        'is_active' => true]);
}
