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

describe('FaqPublicApi activateQuestion', function () {
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
            isActive: false,
        );
        $created = $api->createQuestion($createDto);

        $this->actingAs($user);

        expect(fn () => $api->activateQuestion($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when question does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->activateQuestion(999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should activate inactive question', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
            isActive: false,
        );
        $created = $api->createQuestion($createDto);

        expect($created->isActive)->toBeFalse();

        $api->activateQuestion($created->id);

        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->isActive)->toBeTrue();
    });

    it('should allow tech-admin to activate question', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $category = createFaqCategory('Test Category');
        $api = app(FaqPublicApi::class);

        $createDto = new CreateFaqQuestionDto(
            faqCategoryId: $category->id,
            question: 'Test question?',
            answer: '<p>Test answer</p>',
            isActive: false,
        );
        $created = $api->createQuestion($createDto);

        $this->actingAs($techAdmin);

        $api->activateQuestion($created->id);

        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->isActive)->toBeTrue();
    });
});

describe('FaqPublicApi deactivateQuestion', function () {
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

        expect(fn () => $api->deactivateQuestion($created->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when question does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->deactivateQuestion(999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should deactivate active question', function () {
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

        expect($created->isActive)->toBeTrue();

        $api->deactivateQuestion($created->id);

        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->isActive)->toBeFalse();
    });

    it('should allow tech-admin to deactivate question', function () {
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

        $api->deactivateQuestion($created->id);

        $retrieved = $api->getQuestion($created->id);
        expect($retrieved->isActive)->toBeFalse();
    });
});
