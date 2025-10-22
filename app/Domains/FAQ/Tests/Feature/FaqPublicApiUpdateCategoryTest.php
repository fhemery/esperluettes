<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi updateCategory', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $created = createFaqCategory($admin, 'Test Category');

        $this->actingAs($user);
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqCategoryDto(
            name: 'Updated Name',
            slug: $created->slug,
            description: $created->description,
            isActive: $created->isActive,
            sortOrder: $created->sortOrder,
        );

        expect(fn () => $api->updateCategory($created->id, $updateDto))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ModelNotFoundException when category does not exist', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqCategoryDto(
            name: 'Non-existent',
            slug: 'non-existent',
            description: null,
            isActive: true,
            sortOrder: 1,
        );

        expect(fn () => $api->updateCategory(999, $updateDto))
            ->toThrow(ModelNotFoundException::class);
    });

    it('should update all category fields when caller is admin', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $created = createFaqCategory($admin, 'Original Name', 'Original desc');

        $api = app(FaqPublicApi::class);
        $updateDto = new UpdateFaqCategoryDto(
            name: 'Updated Name',
            slug: 'custom-updated-slug',
            description: 'Updated description',
            isActive: false,
            sortOrder: 42,
        );

        $updated = $api->updateCategory($created->id, $updateDto);

        expect($updated->id)->toBe($created->id);
        expect($updated->name)->toBe('Updated Name');
        expect($updated->slug)->toBe('custom-updated-slug');
        expect($updated->description)->toBe('Updated description');
        expect($updated->isActive)->toBeFalse();
        expect($updated->sortOrder)->toBe(42);
        expect($updated->updatedByUserId)->toBe($admin->id);

        // Verify via getCategory
        $retrieved = getFaqCategory($created->id);
        expect($retrieved->name)->toBe('Updated Name');
        expect($retrieved->slug)->toBe('custom-updated-slug');
        expect($retrieved->description)->toBe('Updated description');
        expect($retrieved->isActive)->toBeFalse();
        expect($retrieved->sortOrder)->toBe(42);
    });

    it('should preserve created_by_user_id on update', function () {
        $admin1 = admin($this);
        $admin2 = techAdmin($this);

        $this->actingAs($admin1);
        $created = createFaqCategory($admin1, 'Test');
        expect($created->createdByUserId)->toBe($admin1->id);

        $this->actingAs($admin2);
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqCategoryDto(
            name: 'Updated',
            slug: $created->slug,
            description: $created->description,
            isActive: $created->isActive,
            sortOrder: $created->sortOrder,
        );

        $updated = $api->updateCategory($created->id, $updateDto);

        expect($updated->createdByUserId)->toBe($admin1->id);
        expect($updated->updatedByUserId)->toBe($admin2->id);

        // Verify via getCategory
        $retrieved = getFaqCategory($created->id);
        expect($retrieved->createdByUserId)->toBe($admin1->id);
        expect($retrieved->updatedByUserId)->toBe($admin2->id);
    });

    it('should allow tech-admin to update categories', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $created = createFaqCategory($admin, 'Test');

        $this->actingAs($techAdmin);
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqCategoryDto(
            name: 'Updated by Tech Admin',
            slug: 'updated-slug',
            description: 'New desc',
            isActive: true,
            sortOrder: 10,
        );

        $updated = $api->updateCategory($created->id, $updateDto);

        expect($updated->name)->toBe('Updated by Tech Admin');
        expect($updated->updatedByUserId)->toBe($techAdmin->id);
    });

    it('should allow moderator to update categories', function () {
        $admin = admin($this);
        $moderator = moderator($this);

        $this->actingAs($admin);
        $created = createFaqCategory($admin, 'Test');

        $this->actingAs($moderator);
        $api = app(FaqPublicApi::class);

        $updateDto = new UpdateFaqCategoryDto(
            name: 'Updated by Moderator',
            slug: 'updated-by-moderator',
            description: 'New desc by mod',
            isActive: true,
            sortOrder: 11,
        );

        $updated = $api->updateCategory($created->id, $updateDto);

        expect($updated->name)->toBe('Updated by Moderator');
        expect($updated->updatedByUserId)->toBe($moderator->id);
    });
});
