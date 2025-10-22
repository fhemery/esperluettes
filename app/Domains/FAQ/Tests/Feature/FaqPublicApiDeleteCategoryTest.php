<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi deleteCategory', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $created = createFaqCategory('Test Category');

        $this->actingAs($user);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->deleteCategory($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw AuthorizationException when user is not authenticated', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $created = createFaqCategory('Test');

        // Log out
        Auth::logout();
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->deleteCategory($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when category does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->deleteCategory(999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should delete category when caller is admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $created = createFaqCategory('Test Category');

        $api = app(FaqPublicApi::class);
        $api->deleteCategory($created->id);

        // Verify deletion - getCategory should throw ModelNotFoundException
        expect(fn () => getFaqCategory($created->id))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should delete category when caller is moderator', function () {
        $admin = admin($this);
        $moderator = moderator($this);

        $this->actingAs($admin);
        $created = createFaqCategory('Test Category');

        $this->actingAs($moderator);
        $api = app(FaqPublicApi::class);

        $api->deleteCategory($created->id);

        expect(fn () => getFaqCategory($created->id))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should delete category when caller is tech-admin', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $created = createFaqCategory('Test Category');

        $this->actingAs($techAdmin);
        $api = app(FaqPublicApi::class);

        $api->deleteCategory($created->id);

        // Verify deletion
        expect(fn () => getFaqCategory($created->id))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should cascade delete questions when category is deleted', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $created = createFaqCategory('Test Category');

        // Create questions directly in database for this test
        // (We'll implement createQuestion in next slice)
        $question1 = FaqQuestion::create([
            'faq_category_id' => $created->id,
            'question' => 'Question 1?',
            'slug' => 'question-1',
            'answer' => '<p>Answer 1</p>',
            'sort_order' => 1,
            'is_active' => true,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $question2 = FaqQuestion::create([
            'faq_category_id' => $created->id,
            'question' => 'Question 2?',
            'slug' => 'question-2',
            'answer' => '<p>Answer 2</p>',
            'sort_order' => 2,
            'is_active' => true,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        // Verify questions exist
        expect(FaqQuestion::query()->find($question1->id))->not->toBeNull();
        expect(FaqQuestion::query()->find($question2->id))->not->toBeNull();

        // Delete category should cascade delete questions
        $api = app(FaqPublicApi::class);
        $api->deleteCategory($created->id);

        // Verify category and questions are deleted
        expect(fn () => getFaqCategory($created->id))
            ->toThrow(ModelNotFoundException::class);
        expect(FaqQuestion::query()->find($question1->id))->toBeNull();
        expect(FaqQuestion::query()->find($question2->id))->toBeNull();
    });
});
