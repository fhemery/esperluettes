<?php

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\StoryRef\Private\Models\StoryRefFeedback;
use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Private\Services\TypeService;
use App\Domains\StoryRef\Private\Services\AudienceService;
use App\Domains\StoryRef\Private\Services\CopyrightService;
use App\Domains\StoryRef\Private\Services\GenreService;
use App\Domains\StoryRef\Private\Services\StatusService;
use App\Domains\StoryRef\Private\Services\FeedbackService;
use App\Domains\StoryRef\Private\Services\TriggerWarningService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

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
        'story_ref_feedback_id' => $attributes['story_ref_feedback_id'] ?? null,
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

function getStory(int $id): Story
{
    return Story::query()->findOrFail($id);
}

/**
 * Ensure a Story Type exists for tests and return it.
 */
function makeStoryType(string $name): StoryRefType
{
    // Use service to auto-generate slug and defaults
    return app(TypeService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultStoryType(): StoryRefType
{
    return StoryRefType::firstOrCreate([
        'name' => 'Default type',
        'slug' => 'default-type',
        'is_active' => true
    ]);
}

function defaultAudience(): StoryRefAudience
{
    return StoryRefAudience::firstOrCreate([
        'name' => 'DefaultAudience',
        'slug' => 'default-audience',
        'is_active' => true
    ]);
}

/**
 * Ensure a Story Audience exists for tests and return it.
 */
function makeAudience(string $name): StoryRefAudience
{
    return app(AudienceService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultCopyright(): StoryRefCopyright
{
    return StoryRefCopyright::firstOrCreate([
        'name' => 'DefaultCopyright',
        'slug' => 'default-copyright',
        'is_active' => true
    ]);
}

function makeCopyright(string $name, string $description = ''): StoryRefCopyright
{
    return app(CopyrightService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => $description,
        'is_active' => true,
    ]);
}

function defaultGenre(): StoryRefGenre
{
    return StoryRefGenre::firstOrCreate([
        'name' => 'DefaultGenre',
        'slug' => 'default-genre',
        'is_active' => true,
    ]);
}

function makeGenre(string $name): StoryRefGenre
{
    return app(GenreService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function makeStatus(string $name): StoryRefStatus
{
    return app(StatusService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function makeFeedback(string $name): StoryRefFeedback
{
    return app(FeedbackService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'is_active' => true,
    ]);
}

function defaultTriggerWarning(): StoryRefTriggerWarning
{
    return StoryRefTriggerWarning::firstOrCreate([
        'name' => 'Violence',
        'slug' => 'violence',
        'is_active' => true,
    ]);
}

function makeTriggerWarning(string $name, string $description = ''): StoryRefTriggerWarning
{
    return app(TriggerWarningService::class)->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => $description,
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
        'description' => generateDummyText(150),
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
        'story_ref_audience_id' => defaultAudience()->id,
        'story_ref_copyright_id' => defaultCopyright()->id,
        'story_ref_genre_ids' => [defaultGenre()->id],
        'story_ref_feedback_id' => null,
        'tw_disclosure' => Story::TW_NO_TW,
    ], $overrides);
}

/**
 * Build a valid payload for chapter create; override fields as needed.
 */
function validChapterPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Chapter Title',
        'author_note' => null,
        'content' => '<p>Content</p>',
        // 'published' => '1' // set in published helper
    ], $overrides);
}

/**
 * Create a published chapter for a story through the real HTTP endpoint.
 * Returns the freshly persisted Chapter model.
 */
function createPublishedChapter(TestCase $t, Story $story, Authenticatable $author, array $overrides = []): Chapter
{
    $t->actingAs($author);
    $payload = validChapterPayload(array_merge(['published' => '1'], $overrides));
    $t->post('/stories/' . $story->slug . '/chapters', $payload)->assertRedirect();
    return Chapter::query()->latest('id')->firstOrFail();
}

/**
 * Create an unpublished chapter for a story through the real HTTP endpoint.
 */
function createUnpublishedChapter(TestCase $t, Story $story, Authenticatable $author, array $overrides = []): Chapter
{
    $t->actingAs($author);
    $payload = validChapterPayload($overrides); // no 'published' key -> draft
    $t->post('/stories/' . $story->slug . '/chapters', $payload)->assertRedirect();
    return Chapter::query()->latest('id')->firstOrFail();
}

/**
 * Helpers to toggle logged reading state through real HTTP endpoints in tests.
 */
function markAsRead($test, Chapter $chapter)
{
    return $test->post(route('chapters.read.mark', [
        'storySlug' => $chapter->story->slug,
        'chapterSlug' => $chapter->slug,
    ]));
}

function markAsUnread($test, Chapter $chapter)
{
    return $test->delete(route('chapters.read.unmark', [
        'storySlug' => $chapter->story->slug,
        'chapterSlug' => $chapter->slug,
    ]));
}

function setUserCredits(int $userId, int $credits): void
{
    DB::table('story_chapter_credits')
        ->where('user_id', $userId)
        ->update([
            'credits_gained' => $credits,
            'updated_at' => now(),
        ]);
}

function addCollaborator(int $storyId, int $userId, string $role = 'author'): void
{
    DB::table('story_collaborators')->insert([
        'story_id' => $storyId,
        'user_id' => $userId,
        'role' => $role,
        'invited_by_user_id' => $userId,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);
}

/**
 * Change a story visibility to the requested value (e.g. 'public', 'community', 'private').
 */
function setStoryVisibility(int $storyId, string $visibility): void
{
    DB::table('stories')
        ->where('id', $storyId)
        ->update([
            'visibility' => $visibility,
            'updated_at' => now(),
        ]);
}
