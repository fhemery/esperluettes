<?php

use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Copyright Admin Controller', function () {

    describe('index', function () {
        it('displays the copyright list for admin', function () {
            $user = admin($this);
            $copyright = StoryRefCopyright::create([
                'name' => 'All Rights Reserved',
                'slug' => 'all-rights-reserved',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.index'));

            $response->assertOk();
            $response->assertSee('All Rights Reserved');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.index'));

            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.create'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.copyrights.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new copyright', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.copyrights.store'), [
                    'name' => 'Creative Commons',
                    'slug' => 'creative-commons',
                    'description' => 'Free to share',
                    'is_active' => true,
                    'order' => 1,
                ]);

            $response->assertRedirect(route('story_ref.admin.copyrights.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_copyrights', [
                'name' => 'Creative Commons',
                'slug' => 'creative-commons',
            ]);
        });

        it('validates required fields', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.copyrights.store'), [
                    'name' => '',
                    'slug' => '',
                    'order' => 1,
                ]);

            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefCopyright::create([
                'name' => 'Existing',
                'slug' => 'existing-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.copyrights.store'), [
                    'name' => 'New Copyright',
                    'slug' => 'existing-slug',
                    'order' => 2,
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('validates slug format', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.copyrights.store'), [
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
            $copyright = StoryRefCopyright::create([
                'name' => 'Test Copyright',
                'slug' => 'test-copyright',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.edit', $copyright));

            $response->assertOk();
            $response->assertSee('Test Copyright');
        });
    });

    describe('update', function () {
        it('updates an existing copyright', function () {
            $user = admin($this);
            $copyright = StoryRefCopyright::create([
                'name' => 'Original Name',
                'slug' => 'original-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.copyrights.update', $copyright), [
                    'name' => 'Updated Name',
                    'slug' => 'updated-slug',
                    'is_active' => false,
                ]);

            $response->assertRedirect(route('story_ref.admin.copyrights.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_copyrights', [
                'id' => $copyright->id,
                'name' => 'Updated Name',
                'slug' => 'updated-slug',
                'is_active' => false,
            ]);
        });

        it('allows same slug on update', function () {
            $user = admin($this);
            $copyright = StoryRefCopyright::create([
                'name' => 'Test',
                'slug' => 'test-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.copyrights.update', $copyright), [
                    'name' => 'Updated Name',
                    'slug' => 'test-slug', // Same slug
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('story_ref.admin.copyrights.index'));
            $response->assertSessionHas('success');
        });
    });

    describe('reorder', function () {
        it('reorders copyrights', function () {
            $user = admin($this);
            $copyright1 = StoryRefCopyright::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $copyright2 = StoryRefCopyright::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $copyright3 = StoryRefCopyright::create(['name' => 'Third', 'slug' => 'third', 'is_active' => true, 'order' => 3]);

            $response = $this->actingAs($user)
                ->putJson(route('story_ref.admin.copyrights.reorder'), [
                    'ordered_ids' => [$copyright3->id, $copyright1->id, $copyright2->id],
                ]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            expect($copyright3->fresh()->order)->toBe(1);
            expect($copyright1->fresh()->order)->toBe(2);
            expect($copyright2->fresh()->order)->toBe(3);
        });
    });

    describe('destroy', function () {
        it('deletes an unused copyright', function () {
            $user = admin($this);
            $copyright = StoryRefCopyright::create([
                'name' => 'To Delete',
                'slug' => 'to-delete',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.copyrights.destroy', $copyright));

            $response->assertRedirect(route('story_ref.admin.copyrights.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_copyrights', ['id' => $copyright->id]);
        });

        it('prevents deletion of copyright in use', function () {
            $user = admin($this);
            $copyright = StoryRefCopyright::create([
                'name' => 'In Use',
                'slug' => 'in-use',
                'is_active' => true,
                'order' => 1,
            ]);

            // Create a story using this copyright
            createStoryForAuthor($user->id, ['story_ref_copyright_id' => $copyright->id]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.copyrights.destroy', $copyright));

            $response->assertRedirect(route('story_ref.admin.copyrights.index'));
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_copyrights', ['id' => $copyright->id]);
        });
    });

    describe('export', function () {
        it('shows the Export CSV button on the copyrights list page', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.index'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.copyrights.export_button'));
        });

        it('streams a valid CSV with expected headers', function () {
            StoryRefCopyright::create([
                'name' => 'Test Copyright',
                'slug' => 'test-copyright',
                'is_active' => true,
                'order' => 1,
            ]);

            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.export'));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertDownload('copyrights.csv');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.copyrights.export'));

            $response->assertRedirect();
        });
    });
});
