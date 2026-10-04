<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function registerAnnotatableDefaultPolicy(): void
{
    app(CommentPolicyRegistry::class)->register('default', new class extends DefaultCommentPolicy {
        public function supportsAnnotations(): bool
        {
            return true;
        }
    });
}

describe('CommentListComponent', function () {
    describe('Access', function () {
        it('should display an alert if user is not logged, with a login button redirecting directly to comment area', function () {
            Auth::logout();
            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
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

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
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

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain(__('comment::comments.list.empty'));
        });

        it('renders the Comment list component with comments', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            // Seed one comment
            createComment('default', 123, 'Hello world', null);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain('Hello world');
        });
    });

    describe('When policies are in place', function () {
        it('should show a minimum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getRootCommentMinLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);


            expect($html)->toContain(__('editor::rich-text.min-characters', ['count' => 10]));
        });

        it('should show a maximum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getRootCommentMaxLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
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

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);


            expect($html)->not()->toContain('<form');
        });
    });

    describe('Annotation drafts banner', function () {
        beforeEach(fn () => registerAnnotatableDefaultPolicy());

        it('the root form contains the hidden annotations input and the banner markup for a user who can comment', function () {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render(
                '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'head-scripts\')',
                ['id' => 123]
            );

            $form = substr($html, strpos($html, 'data-comment-draft="root"'));
            $form = substr($form, 0, strpos($form, '</form>'));
            $bundleUrl = app(\Illuminate\Foundation\Vite::class)->asset('app/Domains/Comment/Resources/js/annotations/index.js');

            expect(substr_count($html, 'src="' . $bundleUrl . '"'))->toBe(1)
                ->and($form)->toContain('<input type="hidden" name="annotations"')
                ->and($form)->toContain('x-data="annotationDrafts()"')
                ->and($form)->toContain('data-entity-type="default"')
                ->and($form)->toContain('data-entity-id="123"')
                ->and($form)->toContain(e(trans_choice('comment::annotations.banner.text', 1)))
                ->and($form)->toContain(__('comment::annotations.banner.show'))
                ->and($form)->toContain(__('comment::annotations.drafts_modal.title'))
                ->and($form)->toContain(__('comment::annotations.drafts_modal.edit'))
                ->and($form)->toContain(__('comment::annotations.drafts_modal.delete'));
        });

        it('no banner when the viewer cannot create a root comment', function () {
            app(CommentPolicyRegistry::class)->register('default', new class extends DefaultCommentPolicy {
                public function supportsAnnotations(): bool
                {
                    return true;
                }

                public function canCreateRoot(int $entityId, int $userId): bool
                {
                    return false;
                }
            });
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->not->toContain('name="annotations"')
                ->and($html)->not->toContain('annotationDrafts()')
                ->and($html)->not->toContain(__('comment::annotations.banner.show'));
        });

        it('an annotations validation error is displayed in the banner', function (string $key) {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
            view()->share('errors', (new ViewErrorBag())->put('default', new MessageBag([
                $key => ['Annotation refusée ici'],
            ])));

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            $form = substr($html, strpos($html, 'data-comment-draft="root"'));
            $form = substr($form, 0, strpos($form, '</form>'));
            expect($form)->toContain('Annotation refusée ici');
        })->with(['annotations', 'annotations.0.highlighted_text']);
    });

    describe('Annotations server-mode pop-up', function () {
        beforeEach(fn () => registerAnnotatableDefaultPolicy());

        it('the annotations modal is rendered once and the bundle pushed for an authenticated viewer', function () {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render(
                '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'head-scripts\')',
                ['id' => 123]
            );

            $bundleUrl = app(\Illuminate\Foundation\Vite::class)->asset('app/Domains/Comment/Resources/js/annotations/index.js');
            $formEnd = strpos($html, '</form>');
            $modalAt = strpos($html, 'x-data="annotationsModal()"');

            expect(substr_count($html, 'src="' . $bundleUrl . '"'))->toBe(1)
                ->and(substr_count($html, 'x-data="annotationsModal()"'))->toBe(1)
                // Outside the root-comment form, so its buttons never submit it.
                ->and($modalAt)->toBeGreaterThan($formEnd)
                ->and($html)->toContain('x-on:annotations:open.window="open($event.detail.commentId)"')
                ->and($html)->toContain('data-label-one="' . e(trans_choice('comment::annotations.button', 1, ['count' => 1])) . '"')
                ->and($html)->toContain(e(__('comment::annotations.server_modal.title')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.mark_processed')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.delete')))
                // Highlighted text is bound as text, the server-sanitized body as HTML.
                ->and($html)->toContain('x-text="row.highlighted_text"')
                ->and($html)->not->toContain('x-html="row.highlighted_text"')
                ->and($html)->toContain('x-html="row.body"')
                // Commenter overlay: store context, pending markers, row actions, error line as text.
                ->and($html)->toContain('x-for="row in rows"')
                ->and($html)->toContain('data-entity-type="default"')
                ->and($html)->toContain('data-entity-id="123"')
                ->and($html)->toContain('data-delete-with-replies-confirm="' . e(__('comment::annotations.server_modal.delete_with_replies_confirm')) . '"')
                ->and($html)->toContain(e(__('comment::annotations.server_modal.pending_edited')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.pending_deleted')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.pending_added')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.edit')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.undo')))
                ->and($html)->toContain(e(__('comment::annotations.server_modal.remove_stale')))
                ->and($html)->toContain('x-text="rowError(row)"');
        });

        it('renders the reply editor in the annotations modal on a chapter', function () {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
            $max = app(CommentPolicyRegistry::class)->getAnnotationBodyMaxLength('default');

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            $modal = substr($html, strpos($html, 'x-data="annotationsModal()"'));

            expect(substr_count($html, 'data-testid="rich-text-annotation-reply-editor"'))->toBe(1)
                ->and($modal)->toContain('data-testid="rich-text-annotation-reply-editor"')
                ->and($modal)->toContain('data-reply-editor')
                ->and($modal)->toContain('id="quill-editor-area-annotation-reply-editor"')
                ->and($modal)->toContain('data-max="' . $max . '"')
                ->and($modal)->toContain('data-reply-max-length="' . $max . '"')
                ->and($modal)->toContain('x-bind:data-reply-slot="row.id"')
                ->and($modal)->toContain('x-for="reply in (row.replies ?? [])"')
                ->and($modal)->toContain('x-text="replyAuthor(reply)"')
                ->and($modal)->toContain('x-html="reply.body"')
                ->and($modal)->toContain('data-delete-reply-confirm="' . e(__('comment::annotations.replies.delete_confirm')) . '"')
                ->and($modal)->toContain('data-reply-empty="' . e(__('comment::annotations.replies.empty_body')) . '"')
                ->and($modal)->toContain('data-reply-too-long="' . e(__('comment::annotations.replies.too_long', ['max' => $max])) . '"')
                ->and($modal)->toContain(e(__('comment::annotations.replies.reply')))
                ->and($modal)->toContain(e(__('comment::annotations.replies.send')))
                ->and($modal)->toContain(e(__('comment::annotations.replies.delete')))
                ->and($modal)->toContain(e(__('comment::annotations.replies.author_hint')))
                ->and($modal)->toContain(e(__('comment::annotations.server_modal.undo_edit')));
        });

        it('the annotations modal is rendered for a viewer who cannot create a root comment', function () {
            app(CommentPolicyRegistry::class)->register('default', new class extends DefaultCommentPolicy {
                public function supportsAnnotations(): bool
                {
                    return true;
                }

                public function canCreateRoot(int $entityId, int $userId): bool
                {
                    return false;
                }
            });
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain('x-data="annotationsModal()"');
        });

        it('no annotations modal for a guest', function () {
            Auth::logout();

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->not->toContain('annotationsModal()');
        });
    });

    describe('Annotation changes save banner', function () {
        it('renders the changes banner outside the root form on a chapter for a logged-in user', function () {
            registerAnnotatableDefaultPolicy();
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            $bannerAt = strpos($html, 'x-data="annotationChangesBanner"');
            expect(substr_count($html, 'x-data="annotationChangesBanner"'))->toBe(1)
                ->and($bannerAt)->toBeGreaterThan(strpos($html, '</form>'))
                ->and($bannerAt)->toBeLessThan(strpos($html, 'x-data="annotationsModal()"'))
                ->and($html)->toContain('x-show="count > 0"')
                ->and($html)->toContain('sticky bottom-0 z-30')
                ->and($html)->toContain('role="status"')
                ->and($html)->toContain('aria-live="polite"')
                ->and($html)->toContain('data-label-many="' . e(trans_choice('comment::annotations.changes_banner.text', 2, ['count' => '__COUNT__'])) . '"')
                ->and($html)->toContain(e(__('comment::annotations.changes_banner.save')))
                ->and($html)->toContain(e(__('comment::annotations.changes_banner.show')))
                ->and($html)->toContain(e(__('comment::annotations.changes_banner.discard')));
        });

        it('renders no changes banner on a news list and for a guest', function () {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
            $news = Blade::render('<x-comment::comment-list-component entity-type="news" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            registerAnnotatableDefaultPolicy();
            Auth::logout();
            $guest = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($news)->not->toContain('annotationChangesBanner')
                ->and($guest)->not->toContain('annotationChangesBanner');
        });

        it('renders a hidden annotations button on the viewer’s own root comment at count 0', function () {
            registerAnnotatableDefaultPolicy();
            $alice = alice($this, roles: [Roles::USER_CONFIRMED]);
            $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($bob);
            $bobRootId = createComment('default', 123, 'Bob root');
            $this->actingAs($alice);
            $aliceRootId = createComment('default', 123, 'Alice root');
            createComment('default', 123, 'Alice reply', $bobRootId);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect(substr_count($html, 'data-annotations-button'))->toBe(1)
                ->and($html)->toMatch('/hidden\s+data-annotations-button\s+data-comment-id="' . $aliceRootId . '"/');
        });
    });

    describe('Entity type without annotations', function () {
        it('renders no banner, no pop-up and pushes no annotations bundle', function () {
            $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

            $html = Blade::render(
                '<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />@stack(\'head-scripts\')',
                ['id' => 123]
            );

            $bundleUrl = app(\Illuminate\Foundation\Vite::class)->asset('app/Domains/Comment/Resources/js/annotations/index.js');

            expect($html)->toContain('data-comment-draft="root"')
                ->and($html)->not->toContain($bundleUrl)
                ->and($html)->not->toContain('annotationDrafts()')
                ->and($html)->not->toContain('name="annotations"')
                ->and($html)->not->toContain('annotationsModal()');
        });
    });

    describe('Comment Share Button', function () {
        beforeEach(function () {
            $this->user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($this->user);
        });

        it('should display share button for each comment', function () {
            // Create a comment
            $commentId = createComment('story', 123, 'Test comment for sharing');

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain share button elements
            expect($html)->toContain('share');
            expect($html)->toContain('comment::comments.actions.share');
            expect($html)->toContain('comment::comments.actions.copied');
            expect($html)->toContain('navigator.clipboard.writeText');
        });

        it('should include share functionality JavaScript', function () {
            // Create a comment
            $commentId = createComment('story', 123, 'Test comment');

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain the JavaScript for copying functionality
            expect($html)->toContain('navigator.clipboard.writeText');
            expect($html)->toContain('url.searchParams.set(\'comment\'');
            expect($html)->toContain('url.hash = \'comments\'');
        });

        it('should work for both root comments and replies', function () {
            // Create a root comment and a reply
            $rootCommentId = createComment('story', 123, 'Root comment');
            $replyId = createComment('story', 123, 'Reply comment', $rootCommentId);

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain share buttons for both comments
            expect($html)->toContain('comment::comments.actions.share');

            // Should have the correct comment IDs in the JavaScript
            expect($html)->toContain('url.searchParams.set(\'comment\', ' . $rootCommentId);
            expect($html)->toContain('url.searchParams.set(\'comment\', ' . $replyId);
        });
    });
});
