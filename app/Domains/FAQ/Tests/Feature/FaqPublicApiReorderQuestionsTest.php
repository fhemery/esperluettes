<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi reorderQuestionsInCategory', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto($category->id, 'Question 1?', '<p>Answer 1</p>');
        $q1 = $api->createQuestion($dto1);

        $dto2 = new CreateFaqQuestionDto($category->id, 'Question 2?', '<p>Answer 2</p>');
        $q2 = $api->createQuestion($dto2);

        $this->actingAs($user);

        expect(fn () => $api->reorderQuestionsInCategory($category->id, [$q2->id, $q1->id]))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ValidationException when category does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderQuestionsInCategory(999, [1, 2]))
            ->toThrow(ValidationException::class);
    });

    it('should throw ValidationException when question does not belong to category', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category1 = createFaqCategory('Category 1');
        $category2 = createFaqCategory('Category 2');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto($category1->id, 'Q1?', '<p>A1</p>');
        $q1 = $api->createQuestion($dto1);

        $dto2 = new CreateFaqQuestionDto($category2->id, 'Q2?', '<p>A2</p>');
        $q2 = $api->createQuestion($dto2);

        // Try to reorder category1 with question from category2
        expect(fn () => $api->reorderQuestionsInCategory($category1->id, [$q1->id, $q2->id]))
            ->toThrow(ValidationException::class);
    });

    it('should reorder questions within category', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto($category->id, 'Question 1?', '<p>Answer 1</p>');
        $q1 = $api->createQuestion($dto1);

        $dto2 = new CreateFaqQuestionDto($category->id, 'Question 2?', '<p>Answer 2</p>');
        $q2 = $api->createQuestion($dto2);

        $dto3 = new CreateFaqQuestionDto($category->id, 'Question 3?', '<p>Answer 3</p>');
        $q3 = $api->createQuestion($dto3);

        // Reorder: q3, q1, q2
        $api->reorderQuestionsInCategory($category->id, [$q3->id, $q1->id, $q2->id]);

        $reordered1 = $api->getQuestion($q3->id);
        $reordered2 = $api->getQuestion($q1->id);
        $reordered3 = $api->getQuestion($q2->id);

        expect($reordered1->sortOrder)->toBe(0);
        expect($reordered2->sortOrder)->toBe(1);
        expect($reordered3->sortOrder)->toBe(2);
    });

    it('should not affect questions in other categories', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category1 = createFaqCategory('Category 1');
        $category2 = createFaqCategory('Category 2');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto($category1->id, 'Q1 Cat1?', '<p>A1</p>');
        $q1 = $api->createQuestion($dto1);

        $dto2 = new CreateFaqQuestionDto($category1->id, 'Q2 Cat1?', '<p>A2</p>');
        $q2 = $api->createQuestion($dto2);

        $dto3 = new CreateFaqQuestionDto($category2->id, 'Q1 Cat2?', '<p>A3</p>');
        $q3 = $api->createQuestion($dto3);

        $originalSortOrder = $q3->sortOrder;

        // Reorder category1 questions
        $api->reorderQuestionsInCategory($category1->id, [$q2->id, $q1->id]);

        // Check category2 question unchanged
        $unchanged = $api->getQuestion($q3->id);
        expect($unchanged->sortOrder)->toBe($originalSortOrder);
    });

    it('should allow tech-admin to reorder questions', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto($category->id, 'Q1?', '<p>A1</p>');
        $q1 = $api->createQuestion($dto1);

        $dto2 = new CreateFaqQuestionDto($category->id, 'Q2?', '<p>A2</p>');
        $q2 = $api->createQuestion($dto2);

        $this->actingAs($techAdmin);

        $api->reorderQuestionsInCategory($category->id, [$q2->id, $q1->id]);

        $reordered1 = $api->getQuestion($q2->id);
        expect($reordered1->sortOrder)->toBe(0);
    });

    it('should throw ValidationException for empty array', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderQuestionsInCategory($category->id, []))
            ->toThrow(ValidationException::class);
    });

    it('should throw ValidationException for duplicate IDs', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto($category->id, 'Q1?', '<p>A1</p>');
        $q1 = $api->createQuestion($dto);

        expect(fn () => $api->reorderQuestionsInCategory($category->id, [$q1->id, $q1->id]))
            ->toThrow(ValidationException::class);
    });
});
