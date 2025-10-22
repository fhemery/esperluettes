<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\Dto\FaqCategoryDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi createCategory', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $this->actingAs($user);
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqCategoryDto(
            name: 'Getting Started',
            description: 'Basic questions about using the platform',
        );

        expect(fn () => $api->createCategory($dto))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw AuthorizationException when user is not authenticated', function () {
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqCategoryDto(name: 'Getting Started');

        expect(fn () => $api->createCategory($dto))
            ->toThrow(AuthorizationException::class);
    });

    it('should create category with required fields when caller is admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $created = createFaqCategory('Getting Started');

        expect($created)->toBeInstanceOf(FaqCategoryDto::class);
        expect($created->name)->toBe('Getting Started');
        expect($created->slug)->toBe('getting-started');
        expect($created->description)->toBeNull();
        expect($created->isActive)->toBeTrue();
        expect($created->sortOrder)->toBeInt();
        expect($created->createdByUserId)->toBe($admin->id);
        expect($created->updatedByUserId)->toBe($admin->id);
        expect($created->id)->toBeInt();

        // Verify via getCategory
        $retrieved = getFaqCategory($created->id);
        expect($retrieved->name)->toBe('Getting Started');
        expect($retrieved->slug)->toBe('getting-started');
        expect($retrieved->createdByUserId)->toBe($admin->id);
    });

    it('should create category with all fields when caller is tech-admin', function () {
        $techAdmin = techAdmin($this);
        $this->actingAs($techAdmin);

        $created = createFaqCategory(
            'Advanced Topics',
            'For experienced users',
            false
        );

        expect($created)->toBeInstanceOf(FaqCategoryDto::class);
        expect($created->name)->toBe('Advanced Topics');
        expect($created->slug)->toBe('advanced-topics');
        expect($created->description)->toBe('For experienced users');
        expect($created->isActive)->toBeFalse();
        expect($created->createdByUserId)->toBe($techAdmin->id);

        // Verify via getCategory
        $retrieved = getFaqCategory($created->id);
        expect($retrieved->description)->toBe('For experienced users');
        expect($retrieved->isActive)->toBeFalse();
    });

    it('should create category when caller is moderator', function () {
        $moderator = moderator($this);
        $this->actingAs($moderator);

        $created = createFaqCategory(
            'Moderator Topics',
            'Created by moderator',
            true
        );

        expect($created)->toBeInstanceOf(FaqCategoryDto::class);
        expect($created->name)->toBe('Moderator Topics');
        expect($created->slug)->toBe('moderator-topics');
        expect($created->description)->toBe('Created by moderator');
        expect($created->isActive)->toBeTrue();
        expect($created->createdByUserId)->toBe($moderator->id);

        $retrieved = getFaqCategory($created->id);
        expect($retrieved->name)->toBe('Moderator Topics');
    });

    it('should auto-generate unique slug from name', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $dto1 = createFaqCategory('General');
        expect($dto1->slug)->toBe('general');

        $dto2 = createFaqCategory('General');
        expect($dto2->slug)->not->toBe('general');
        expect($dto2->slug)->toContain('general');
    });

    it('should set default sort_order', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $dto = createFaqCategory('First Category');
        expect($dto->sortOrder)->toBeGreaterThanOrEqual(0);
    });
});
