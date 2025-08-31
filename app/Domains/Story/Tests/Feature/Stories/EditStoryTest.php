<?php

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('redirects guests from edit page to login', function () {
    // Arrange: existing public story
    $author = alice($this, roles: ['user-confirmed']);
    $story = publicStory('Guest Edit Test', $author->id);

    // Act
    $resp = $this->get('/stories/' . $story->slug . '/edit');

    // Assert
    $resp->assertRedirect('/login');
});

it('allows the author to load edit page and update story', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Original Title', $author->id, ['description' => '<p>desc</p>']);

    // Load edit page
    $this->get('/stories/' . $story->slug . '/edit')
        ->assertOk()
        ->assertSee('story::edit.title');

    // Update
    $payload = validStoryPayload([
        'title' => 'Updated Title',
        'description' => '<p>new desc</p>',
    ]);

    $resp = $this->put('/stories/' . $story->slug, $payload);
    $resp->assertRedirect();

    // Reload model
    $story->refresh();

    // Slug base regenerated, id suffix preserved
    $newBase = Story::generateSlugBase('Updated Title');
    expect($story->slug)
        ->toStartWith($newBase . '-')
        ->and($story->slug)
        ->toEndWith('-' . $story->id);
});

it('allows a co-author with role author to update', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $coauthor = bob($this, roles: ['user-confirmed']);
    $story = publicStory('Team Story', $author->id);

    // Add coauthor as author
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $coauthor->id,
        'role' => 'author',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $this->actingAs($coauthor);

    $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Coauthored Title',
        'description' => '<p>x</p>',
    ]));

    $resp->assertRedirect();
    $story->refresh();
    expect($story->title)->toBe('Coauthored Title');
});

it('returns 404 for collaborator without author role', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $other = bob($this, roles: ['user-confirmed']);
    $story = publicStory('No Edit Perms', $author->id);

    // Add collaborator with non-author role
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $other->id,
        'role' => 'editor',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $this->actingAs($other);

    $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
    $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Should Fail',
        'description' => '<p>x</p>',
    ]))->assertNotFound();
});

it('returns 404 for non-collaborator trying to edit', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $intruder = bob($this, roles: ['user-confirmed']);
    $story = publicStory('No Access', $author->id);

    $this->actingAs($intruder);

    $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
    $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Nope',
        'description' => '<p>x</p>',
    ]))->assertNotFound();
});

it('301-redirects from old slug base to canonical after title change', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Old Title', $author->id);
    $oldSlug = $story->slug; // contains -id

    // Update title
    $this->put('/stories/' . $oldSlug, validStoryPayload([
        'title' => 'New Canonical Title',
        'description' => '<p>desc</p>',
    ]))->assertRedirect();

    $story->refresh();

    // Visiting the old slug-with-id should 301 to the new canonical slug
    $resp = $this->get('/stories/' . $oldSlug);
    $resp->assertStatus(301);
    $resp->assertRedirect('/stories/' . $story->slug);
});

it('denies edit access to co-authors without user-confirmed role (middleware)', function () {
    // Arrange: author is confirmed, coauthor is NOT (has only user role)
    $author = alice($this, roles: ['user-confirmed']);
    $coauthor = bob($this, roles: ['user']);
    $story = publicStory('Needs Confirmed Role', $author->id);

    // Add coauthor as author collaborator at story level
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $coauthor->id,
        'role' => 'author',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    // Act as non-confirmed coauthor
    $this->actingAs($coauthor);

    // Because routes are guarded by role:user-confirmed, middleware should redirect to dashboard
    $this->get('/stories/' . $story->slug . '/edit')->assertRedirect(route('dashboard'));

    // And update attempts should also be blocked by middleware
    $this->put('/stories/' . $story->slug, [
        'title' => 'Should Not Be Applied',
        'description' => '<p>x</p>',
        'visibility' => Story::VIS_PUBLIC,
    ])->assertRedirect(route('dashboard'));

    // Ensure story unchanged
    $story->refresh();
    expect($story->title)->toBe('Needs Confirmed Role');
});

it('syncs genres on update (replaces previous selection)', function () {
    // Arrange: author and story with default genre
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Genres Updatable', $author->id, [
        'description' => '<p>x</p>',
        'story_ref_genre_ids' => [defaultGenre()->id],
    ]);

    // New genres to replace existing selection
    $g1 = makeGenre('Sci-Fi');
    $g2 = makeGenre('Mystery');

    // Act: update with two genres
    $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Genres Updatable',
        'description' => '<p>x</p>',
        'story_ref_genre_ids' => [$g1->id, $g2->id],
    ]));
    $resp->assertRedirect();

    // Assert: pivot now contains only the two new genres
    $story->refresh();
    $ids = $story->genres()->pluck('story_ref_genres.id')->sort()->values()->all();
    expect($ids)->toBe([$g1->id, $g2->id]);

    // Show page reflects new genres
    $show = $this->get('/stories/' . $story->slug);
    $show->assertOk();
    $show->assertSee(trans('story::shared.genres.label'));
    $show->assertSee('Sci-Fi');
    $show->assertSee('Mystery');
});

it('allows the author to set and change the optional status on update', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Status Updatable', $author->id, [
        'description' => '<p>x</p>',
    ]);

    $status = makeStatus('Draft');

    $resp1 = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Status Updatable',
        'description' => '<p>x</p>',
        'story_ref_status_id' => $status->id,
    ]));
    $resp1->assertRedirect();

    $story->refresh();
    expect($story->story_ref_status_id)->toBe($status->id);
    $this->get('/stories/' . $story->slug)
        ->assertOk()
        ->assertSee(trans('story::shared.status.label'))
        ->assertSee($status->name);
});

it('syncs trigger warnings on update (replaces previous selection)', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $twA = makeTriggerWarning('Violence');
    $twB = makeTriggerWarning('Drogues');
    $twC = makeTriggerWarning('Suicide');

    // Story initially with one TW (A)
    $story = publicStory('TW Updatable', $author->id, [
        'description' => '<p>x</p>',
    ]);
    $story->triggerWarnings()->sync([$twA->id]);

    // Act: update with [B, C]
    $resp = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'TW Updatable',
        'description' => '<p>x</p>',
        'story_ref_trigger_warning_ids' => [$twB->id, $twC->id],
    ]));
    $resp->assertRedirect();

    // Assert pivot replaced
    $story->refresh();
    $ids = $story->triggerWarnings()->pluck('story_ref_trigger_warnings.id')->sort()->values()->all();
    expect($ids)->toBe([$twB->id, $twC->id]);

    // Show page displays new TWs
    $show = $this->get('/stories/' . $story->slug);
    $show->assertOk();
    $show->assertSee(trans('story::shared.trigger_warnings.label'));
    $show->assertSee('Drogues');
    $show->assertSee('Suicide');
});


it('allows the author to set and change the optional feedback on update', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Feedback Updatable', $author->id, [
        'description' => '<p>x</p>',
    ]);

    $fb = makeFeedback('Looking for critique');

    // First set feedback
    $resp1 = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Feedback Updatable',
        'description' => '<p>x</p>',
        'story_ref_feedback_id' => $fb->id,
    ]));
    $resp1->assertRedirect();

    $story->refresh();
    expect($story->story_ref_feedback_id)->toBe($fb->id);
    $this->get('/stories/' . $story->slug)
        ->assertOk()
        ->assertSee(trans('story::shared.feedback.label'))
        ->assertSee($fb->name);

    // Then clear feedback by sending null
    $resp2 = $this->put('/stories/' . $story->slug, validStoryPayload([
        'title' => 'Feedback Updatable',
        'description' => '<p>x</p>',
        'story_ref_feedback_id' => null,
    ]));
    $resp2->assertRedirect();

    $story->refresh();
    expect($story->story_ref_feedback_id)->toBeNull();
});
