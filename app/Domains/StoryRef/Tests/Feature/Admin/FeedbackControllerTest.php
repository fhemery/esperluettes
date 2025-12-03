<?php

use App\Domains\StoryRef\Private\Models\StoryRefFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feedback Admin Controller', function () {

    describe('index', function () {
        it('displays the feedback list for admin', function () {
            $user = admin($this);
            $feedback = StoryRefFeedback::create([
                'name' => 'Commentaires',
                'slug' => 'commentaires',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.index'));

            $response->assertOk();
            $response->assertSee('Commentaires');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.index'));

            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.create'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.feedbacks.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new feedback', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.feedbacks.store'), [
                    'name' => 'Corrections',
                    'slug' => 'corrections',
                    'description' => 'Pour les corrections orthographiques',
                    'is_active' => true,
                    'order' => 1,
                ]);

            $response->assertRedirect(route('story_ref.admin.feedbacks.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_feedbacks', [
                'name' => 'Corrections',
                'slug' => 'corrections',
            ]);
        });

        it('validates required fields', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.feedbacks.store'), [
                    'name' => '',
                    'slug' => '',
                    'order' => 1,
                ]);

            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefFeedback::create([
                'name' => 'Existing',
                'slug' => 'existing-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.feedbacks.store'), [
                    'name' => 'New Feedback',
                    'slug' => 'existing-slug',
                    'order' => 2,
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('validates slug format', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.feedbacks.store'), [
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
            $feedback = StoryRefFeedback::create([
                'name' => 'Test Feedback',
                'slug' => 'test-feedback',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.edit', $feedback));

            $response->assertOk();
            $response->assertSee('Test Feedback');
        });
    });

    describe('update', function () {
        it('updates an existing feedback', function () {
            $user = admin($this);
            $feedback = StoryRefFeedback::create([
                'name' => 'Original Name',
                'slug' => 'original-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.feedbacks.update', $feedback), [
                    'name' => 'Updated Name',
                    'slug' => 'updated-slug',
                    'is_active' => false,
                ]);

            $response->assertRedirect(route('story_ref.admin.feedbacks.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_feedbacks', [
                'id' => $feedback->id,
                'name' => 'Updated Name',
                'slug' => 'updated-slug',
                'is_active' => false,
            ]);
        });

        it('allows same slug on update', function () {
            $user = admin($this);
            $feedback = StoryRefFeedback::create([
                'name' => 'Test',
                'slug' => 'test-slug',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.feedbacks.update', $feedback), [
                    'name' => 'Updated Name',
                    'slug' => 'test-slug',
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('story_ref.admin.feedbacks.index'));
            $response->assertSessionHas('success');
        });
    });

    describe('reorder', function () {
        it('reorders feedbacks', function () {
            $user = admin($this);
            $feedback1 = StoryRefFeedback::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $feedback2 = StoryRefFeedback::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $feedback3 = StoryRefFeedback::create(['name' => 'Third', 'slug' => 'third', 'is_active' => true, 'order' => 3]);

            $response = $this->actingAs($user)
                ->putJson(route('story_ref.admin.feedbacks.reorder'), [
                    'ordered_ids' => [$feedback3->id, $feedback1->id, $feedback2->id],
                ]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            expect($feedback3->fresh()->order)->toBe(1);
            expect($feedback1->fresh()->order)->toBe(2);
            expect($feedback2->fresh()->order)->toBe(3);
        });
    });

    describe('destroy', function () {
        it('deletes an unused feedback', function () {
            $user = admin($this);
            $feedback = StoryRefFeedback::create([
                'name' => 'To Delete',
                'slug' => 'to-delete',
                'is_active' => true,
                'order' => 1,
            ]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.feedbacks.destroy', $feedback));

            $response->assertRedirect(route('story_ref.admin.feedbacks.index'));
            $response->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_feedbacks', ['id' => $feedback->id]);
        });

        it('prevents deletion of feedback in use', function () {
            $user = admin($this);
            $feedback = StoryRefFeedback::create([
                'name' => 'In Use',
                'slug' => 'in-use',
                'is_active' => true,
                'order' => 1,
            ]);

            // Create a story using this feedback
            createStoryForAuthor($user->id, ['story_ref_feedback_id' => $feedback->id]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.feedbacks.destroy', $feedback));

            $response->assertRedirect(route('story_ref.admin.feedbacks.index'));
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_feedbacks', ['id' => $feedback->id]);
        });
    });

    describe('export', function () {
        it('shows the Export CSV button on the feedbacks list page', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.index'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.feedbacks.export_button'));
        });

        it('streams a valid CSV with expected headers', function () {
            StoryRefFeedback::create([
                'name' => 'Test Feedback',
                'slug' => 'test-feedback',
                'is_active' => true,
                'order' => 1,
            ]);

            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.export'));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertDownload('feedbacks.csv');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.feedbacks.export'));

            $response->assertRedirect();
        });
    });
});
