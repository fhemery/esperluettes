<?php

use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('TriggerWarning Admin Controller', function () {
    describe('index', function () {
        it('displays the trigger warning list for admin', function () {
            $user = admin($this);
            StoryRefTriggerWarning::create(['name' => 'Violence', 'slug' => 'violence', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.trigger-warnings.index'));
            $response->assertOk()->assertSee('Violence');
        });

        it('denies access to non-admin users', function () {
            $user = bob($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.trigger-warnings.index'));
            $response->assertRedirect();
        });
    });

    describe('create', function () {
        it('displays the create form for admin', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.trigger-warnings.create'));
            $response->assertOk()->assertSee(__('story_ref::admin.trigger_warnings.create_title'));
        });
    });

    describe('store', function () {
        it('creates a new trigger warning', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.trigger-warnings.store'), [
                'name' => 'Langage vulgaire', 'slug' => 'langage-vulgaire', 'is_active' => true, 'order' => 1,
            ]);
            $response->assertRedirect(route('story_ref.admin.trigger-warnings.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_trigger_warnings', ['name' => 'Langage vulgaire']);
        });

        it('validates required fields', function () {
            $user = admin($this);
            $response = $this->actingAs($user)->post(route('story_ref.admin.trigger-warnings.store'), ['name' => '', 'slug' => '', 'order' => 1]);
            $response->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StoryRefTriggerWarning::create(['name' => 'Existing', 'slug' => 'existing', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->post(route('story_ref.admin.trigger-warnings.store'), ['name' => 'New', 'slug' => 'existing', 'order' => 2]);
            $response->assertSessionHasErrors('slug');
        });
    });

    describe('edit', function () {
        it('displays the edit form for admin', function () {
            $user = admin($this);
            $tw = StoryRefTriggerWarning::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->get(route('story_ref.admin.trigger-warnings.edit', $tw));
            $response->assertOk()->assertSee('Test');
        });
    });

    describe('update', function () {
        it('updates an existing trigger warning', function () {
            $user = admin($this);
            $tw = StoryRefTriggerWarning::create(['name' => 'Original', 'slug' => 'original', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->put(route('story_ref.admin.trigger-warnings.update', $tw), [
                'name' => 'Updated', 'slug' => 'updated', 'is_active' => false,
            ]);
            $response->assertRedirect(route('story_ref.admin.trigger-warnings.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('story_ref_trigger_warnings', ['id' => $tw->id, 'name' => 'Updated']);
        });
    });

    describe('reorder', function () {
        it('reorders trigger warnings', function () {
            $user = admin($this);
            $tw1 = StoryRefTriggerWarning::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'order' => 1]);
            $tw2 = StoryRefTriggerWarning::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'order' => 2]);
            $response = $this->actingAs($user)->putJson(route('story_ref.admin.trigger-warnings.reorder'), ['ordered_ids' => [$tw2->id, $tw1->id]]);
            $response->assertOk()->assertJson(['success' => true]);
            expect($tw2->fresh()->order)->toBe(1);
            expect($tw1->fresh()->order)->toBe(2);
        });
    });

    describe('destroy', function () {
        it('deletes an unused trigger warning', function () {
            $user = admin($this);
            $tw = StoryRefTriggerWarning::create(['name' => 'ToDelete', 'slug' => 'to-delete', 'is_active' => true, 'order' => 1]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.trigger-warnings.destroy', $tw));
            $response->assertRedirect(route('story_ref.admin.trigger-warnings.index'))->assertSessionHas('success');
            $this->assertDatabaseMissing('story_ref_trigger_warnings', ['id' => $tw->id]);
        });

        it('prevents deletion of trigger warning in use', function () {
            $user = admin($this);
            $tw = StoryRefTriggerWarning::create(['name' => 'InUse', 'slug' => 'in-use', 'is_active' => true, 'order' => 1]);
            $story = createStoryForAuthor($user->id);
            DB::table('story_trigger_warnings')->insert(['story_id' => $story->id, 'story_ref_trigger_warning_id' => $tw->id]);
            $response = $this->actingAs($user)->delete(route('story_ref.admin.trigger-warnings.destroy', $tw));
            $response->assertRedirect(route('story_ref.admin.trigger-warnings.index'))->assertSessionHas('error');
            $this->assertDatabaseHas('story_ref_trigger_warnings', ['id' => $tw->id]);
        });
    });

    describe('export', function () {
        it('streams a valid CSV', function () {
            StoryRefTriggerWarning::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'order' => 1]);
            $user = admin($this);
            $response = $this->actingAs($user)->get(route('story_ref.admin.trigger-warnings.export'));
            $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertDownload('trigger-warnings.csv');
        });
    });
});
