<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi updateQuestion', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Original question?',
            answer: '<p>Original answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $this->actingAs($user);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Updated question?',
            slug: $created->slug,
            answer: '<p>Updated answer</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );

        expect(fn () => $api->updateQuestion($created->id, $updateDto))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when question does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Non-existent?',
            slug: 'non-existent',
            answer: '<p>Answer</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );

        expect(fn () => $api->updateQuestion(999, $updateDto))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should throw ValidationException when moving to non-existent category', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Original question?',
            answer: '<p>Original answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: 999,
            question: 'Updated question?',
            slug: $created->slug,
            answer: '<p>Updated answer</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );

        expect(fn () => $api->updateQuestion($created->id, $updateDto))
            ->toThrow(ValidationException::class);
    });

    it('should update all question fields', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Original question?',
            answer: '<p>Original answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Updated question?',
            slug: 'custom-updated-slug',
            answer: '<p>Updated answer</p>',
            imagePath: '/images/new.png',
            imageAltText: 'New image',
            isActive: false,
            sortOrder: 42,
        );

        $updated = $api->updateQuestion($created->id, $updateDto);

        expect($updated->id)->toBe($created->id);
        expect($updated->question)->toBe('Updated question?');
        expect($updated->slug)->toBe('custom-updated-slug');
        expect($updated->answer)->toBe('<p>Updated answer</p>');
        expect($updated->imagePath)->toBe('/images/new.png');
        expect($updated->imageAltText)->toBe('New image');
        expect($updated->isActive)->toBeFalse();
        expect($updated->sortOrder)->toBe(42);
        expect($updated->updatedByUserId)->toBe($admin->id);

        // Verify
        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->question)->toBe('Updated question?');
        expect($retrieved->slug)->toBe('custom-updated-slug');
    });

    it('should allow moving question to different category', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category1 = createFaqCategory('Category 1');
        $category2 = createFaqCategory('Category 2');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category1->id,
            question: 'Question in category 1?',
            answer: '<p>Answer</p>',
        );
        $created = $api->createQuestion($createDto);

        expect($created->faqCategoryId)->toBe($category1->id);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category2->id,
            question: $created->question,
            slug: $created->slug,
            answer: $created->answer,
            imagePath: $created->imagePath,
            imageAltText: $created->imageAltText,
            isActive: $created->isActive,
            sortOrder: $created->sortOrder,
        );

        $updated = $api->updateQuestion($created->id, $updateDto);

        expect($updated->faqCategoryId)->toBe($category2->id);

        // Verify
        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->faqCategoryId)->toBe($category2->id);
    });

    it('should preserve created_by_user_id on update', function () {
        $admin1 = admin($this);
        $admin2 = techAdmin($this);

        $this->actingAs($admin1);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
        );
        $created = $api->createQuestion($createDto);

        expect($created->createdByUserId)->toBe($admin1->id);

        $this->actingAs($admin2);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Updated question?',
            slug: $created->slug,
            answer: '<p>Updated answer</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );

        $updated = $api->updateQuestion($created->id, $updateDto);

        expect($updated->createdByUserId)->toBe($admin1->id);
        expect($updated->updatedByUserId)->toBe($admin2->id);
    });

    it('should sanitize HTML in answer field on update', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Original</p>',
        );
        $created = $api->createQuestion($createDto);

        $updateDto = new UpdateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: $created->question,
            slug: $created->slug,
            answer: '<p>Updated content</p><script>alert("xss")</script>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );

        $updated = $api->updateQuestion($created->id, $updateDto);

        expect($updated->answer)->not->toContain('<script>');
        expect($updated->answer)->toContain('<p>Updated content</p>');
    });
});
