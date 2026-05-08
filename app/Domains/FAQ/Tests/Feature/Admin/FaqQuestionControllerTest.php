<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeFaqCategoryForQuestion(string $name = 'Test Category'): FaqCategory
{
    return FaqCategory::create([
        'name' => $name,
        'slug' => Str::slug($name) . '-' . uniqid(),
        'is_active' => true,
        'sort_order' => 1,
        'created_by_user_id' => 1,
        'updated_by_user_id' => 1,
    ]);
}

function makeFaqQuestion(FaqCategory $category, string $question = 'Test question?', bool $isActive = true): FaqQuestion
{
    return FaqQuestion::create([
        'faq_category_id' => $category->id,
        'question' => $question,
        'slug' => Str::slug($question) . '-' . uniqid(),
        'answer' => '<p>Test answer</p>',
        'is_active' => $isActive,
        'sort_order' => 1,
        'created_by_user_id' => 1,
        'updated_by_user_id' => 1,
    ]);
}

describe('FaqQuestion Admin Controller', function () {

    describe('index', function () {
        it('redirects unauthenticated users to login', function () {
            $this->get(route('faq.admin.faq-questions.index'))
                ->assertRedirect(route('login'));
        });

        it('denies access to non-admins', function () {
            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('faq.admin.faq-questions.index'))
                ->assertRedirect();
        });

        it('displays the list for admins', function () {
            $category = makeFaqCategoryForQuestion();
            makeFaqQuestion($category, 'Comment créer un compte ?');

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-questions.index'))
                ->assertOk()
                ->assertSee('Comment créer un compte ?');
        });

        it('filters by category', function () {
            $catA = makeFaqCategoryForQuestion('Cat A');
            $catB = makeFaqCategoryForQuestion('Cat B');
            makeFaqQuestion($catA, 'Question de A ?');
            makeFaqQuestion($catB, 'Question de B ?');

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-questions.index', ['category_id' => $catA->id]))
                ->assertOk()
                ->assertSee('Question de A ?')
                ->assertDontSee('Question de B ?');
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            makeFaqCategoryForQuestion();

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-questions.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('creates a question', function () {
            $category = makeFaqCategoryForQuestion();

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-questions.store'), [
                    'faq_category_id' => $category->id,
                    'question' => 'Comment faire ?',
                    'slug' => 'comment-faire',
                    'answer' => '<p>Comme ceci.</p>',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-questions.index'));

            $this->assertDatabaseHas('faq_questions', ['question' => 'Comment faire ?']);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-questions.store'), [])
                ->assertSessionHasErrors(['faq_category_id', 'question', 'slug', 'answer']);
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category, 'Question modifiable ?');

            $this->actingAs(admin($this))
                ->get(route('faq.admin.faq-questions.edit', $question))
                ->assertOk()
                ->assertSee('Question modifiable ?');
        });
    });

    describe('update', function () {
        it('updates a question', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category, 'Ancienne question ?');

            $this->actingAs(admin($this))
                ->put(route('faq.admin.faq-questions.update', $question), [
                    'faq_category_id' => $category->id,
                    'question' => 'Nouvelle question ?',
                    'slug' => 'nouvelle-question',
                    'answer' => '<p>Nouvelle réponse.</p>',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-questions.index'));

            $this->assertDatabaseHas('faq_questions', ['id' => $question->id, 'question' => 'Nouvelle question ?']);
        });

        it('allows resubmitting the same slug', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category, 'Question existante ?');

            $this->actingAs(admin($this))
                ->put(route('faq.admin.faq-questions.update', $question), [
                    'faq_category_id' => $category->id,
                    'question' => $question->question,
                    'slug' => $question->slug,
                    'answer' => $question->answer,
                    'is_active' => '1',
                ])
                ->assertRedirect(route('faq.admin.faq-questions.index'));
        });
    });

    describe('destroy', function () {
        it('deletes a question', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category);

            $this->actingAs(admin($this))
                ->delete(route('faq.admin.faq-questions.destroy', $question))
                ->assertRedirect(route('faq.admin.faq-questions.index'));

            $this->assertDatabaseMissing('faq_questions', ['id' => $question->id]);
        });
    });

    describe('reorder', function () {
        it('reorders questions', function () {
            $category = makeFaqCategoryForQuestion();
            $q1 = makeFaqQuestion($category, 'Q1 ?');
            $q2 = makeFaqQuestion($category, 'Q2 ?');

            $this->actingAs(admin($this))
                ->putJson(route('faq.admin.faq-questions.reorder'), [
                    'ordered_ids' => [$q2->id, $q1->id],
                ])
                ->assertOk();

            $this->assertDatabaseHas('faq_questions', ['id' => $q2->id, 'sort_order' => 1]);
            $this->assertDatabaseHas('faq_questions', ['id' => $q1->id, 'sort_order' => 2]);
        });
    });

    describe('toggleActive', function () {
        it('activates an inactive question', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category, isActive: false);

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-questions.toggle-active', $question))
                ->assertRedirect(route('faq.admin.faq-questions.index'));

            $this->assertDatabaseHas('faq_questions', ['id' => $question->id, 'is_active' => true]);
        });

        it('deactivates an active question', function () {
            $category = makeFaqCategoryForQuestion();
            $question = makeFaqQuestion($category, isActive: true);

            $this->actingAs(admin($this))
                ->post(route('faq.admin.faq-questions.toggle-active', $question))
                ->assertRedirect(route('faq.admin.faq-questions.index'));

            $this->assertDatabaseHas('faq_questions', ['id' => $question->id, 'is_active' => false]);
        });
    });
});
