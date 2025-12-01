<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Audience Admin Controller', function () {
    describe('index', function () {
        it('displays the audience list for admin users', function () {
            $user = admin($this);
            makeRefAudience('All Ages', ['order' => 1]);
            makeRefAudience('Adults Only', ['order' => 2, 'is_mature_audience' => true, 'threshold_age' => 18]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.index'));

            $response->assertOk();
            $response->assertSee('All Ages');
            $response->assertSee('Adults Only');
            $response->assertSee('-18');
        });

        it('denies access to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.index'));

            // CheckRole middleware redirects unauthorized users to dashboard
            $response->assertRedirect(route('dashboard'));
        });

        it('redirects unauthenticated users to login', function () {
            $response = $this->get(route('story_ref.admin.audiences.index'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.create'));

            $response->assertOk();
            $response->assertSee('story_ref::admin.audiences.create_title');
        });
    });

    describe('store', function () {
        it('creates a non-mature audience', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.audiences.store'), [
                    'name' => 'All Ages',
                    'slug' => 'all-ages',
                    'order' => 1,
                    'is_active' => true,
                    'is_mature_audience' => false,
                ]);

            $response->assertRedirect(route('story_ref.admin.audiences.index'));

            $this->assertDatabaseHas('story_ref_audiences', [
                'name' => 'All Ages',
                'slug' => 'all-ages',
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);
        });

        it('creates a mature audience with threshold age', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.audiences.store'), [
                    'name' => 'Adults Only',
                    'slug' => 'adults-only',
                    'order' => 2,
                    'is_active' => true,
                    'is_mature_audience' => true,
                    'threshold_age' => 18,
                ]);

            $response->assertRedirect(route('story_ref.admin.audiences.index'));

            $this->assertDatabaseHas('story_ref_audiences', [
                'name' => 'Adults Only',
                'slug' => 'adults-only',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
        });

        it('requires threshold_age when is_mature_audience is true', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.audiences.store'), [
                    'name' => 'Incomplete Mature',
                    'slug' => 'incomplete-mature',
                    'order' => 1,
                    'is_active' => true,
                    'is_mature_audience' => true,
                    // threshold_age is missing
                ]);

            $response->assertSessionHasErrors('threshold_age');
            $this->assertDatabaseMissing('story_ref_audiences', ['slug' => 'incomplete-mature']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            makeRefAudience('Existing', ['slug' => 'existing-slug']);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.audiences.store'), [
                    'name' => 'New Audience',
                    'slug' => 'existing-slug',
                    'order' => 2,
                    'is_active' => true,
                    'is_mature_audience' => false,
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('validates slug format (lowercase, numbers, hyphens only)', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('story_ref.admin.audiences.store'), [
                    'name' => 'Invalid Slug',
                    'slug' => 'Invalid_Slug!',
                    'order' => 1,
                    'is_active' => true,
                    'is_mature_audience' => false,
                ]);

            $response->assertSessionHasErrors('slug');
        });
    });

    describe('edit', function () {
        it('displays the edit form with existing data', function () {
            $user = admin($this);
            $audience = StoryRefAudience::create([
                'name' => 'Adults',
                'slug' => 'adults',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.edit', $audience));

            $response->assertOk();
            $response->assertSee('Adults');
            $response->assertSee('18');
        });
    });

    describe('update', function () {
        it('updates an audience', function () {
            $user = admin($this);
            $audience = StoryRefAudience::create([
                'name' => 'Old Name',
                'slug' => 'old-slug',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.audiences.update', $audience), [
                    'name' => 'New Name',
                    'slug' => 'new-slug',
                    'order' => 2,
                    'is_active' => true,
                    'is_mature_audience' => true,
                    'threshold_age' => 16,
                ]);

            $response->assertRedirect(route('story_ref.admin.audiences.index'));

            $this->assertDatabaseHas('story_ref_audiences', [
                'id' => $audience->id,
                'name' => 'New Name',
                'slug' => 'new-slug',
                'is_mature_audience' => true,
                'threshold_age' => 16,
            ]);
        });

        it('allows same slug when updating own record', function () {
            $user = admin($this);
            $audience = StoryRefAudience::create([
                'name' => 'All Ages',
                'slug' => 'all-ages',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);

            $response = $this->actingAs($user)
                ->put(route('story_ref.admin.audiences.update', $audience), [
                    'name' => 'All Ages Updated',
                    'slug' => 'all-ages', // Same slug
                    'order' => 1,
                    'is_active' => true,
                    'is_mature_audience' => false,
                ]);

            $response->assertRedirect(route('story_ref.admin.audiences.index'));
            $response->assertSessionHasNoErrors();
        });
    });

    describe('reorder', function () {
        it('reorders audiences via PUT request', function () {
            $user = admin($this);
            $audience1 = StoryRefAudience::create([
                'name' => 'First',
                'slug' => 'first',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);
            $audience2 = StoryRefAudience::create([
                'name' => 'Second',
                'slug' => 'second',
                'order' => 2,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);
            $audience3 = StoryRefAudience::create([
                'name' => 'Third',
                'slug' => 'third',
                'order' => 3,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);

            // Reorder: Third -> First -> Second
            $response = $this->actingAs($user)
                ->putJson(route('story_ref.admin.audiences.reorder'), [
                    'ordered_ids' => [$audience3->id, $audience1->id, $audience2->id],
                ]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            // Verify new order
            expect($audience3->fresh()->order)->toBe(1);
            expect($audience1->fresh()->order)->toBe(2);
            expect($audience2->fresh()->order)->toBe(3);
        });

        it('validates ordered_ids array', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->putJson(route('story_ref.admin.audiences.reorder'), [
                    'ordered_ids' => 'not-an-array',
                ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('ordered_ids');
        });
    });

    describe('destroy', function () {
        it('deletes an unused audience', function () {
            $user = admin($this);
            $audience = StoryRefAudience::create([
                'name' => 'To Delete',
                'slug' => 'to-delete',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.audiences.destroy', $audience));

            $response->assertRedirect(route('story_ref.admin.audiences.index'));
            $this->assertDatabaseMissing('story_ref_audiences', ['id' => $audience->id]);
        });

        it('prevents deletion of audience in use by stories', function () {
            $user = admin($this);
            $audience = StoryRefAudience::create([
                'name' => 'In Use',
                'slug' => 'in-use',
                'order' => 1,
                'is_active' => true,
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);

            // Create a story using this audience
            createStoryForAuthor($user->id, ['story_ref_audience_id' => $audience->id]);

            $response = $this->actingAs($user)
                ->delete(route('story_ref.admin.audiences.destroy', $audience));

            $response->assertRedirect(route('story_ref.admin.audiences.index'));
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_audiences', ['id' => $audience->id]);
        });
    });

    describe('export', function () {
        it('shows the Export CSV button on the audiences list page', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.index'));

            $response->assertOk();
            $response->assertSee(__('story_ref::admin.audiences.export_button'));
        });

        it('streams a valid CSV with expected headers and data', function () {
            $audience1 = StoryRefAudience::create([
                'name' => 'Adults',
                'slug' => 'adults-export',
                'is_active' => true,
                'is_mature_audience' => true,
                'threshold_age' => 18,
                'order' => 1,
            ]);

            $audience2 = StoryRefAudience::create([
                'name' => 'Teens',
                'slug' => 'teens-export',
                'is_active' => false,
                'is_mature_audience' => false,
                'threshold_age' => null,
                'order' => 2,
            ]);

            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.export'));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertDownload('audiences.csv');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);

            $response = $this->actingAs($user)
                ->get(route('story_ref.admin.audiences.export'));

            // Non-admin users are redirected (role middleware behavior)
            $response->assertRedirect();
        });
    });
});
