<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Models\FaqCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeFaqCategory(string $name = 'Test Category', bool $isActive = true, int $sortOrder = 1): FaqCategory
{
    return FaqCategory::create([
        'name' => $name,
        'slug' => Str::slug($name) . '-' . uniqid(),
        'is_active' => $isActive,
        'sort_order' => $sortOrder,
        'created_by_user_id' => 1,
        'updated_by_user_id' => 1,
    ]);
}

describe('FaqCategory Admin Controller', function () {

    describe('index', function () {
        it('redirects unauthenticated users to login', function () {
            $this->get(route('faq.admin.faq-categories.index'))
                ->assertRedirect(route('login'));
        });

        it('denies access to non-admins', function () {
            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('faq.admin.faq-categories.index'))
                ->assertRedirect();
        });

        it('displays the list for admins', function () {
            makeFaqCategory('Premiers pas');

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-categories.index'))
                ->assertOk()
                ->assertSee('Premiers pas');
        });

        it('displays the list for tech-admins', function () {
            makeFaqCategory('Technique');

            $this->actingAs(techAdmin($this))
                ->get(route('faq.admin.faq-categories.index'))
                ->assertOk()
                ->assertSee('Technique');
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-categories.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('creates a category', function () {
            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.store'), [
                    'name' => 'Nouvelle catégorie',
                    'slug' => 'nouvelle-categorie',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseHas('faq_categories', ['name' => 'Nouvelle catégorie']);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.store'), [])
                ->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates slug uniqueness', function () {
            makeFaqCategory();
            $existing = FaqCategory::first();

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.store'), [
                    'name' => 'Other',
                    'slug' => $existing->slug,
                ])
                ->assertSessionHasErrors(['slug']);
        });

        it('assigns sort_order as max existing + 1', function () {
            makeFaqCategory('Existant', sortOrder: 5);

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.store'), [
                    'name' => 'Nouveau',
                    'slug' => 'nouveau',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseHas('faq_categories', ['name' => 'Nouveau', 'sort_order' => 6]);
        });

        it('assigns sort_order 1 when no categories exist', function () {
            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.store'), [
                    'name' => 'Première',
                    'slug' => 'premiere',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseHas('faq_categories', ['name' => 'Première', 'sort_order' => 1]);
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $category = makeFaqCategory('Catégorie éditable');

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-categories.edit', $category))
                ->assertOk()
                ->assertSee('Catégorie éditable');
        });
    });

    describe('update', function () {
        it('updates a category', function () {
            $category = makeFaqCategory('Ancienne');

            $this->actingAs(admin($this))
                ->put(route('faq.admin.faq-categories.update', $category), [
                    'name' => 'Nouvelle',
                    'slug' => 'nouvelle',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseHas('faq_categories', ['id' => $category->id, 'name' => 'Nouvelle']);
        });

        it('allows resubmitting the same slug', function () {
            $category = makeFaqCategory('Catégorie existante');

            $this->actingAs(admin($this))
                ->put(route('faq.admin.faq-categories.update', $category), [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-categories.index'));
        });
    });

    describe('destroy', function () {
        it('deletes a category', function () {
            $category = makeFaqCategory();

            $this->actingAs(admin($this))
                ->delete(route('faq.admin.faq-categories.destroy', $category))
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseMissing('faq_categories', ['id' => $category->id]);
        });
    });

    describe('reorder', function () {
        it('reorders categories', function () {
            $a = makeFaqCategory('A', sortOrder: 1);
            $b = makeFaqCategory('B', sortOrder: 2);

            $this->actingAs(admin($this))
                ->putJson(route('faq.admin.faq-categories.reorder'), [
                    'ordered_ids' => [$b->id, $a->id],
                ])
                ->assertOk();

            $this->assertDatabaseHas('faq_categories', ['id' => $b->id, 'sort_order' => 1]);
            $this->assertDatabaseHas('faq_categories', ['id' => $a->id, 'sort_order' => 2]);
        });
    });

    describe('toggleActive', function () {
        it('toggles active status', function () {
            $category = makeFaqCategory(isActive: true);

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-categories.toggle-active', $category))
                ->assertRedirect(route('faq.admin.faq-categories.index'));

            $this->assertDatabaseHas('faq_categories', ['id' => $category->id, 'is_active' => false]);
        });
    });
});
