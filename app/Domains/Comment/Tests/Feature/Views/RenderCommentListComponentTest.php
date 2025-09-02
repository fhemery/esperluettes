<?php

use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Access', function () {
    it('should display an alert if user is not logged', function () {
        $html = Blade::render('<x-comment-list entity-type="chapter" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain(__('comment::comments.errors.members_only'));
        expect($html)->toContain(__('comment::comments.actions.login'));
        expect($html)->not()->toContain(__('comment::comments.list.empty'));
        expect($html)->not()->toContain('<form');
    });

    it('should display an alert if user is not verified', function () {
        $user = alice($this, roles: [], isVerified: false);
        $this->actingAs($user);

        $html = Blade::render('<x-comment-list entity-type="chapter" :entity-id="$id" :per-page="10" />', [
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

        $html = Blade::render('<x-comment-list entity-type="chapter" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain(__('comment::comments.list.empty'));
    });

    it('renders the Comment list component with comments', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        // Seed one comment
        createComment(app(CommentPublicApi::class), 'chapter', 123, 'Hello world', null);

        $html = Blade::render('<x-comment-list entity-type="chapter" :entity-id="$id" :per-page="10" />', [
            'id' => 123,
        ]);

        expect($html)->toContain('Hello world');
    });
});
