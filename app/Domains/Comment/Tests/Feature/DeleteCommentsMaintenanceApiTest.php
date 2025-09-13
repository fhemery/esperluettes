<?php

declare(strict_types=1);

use App\Domains\Comment\PublicApi\CommentMaintenancePublicApi;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentMaintenancePublicApi - deleteComments', function () {
    it('deletes all comments for a given target (roots and replies)', function () {
        $user = alice($this);
        $this->actingAs($user);
        /** @var CommentPublicApi $pub */
        $pub = app(CommentPublicApi::class);
        /** @var CommentMaintenancePublicApi $maint */
        $maint = app(CommentMaintenancePublicApi::class);

        $entityType = 'default';
        $entityId = 123;

        // Create 3 root comments
        $rootIds = [];
        $long = str_repeat('a', 80);
        for ($i = 0; $i < 3; $i++) {
            $rootIds[] = $pub->create(new CommentToCreateDto($entityType, $entityId, $long . ' ' . $i, null));
        }
        // Create a reply under first root
        $replyId = $pub->create(new CommentToCreateDto($entityType, $entityId, $long . ' reply', $rootIds[0]));

        // Sanity check: listing returns items and total
        $listBefore = $pub->getFor($entityType, $entityId, page: 1, perPage: 10);
        expect($listBefore->total)->toBe(3);
        expect($listBefore->items)->toHaveCount(3);

        // Delete all comments for this target
        $affected = $maint->deleteFor($entityType, $entityId);
        expect($affected)->toBeGreaterThanOrEqual(4);

        // Listing should return 0
        $listAfter = $pub->getFor($entityType, $entityId, page: 1, perPage: 10);
        expect($listAfter->total)->toBe(0);
        expect($listAfter->items)->toHaveCount(0);
    });

    it('is idempotent and safe when there are no comments', function () {
        /** @var CommentMaintenancePublicApi $maint */
        $maint = app(CommentMaintenancePublicApi::class);
        $affected = $maint->deleteFor('story', 999999);
        expect($affected)->toBe(0);
    });
});
