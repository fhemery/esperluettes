<?php

use App\Domains\Auth\Models\User;
use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;

/**
 * Create a Story and attach the given author as 'author' collaborator.
 */
function createStoryForAuthor(User $author, array $attributes = []): Story
{
    $title = $attributes['title'] ?? 'Untitled Story';
    $slugBase = Story::generateSlugBase($title);

    $story = new Story([
        'created_by_user_id' => $author->id, // audit only
        'title' => $title,
        'slug' => $attributes['slug'] ?? $slugBase,
        'description' => $attributes['description'] ?? null,
        'visibility' => $attributes['visibility'] ?? Story::VIS_PUBLIC,
        'last_chapter_published_at' => $attributes['last_chapter_published_at'] ?? null,
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
        'user_id' => $author->id,
        'role' => 'author',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    return $story;
}

function publicStory(string $title, User $author, array $attributes = []): Story
{
    return createStoryForAuthor($author, array_merge(['title' => $title, 'visibility' => Story::VIS_PUBLIC], $attributes));
}

function privateStory(string $title, User $author, array $attributes = []): Story
{
    return createStoryForAuthor($author, array_merge(['title' => $title, 'visibility' => Story::VIS_PRIVATE], $attributes));
}

function communityStory(string $title, User $author, array $attributes = []): Story
{
    return createStoryForAuthor($author, array_merge(['title' => $title, 'visibility' => Story::VIS_COMMUNITY], $attributes));
}
