<?php

use App\Domains\StoryRef\Private\Models\StoryRefType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Type Admin Controller', function () {
    describe('index', function () {
        it('displays the type list for admin', function () {
            $user = admin($this);
            StoryRefType::create(['name' => 'Roman', 'slug' => 'roman', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.types.index'));
            $response->assertOk()->assertSee('Roman');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.types.index'));
            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.types.create'));
            $response->assertOk()->assertSee(__('story_ref::admin.types.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new type', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.types.store'), [
                'name' => 'Nouvelle', 'slug' => 'nouvelle', 'is_active' => true, 'order' => 1,
            ]);
            $response->assertRedirect(route('story_ref.admin.types.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_types', ['name' => 'Nouvelle', 'slug' => 'nouvelle']);
        });

        it('validates required fields', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.types.store'), ['name' => '', 'slug' => '', 'order' => 1]);
            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefType::create(['name' => 'Existing', 'slug' => 'existing', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->post(route('story_ref.admin.types.store'), ['name' => 'New', 'slug' => 'existing', 'order' => 2]);
            $response->assertSessionHasErrors('slug');
        });
    });

    describe('edit', function () {
        it('displays the edit form for admin', function () {
            $user = admin($this);
            $type = StoryRefType::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.types.edit', $type));
            $response->assertOk()->assertSee('Test');
        });
    });

    describe('update', function () {
        it('updates an existing type', function () {
            $user = admin($this);
            $type = StoryRefType::create(['name' => 'Original', 'slug' => 'original', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->put(route('story_ref.admin.types.update', $type), [
                'name' => 'Updated', 'slug' => 'updated', 'is_active' => false,
            ]);
            $response->assertRedirect(route('story_ref.admin.types.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_types', ['id' => $type->id, 'name' => 'Updated']);
        });
    });

    describe('reorder', function () {
        it('reorders types', function () {
            $user = admin($this);
            $t1 = StoryRefType::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $t2 = StoryRefType::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $response = $this->actingAs($user)->putJson(route('story_ref.admin.types.reorder'), ['ordered_ids' => [$t2->id, $t1->id]]);
            $response->assertOk()->assertJson(['success' => true]);
            expect($t2->fresh()->order)->toBe(1);
            expect($t1->fresh()->order)->toBe(2);
        });
    });

    describe('destroy', function () {
        it('deletes an unused type', function () {
            $user = admin($this);
            $type = StoryRefType::create(['name' => 'ToDelete', 'slug' => 'to-delete', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.types.destroy', $type));
            $response->assertRedirect(route('story_ref.admin.types.index'))->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_types', ['id' => $type->id]);
        });

        it('prevents deletion of type in use', function () {
            $user = admin($this);
            $type = StoryRefType::create(['name' => 'InUse', 'slug' => 'in-use', 'is_active' => true, 'order' => 1]);
            createStoryForAuthor($user->id, ['story_ref_type_id' => $type->id]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.types.destroy', $type));
            $response->assertRedirect(route('story_ref.admin.types.index'))->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_types', ['id' => $type->id]);
        });
    });

    describe('export', function () {
        it('streams a valid CSV', function () {
            StoryRefType::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.types.export'));
            $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertDownload('types.csv');
        });
    });
});
