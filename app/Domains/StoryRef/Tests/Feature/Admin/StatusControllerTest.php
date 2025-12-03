<?php

use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Status Admin Controller', function () {
    describe('index', function () {
        it('displays the status list for admin', function () {
            $user = admin($this);
            StoryRefStatus::create(['name' => 'En cours', 'slug' => 'en-cours', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.statuses.index'));
            $response->assertOk()->assertSee('En cours');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.statuses.index'));
            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.statuses.create'));
            $response->assertOk()->assertSee(__('story_ref::admin.statuses.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new status', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.statuses.store'), [
                'name' => 'Terminé', 'slug' => 'termine', 'is_active' => true, 'order' => 1,
            ]);
            $response->assertRedirect(route('story_ref.admin.statuses.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_statuses', ['name' => 'Terminé', 'slug' => 'termine']);
        });

        it('validates required fields', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.statuses.store'), ['name' => '', 'slug' => '', 'order' => 1]);
            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefStatus::create(['name' => 'Existing', 'slug' => 'existing', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->post(route('story_ref.admin.statuses.store'), ['name' => 'New', 'slug' => 'existing', 'order' => 2]);
            $response->assertSessionHasErrors('slug');
        });
    });

    describe('edit', function () {
        it('displays the edit form for admin', function () {
            $user = admin($this);
            $status = StoryRefStatus::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.statuses.edit', $status));
            $response->assertOk()->assertSee('Test');
        });
    });

    describe('update', function () {
        it('updates an existing status', function () {
            $user = admin($this);
            $status = StoryRefStatus::create(['name' => 'Original', 'slug' => 'original', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->put(route('story_ref.admin.statuses.update', $status), [
                'name' => 'Updated', 'slug' => 'updated', 'is_active' => false,
            ]);
            $response->assertRedirect(route('story_ref.admin.statuses.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_statuses', ['id' => $status->id, 'name' => 'Updated']);
        });
    });

    describe('reorder', function () {
        it('reorders statuses', function () {
            $user = admin($this);
            $s1 = StoryRefStatus::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $s2 = StoryRefStatus::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $response = $this->actingAs($user)->putJson(route('story_ref.admin.statuses.reorder'), ['ordered_ids' => [$s2->id, $s1->id]]);
            $response->assertOk()->assertJson(['success' => true]);
            expect($s2->fresh()->order)->toBe(1);
            expect($s1->fresh()->order)->toBe(2);
        });
    });

    describe('destroy', function () {
        it('deletes an unused status', function () {
            $user = admin($this);
            $status = StoryRefStatus::create(['name' => 'ToDelete', 'slug' => 'to-delete', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.statuses.destroy', $status));
            $response->assertRedirect(route('story_ref.admin.statuses.index'))->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_statuses', ['id' => $status->id]);
        });

        it('prevents deletion of status in use', function () {
            $user = admin($this);
            $status = StoryRefStatus::create(['name' => 'InUse', 'slug' => 'in-use', 'is_active' => true, 'order' => 1]);
            $story = createStoryForAuthor($user->id);
            \Illuminate\Support\Facades\DB::table('stories')->where('id', $story->id)->update(['story_ref_status_id' => $status->id]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.statuses.destroy', $status));
            $response->assertRedirect(route('story_ref.admin.statuses.index'))->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_statuses', ['id' => $status->id]);
        });
    });

    describe('export', function () {
        it('streams a valid CSV', function () {
            StoryRefStatus::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.statuses.export'));
            $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertDownload('statuses.csv');
        });
    });
});
