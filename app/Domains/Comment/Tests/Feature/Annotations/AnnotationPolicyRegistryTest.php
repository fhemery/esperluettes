<?php

use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Comment policy registry — annotation methods', function () {
    it('answers the defaults for an entity type without a policy', function () {
        $registry = new CommentPolicyRegistry();

        expect($registry->canAnnotate('unregistered', 1, 2))->toBeFalse()
            ->and($registry->canMarkAsProcessed('unregistered', 1, 2))->toBeFalse()
            ->and($registry->getAnnotationBodyMaxLength('unregistered'))->toBe(1000)
            ->and($registry->getAnnotationHighlightMaxLength('unregistered'))->toBe(500);
    });

    it('delegates the four annotation methods to a registered policy', function () {
        $registry = new CommentPolicyRegistry();
        $registry->register('custom', new class extends DefaultCommentPolicy {
            public function canAnnotate(int $entityId, int $userId): bool
            {
                return $entityId === 7 && $userId === 3;
            }

            public function canMarkAsProcessed(int $entityId, int $userId): bool
            {
                return $entityId === 7 && $userId === 4;
            }

            public function getAnnotationBodyMaxLength(): ?int
            {
                return 42;
            }

            public function getAnnotationHighlightMaxLength(): ?int
            {
                return null;
            }
        });

        expect($registry->canAnnotate('custom', 7, 3))->toBeTrue()
            ->and($registry->canAnnotate('custom', 7, 4))->toBeFalse()
            ->and($registry->canMarkAsProcessed('custom', 7, 4))->toBeTrue()
            ->and($registry->canMarkAsProcessed('custom', 7, 3))->toBeFalse()
            ->and($registry->getAnnotationBodyMaxLength('custom'))->toBe(42)
            ->and($registry->getAnnotationHighlightMaxLength('custom'))->toBeNull();
    });
});
