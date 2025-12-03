<?php

use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Genre Admin Controller', function () {

    describe('index', function () {
        it('displays the genre list for admin', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'Fantasy',
                'slug' => 'fantasy',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.index'));

            $response->assertOk();
            $response->assertSee('Fantasy');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.index'));

            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.create'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.genres.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new genre', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.genres.store'), [
                    'name' => 'Science-Fiction',
                    'slug' => 'science-fiction',
                    'description' => 'Histoires de science-fiction',
                    'is_active' => true,
                    'order' => 1,
                ]);

            $response->assertRedirect(route('story_ref.admin.genres.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_genres', [
                'name' => 'Science-Fiction',
                'slug' => 'science-fiction',
            ]);
        });

        it('validates required fields', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.genres.store'), [
                    'name' => '',
                    'slug' => '',
                    'order' => 1,
                ]);

            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefGenre::create([
                'name' => 'Existing',
                'slug' => 'existing-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.genres.store'), [
                    'name' => 'New Genre',
                    'slug' => 'existing-slug',
                    'order' => 2,
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('validates slug format', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.genres.store'), [
                    'name' => 'Test',
                    'slug' => 'Invalid Slug!',
                    'order' => 1,
                ]);

            $response->assertSessionHasErrors('slug');
        });
    });

    describe('edit', function () {
        it('displays the edit form for admin', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'Test Genre',
                'slug' => 'test-genre',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.edit', $genre));

            $response->assertOk();
            $response->assertSee('Test Genre');
        });
    });

    describe('update', function () {
        it('updates an existing genre', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'Original Name',
                'slug' => 'original-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.genres.update', $genre), [
                    'name' => 'Updated Name',
                    'slug' => 'updated-slug',
                    'is_active' => false,
                ]);

            $response->assertRedirect(route('story_ref.admin.genres.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_genres', [
                'id' => $genre->id,
                'name' => 'Updated Name',
                'slug' => 'updated-slug',
                'is_active' => false,
            ]);
        });

        it('allows same slug on update', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'Test',
                'slug' => 'test-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.genres.update', $genre), [
                    'name' => 'Updated Name',
                    'slug' => 'test-slug',
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('story_ref.admin.genres.index'));
            $response->assertSessionHas('success');
        });
    });

    describe('reorder', function () {
        it('reorders genres', function () {
            $user = admin($this);
            $genre1 = StoryRefGenre::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $genre2 = StoryRefGenre::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $genre3 = StoryRefGenre::create(['name' => 'Third', 'slug' => 'third', 'is_active' => true, 'order' => 3]);

            $response = $this->actingAs($user)
                ->putJson(route('story_ref.admin.genres.reorder'), [
                    'ordered_ids' => [$genre3->id, $genre1->id, $genre2->id],
                ]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            expect($genre3->fresh()->order)->toBe(1);
            expect($genre1->fresh()->order)->toBe(2);
            expect($genre2->fresh()->order)->toBe(3);
        });
    });

    describe('destroy', function () {
        it('deletes an unused genre', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'To Delete',
                'slug' => 'to-delete',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.genres.destroy', $genre));

            $response->assertRedirect(route('story_ref.admin.genres.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_genres', ['id' => $genre->id]);
        });

        it('prevents deletion of genre in use', function () {
            $user = admin($this);
            $genre = StoryRefGenre::create([
                'name' => 'In Use',
                'slug' => 'in-use',
                'is_active' => true,
                'order' => 1,
            ]);

            // Create a story using this genre via pivot
            $story = createStoryForAuthor($user->id);
            \Illuminate\Support\Facades\DB::table('story_genres')->insert([
                'story_id' => $story->id,
                'story_ref_genre_id' => $genre->id,
            ]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.genres.destroy', $genre));

            $response->assertRedirect(route('story_ref.admin.genres.index'));
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_genres', ['id' => $genre->id]);
        });
    });

    describe('export', function () {
        it('shows the Export CSV button on the genres list page', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.index'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.genres.export_button'));
        });

        it('streams a valid CSV with expected headers', function () {
            StoryRefGenre::create([
                'name' => 'Test Genre',
                'slug' => 'test-genre',
                'is_active' => true,
                'order' => 1,
            ]);

            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.export'));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertDownload('genres.csv');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.genres.export'));

            $response->assertRedirect();
        });
    });
});
