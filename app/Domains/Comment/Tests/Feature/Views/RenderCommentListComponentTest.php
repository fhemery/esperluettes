<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Access', function () {
    it('should display an alert if user is not logged, with a login button redirecting directly to comment area', function () {
        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain(__('comment::comments.errors.members_only'));
        expect($html)->toContain(__('comment::comments.actions.login'));
        // Regex: ensure link contains login-intended and encoded #comments anchor
        expect($html)->toMatch('/login-intended\?redirect=[^"\s>]*%23comments/');
        expect($html)->not()->toContain(__('comment::comments.list.empty'));
        expect($html)->not()->toContain('<form');
    });

    it('should display an alert if user is not verified', function () {
        $user = alice($this, roles: [], isVerified: false);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain(__('comment::comments.errors.members_only'));
        expect($html)->not()->toContain(__('comment::comments.actions.login'));
        expect($html)->not()->toContain(__('comment::comments.list.empty'));
        expect($html)->not()->toContain('<form');
    });
});

describe('Content', function () {
    it('renders the Comment list component without comments', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain(__('comment::comments.list.empty'));
    });

    it('renders the Comment list component with comments', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        // Seed one comment
        createComment('default', 123, 'Hello world', null);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain('Hello world');
    });
});

describe('When policies are in place', function(){
    it('should show a minimum number of character in the editor if specified ', function () {
        $entityType = 'default';
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register($entityType, new class extends DefaultCommentPolicy {
            public function getMinBodyLength(): ?int
            {
                return 10;
            }
        });
        
        $user = alice($this);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);


        expect($html)->toContain(__('shared::editor.min-characters', ['count' => 10]));
    });

    it('should show a maximum number of character in the editor if specified ', function () {
        $entityType = 'default';
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register($entityType, new class extends DefaultCommentPolicy {
            public function getMaxBodyLength(): ?int
            {
                return 10;
            }
        });
        
        $user = alice($this);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);


        expect($html)->toContain('/ 10');
    });

    it('should not show the form is root posting is disabled', function () {
        $entityType = 'default';
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register($entityType, new class extends DefaultCommentPolicy {
            public function canCreateRoot(int $entityId, int $userId): bool
            {
                return false;
            }
        });
        
        $user = alice($this);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="default" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);


        expect($html)->not()->toContain('<form');
    });
});