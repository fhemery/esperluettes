<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\Dto\FaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi createQuestion', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');

        $this->actingAs($user);
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'How do I start?',
            answer: '<p>Just begin!</p>',
        );

        expect(fn () => $api->createQuestion($dto))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw AuthorizationException when user is not authenticated', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');

        \Illuminate\Support\Facades\Auth::logout();
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'How do I start?',
            answer: '<p>Just begin!</p>',
        );

        expect(fn () => $api->createQuestion($dto))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ValidationException when category does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto(
            faqCategoryId: 999,
            question: 'How do I start?',
            answer: '<p>Just begin!</p>',
        );

        expect(fn () => $api->createQuestion($dto))
            ->toThrow(ValidationException::class);
    });

    it('should create question with required fields when caller is admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');

        $api = app(FaqPublicApi::class);
        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'How do I start?',
            answer: '<p>Just begin!</p>',
        );

        $created = $api->createQuestion($dto);

        expect($created)->toBeInstanceOf(FaqQuestionDto::class);
        expect($created->faqCategoryId)->toBe($category->id);
        expect($created->question)->toBe('How do I start?');
        expect($created->answer)->toBe('<p>Just begin!</p>');
        expect($created->slug)->toBe('how-do-i-start');
        expect($created->isActive)->toBeTrue();
        expect($created->sortOrder)->toBeInt();
        expect($created->createdByUserId)->toBe($admin->id);
        expect($created->id)->toBeInt();

        // Verify via getQuestion
        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->question)->toBe('How do I start?');
        expect($retrieved->faqCategoryId)->toBe($category->id);
    });

    it('should create question with all optional fields when caller is tech-admin', function () {
        $techAdmin = techAdmin($this);
        $this->actingAs($techAdmin);

        $category = createFaqCategory('Test Category');

        $api = app(FaqPublicApi::class);
        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Advanced question?',
            answer: '<p>Advanced answer</p>',
            imagePath: '/images/faq/advanced.png',
            imageAltText: 'Advanced diagram',
            isActive: false,
            sortOrder: 5,
        );

        $created = $api->createQuestion($dto);

        expect($created->imagePath)->toBe('/images/faq/advanced.png');
        expect($created->imageAltText)->toBe('Advanced diagram');
        expect($created->isActive)->toBeFalse();
        expect($created->sortOrder)->toBe(5);
        expect($created->createdByUserId)->toBe($techAdmin->id);
    });

    it('should create question when caller is moderator', function () {
        $moderator = moderator($this);
        $this->actingAs($moderator);

        $category = createFaqCategory('Test Category');

        $api = app(FaqPublicApi::class);
        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Moderator question?',
            answer: '<p>Moderator answer</p>',
        );

        $created = $api->createQuestion($dto);

        expect($created->question)->toBe('Moderator question?');
        expect($created->createdByUserId)->toBe($moderator->id);
    });

    it('should auto-generate unique slug from question', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto1 = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'What is this?',
            answer: '<p>Answer 1</p>',
        );

        $created1 = $api->createQuestion($dto1);
        expect($created1->slug)->toBe('what-is-this');

        $dto2 = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'What is this?',
            answer: '<p>Answer 2</p>',
        );

        $created2 = $api->createQuestion($dto2);
        expect($created2->slug)->not->toBe('what-is-this');
        expect($created2->slug)->toContain('what-is-this');
    });

    it('should sanitize HTML in answer field', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Safe content</p><script>alert("xss")</script>',
        );

        $created = $api->createQuestion($dto);

        expect($created->answer)->not->toContain('<script>');
        expect($created->answer)->toContain('<p>Safe content</p>');
    });
});
