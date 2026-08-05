<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** Same counting logic as EditorAssetsTest — local names avoid Pest redeclare. */
function commentListEditorBundleScriptCount(string $html): int
{
    return preg_match_all('#<script[^>]+src="[^"]*editor-bundle[^"]*"#', $html);
}

function commentListEditorCssLinkCount(string $html): int
{
    return preg_match_all(
        '#<link[^>]+rel="stylesheet"[^>]+href="[^"]*/editor-(?!bundle)[^"/]*\.css"#',
        $html
    );
}

describe('CommentListComponent editor assets', function () {
    it('emits editor assets when canCreateRoot is false', function () {
        $entityType = 'default';

        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        createComment($entityType, 123, 'Hello world');

        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register($entityType, new class extends DefaultCommentPolicy {
            public function canCreateRoot(int $entityId, int $userId): bool
            {
                return false;
            }
        });

        $html = Blade::render(
            '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'scripts\')',
            ['id' => 123]
        );

        expect(commentListEditorCssLinkCount($html))->toBe(1);
        expect(commentListEditorBundleScriptCount($html))->toBe(1);
    });

    it('emits editor assets exactly once when canCreateRoot is true', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $html = Blade::render(
            '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'scripts\')',
            ['id' => 123]
        );

        expect(commentListEditorCssLinkCount($html))->toBe(1);
        expect(commentListEditorBundleScriptCount($html))->toBe(1);
    });

    it('emits no editor assets for guest', function () {
        Auth::logout();

        $html = Blade::render(
            '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'scripts\')',
            ['id' => 123]
        );

        expect($html)->not->toContain('editor-bundle');
        expect(commentListEditorCssLinkCount($html))->toBe(0);
        expect(commentListEditorBundleScriptCount($html))->toBe(0);
    });

    it('emits no editor assets when listing is not allowed', function () {
        $user = alice($this, roles: [], isVerified: false);
        $this->actingAs($user);

        $html = Blade::render(
            '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'scripts\')',
            ['id' => 123]
        );

        expect($html)->not->toContain('editor-bundle');
        expect(commentListEditorCssLinkCount($html))->toBe(0);
        expect(commentListEditorBundleScriptCount($html))->toBe(0);
    });
});
