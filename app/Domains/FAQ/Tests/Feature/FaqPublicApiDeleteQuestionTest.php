<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi deleteQuestion', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $this->actingAs($user);

        expect(fn () => $api->deleteQuestion($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw AuthorizationException when user is not authenticated', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
        );
        $created = $api->createQuestion($createDto);

        \Illuminate\Support\Facades\Auth::logout();

        expect(fn () => $api->deleteQuestion($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when question does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->deleteQuestion(999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should delete question when caller is admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $api->deleteQuestion($created->id);

        // Verify deletion
        expect(fn () => $api->getQuestion($created->id))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should delete question when caller is tech-admin', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
        );
        $created = $api->createQuestion($createDto);

        $this->actingAs($techAdmin);

        $api->deleteQuestion($created->id);

        // Verify deletion
        expect(fn () => $api->getQuestion($created->id))
            ->toThrow(ModelNotFoundException::class);
    });
});
