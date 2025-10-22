<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('FaqPublicApi reorderCategories', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = admin($this);

        $this->actingAs($admin);
        $cat1 = createFaqCategory('Category 1');
        $cat2 = createFaqCategory('Category 2');

        $this->actingAs($user);
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderCategories([$cat2->id, $cat1->id]))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw AuthorizationException when user is not authenticated', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $cat1 = createFaqCategory('Category 1');

        \Illuminate\Support\Facades\Auth::logout();
        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderCategories([$cat1->id]))
            ->toThrow(AuthorizationException::class);
    });

    it('should throw ValidationException when non-existent category ID is provided', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $cat1 = createFaqCategory('Category 1');

        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderCategories([$cat1->id, 999]))
            ->toThrow(ValidationException::class);
    });

    it('should reorder categories by updating sort_order', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $cat1 = createFaqCategory('Category 1');
        $cat2 = createFaqCategory('Category 2');
        $cat3 = createFaqCategory('Category 3');

        // Initial order might be based on creation order
        expect($cat1->sortOrder)->toBeInt();
        expect($cat2->sortOrder)->toBeInt();
        expect($cat3->sortOrder)->toBeInt();

        $api = app(FaqPublicApi::class);

        // Reorder: cat3, cat1, cat2
        $api->reorderCategories([$cat3->id, $cat1->id, $cat2->id]);

        // Verify new order
        $reordered1 = getFaqCategory($cat3->id);
        $reordered2 = getFaqCategory($cat1->id);
        $reordered3 = getFaqCategory($cat2->id);

        expect($reordered1->sortOrder)->toBe(0);
        expect($reordered2->sortOrder)->toBe(1);
        expect($reordered3->sortOrder)->toBe(2);
    });

    it('should allow tech-admin to reorder categories', function () {
        $admin = admin($this);
        $techAdmin = techAdmin($this);

        $this->actingAs($admin);
        $cat1 = createFaqCategory('Category 1');
        $cat2 = createFaqCategory('Category 2');

        $this->actingAs($techAdmin);
        $api = app(FaqPublicApi::class);

        // Should not throw
        $api->reorderCategories([$cat2->id, $cat1->id]);

        $reordered1 = getFaqCategory($cat2->id);
        $reordered2 = getFaqCategory($cat1->id);

        expect($reordered1->sortOrder)->toBe(0);
        expect($reordered2->sortOrder)->toBe(1);
    });

    it('should handle single category reorder', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $cat1 = createFaqCategory('Category 1');

        $api = app(FaqPublicApi::class);
        $api->reorderCategories([$cat1->id]);

        $reordered = getFaqCategory($cat1->id);
        expect($reordered->sortOrder)->toBe(0);
    });

    it('should throw ValidationException for empty array', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderCategories([]))
            ->toThrow(ValidationException::class);
    });

    it('should throw ValidationException for duplicate IDs', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $cat1 = createFaqCategory('Category 1');

        $api = app(FaqPublicApi::class);

        expect(fn () => $api->reorderCategories([$cat1->id, $cat1->id]))
            ->toThrow(ValidationException::class);
    });
});
