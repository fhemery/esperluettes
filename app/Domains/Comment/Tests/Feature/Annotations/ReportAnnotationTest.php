<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Moderation\Database\Seeders\ModerationSeeder;
use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Report an annotation', function () {
    beforeEach(function () {
        Cache::flush();

        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);
        $this->coAuthor = carol($this);
        addCollaborator($this->story->id, $this->coAuthor->id, 'author');
        $this->formerCoAuthor = daniel($this);

        $this->actingAs($this->reader);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage']),
            annotationItem(['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage']),
        ]))->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        [$this->first, $this->second] = CommentAnnotation::query()->orderBy('id')->get()->all();
        $this->reason = defaultReason('annotation');

        $this->reply = function (CommentAnnotation $root, int $writerId, string $body): CommentAnnotation {
            return CommentAnnotation::query()->forceCreate([
                'comment_id' => $root->comment_id,
                'parent_annotation_id' => $root->id,
                'author_id' => $writerId,
                'body' => $body,
                'highlighted_text' => '',
            ]);
        };
        $this->report = function ($viewer, int $annotationId) {
            return $this->actingAs($viewer)->postJson('/moderation/report', [
                'topic_key' => 'annotation',
                'entity_id' => $annotationId,
                'reason_id' => $this->reason->id,
            ]);
        };
    });

    describe('accepted', function () {
        it('lets the commenter report an author reply on their annotation', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Merci</p>');

            ($this->report)($this->reader, $reply->id)->assertOk();

            $report = ModerationReport::query()->sole();
            expect($report->topic_key)->toBe('annotation')
                ->and($report->entity_id)->toBe($reply->id);
        });

        it('lets an author report a reader root annotation', function () {
            ($this->report)($this->author, $this->first->id)->assertOk();

            expect(ModerationReport::query()->sole()->entity_id)->toBe($this->first->id);
        });

        it('lets an author report a reader reply', function () {
            $reply = ($this->reply)($this->first, $this->reader->id, '<p>Re</p>');

            ($this->report)($this->author, $reply->id)->assertOk();

            expect(ModerationReport::query()->sole()->entity_id)->toBe($reply->id);
        });

        it('lets a co-author report another co-author reply', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Alice répond</p>');

            ($this->report)($this->coAuthor, $reply->id)->assertOk();

            expect(ModerationReport::query()->count())->toBe(1);
        });

        it('lets a moderator report a root annotation and a reply', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Alice répond</p>');
            $moderator = moderator($this);

            ($this->report)($moderator, $this->first->id)->assertOk();
            ($this->report)($moderator, $reply->id)->assertOk();

            expect(ModerationReport::query()->count())->toBe(2);
        });

        it('lets a non-confirmed user who is a party to the thread report', function () {
            $newbie = registerUserThroughForm($this, ['name' => 'Newbie', 'email' => 'newbie@example.com'], true, [Roles::USER]);
            $comment = Comment::query()->forceCreate([
                'commentable_type' => 'chapter',
                'commentable_id' => $this->chapter->id,
                'author_id' => $newbie->id,
                'body' => '<p>Un commentaire assez long pour exister</p>',
                'is_active' => true,
            ]);
            $root = CommentAnnotation::query()->forceCreate([
                'comment_id' => $comment->id,
                'author_id' => $newbie->id,
                'body' => '<p>Mon avis</p>',
                'highlighted_text' => 'un passage',
            ]);
            $reply = ($this->reply)($root, $this->author->id, '<p>Réponse</p>');

            ($this->report)($newbie, $reply->id)->assertOk();

            expect(ModerationReport::query()->sole()->entity_id)->toBe($reply->id);
        });

        it('accepts a report on an emptied root annotation', function () {
            app(AnnotationPublicApi::class)->moderatorEmpty($this->first->id, moderator($this)->id);

            ($this->report)($this->author, $this->first->id)->assertOk();

            expect(ModerationReport::query()->sole()->content_snapshot['body'])
                ->toContain(e(__('comment::annotations.emptied_body')));
        });

        it('accepts duplicate reports by the same user', function () {
            ($this->report)($this->author, $this->first->id)->assertOk();
            ($this->report)($this->author, $this->first->id)->assertOk();

            expect(ModerationReport::query()->count())->toBe(2);
        });
    });

    describe('refused', function () {
        it('refuses a user unrelated to the thread', function () {
            $stranger = daniel($this);

            ($this->report)($stranger, $this->first->id)->assertForbidden()->assertJsonPath('success', false);

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses the writer of the root annotation', function () {
            ($this->report)($this->reader, $this->first->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses the writer of the reply', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Alice répond</p>');

            ($this->report)($this->author, $reply->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses a non-existent annotation id', function () {
            ($this->report)($this->author, 999999)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses a commenter on another reader root comment of the same chapter', function () {
            $other = Comment::query()->forceCreate([
                'commentable_type' => 'chapter',
                'commentable_id' => $this->chapter->id,
                'author_id' => $this->formerCoAuthor->id,
                'body' => '<p>Un autre commentaire assez long</p>',
                'is_active' => true,
            ]);
            $theirs = CommentAnnotation::query()->forceCreate([
                'comment_id' => $other->id,
                'author_id' => $this->formerCoAuthor->id,
                'body' => '<p>Autre avis</p>',
                'highlighted_text' => 'ailleurs',
            ]);
            $reply = ($this->reply)($theirs, $this->author->id, '<p>Réponse</p>');

            ($this->report)($this->reader, $theirs->id)->assertForbidden();
            ($this->report)($this->reader, $reply->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses when the root comment is trashed', function () {
            $this->comment->delete();

            ($this->report)($this->author, $this->first->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses a soft-deleted annotation and a reply under a soft-deleted root', function () {
            $reply = ($this->reply)($this->first, $this->reader->id, '<p>Re</p>');
            $replyOfSecond = ($this->reply)($this->second, $this->reader->id, '<p>Re 2</p>');
            $this->second->delete();
            $this->first->delete();

            ($this->report)($this->author, $this->second->id)->assertForbidden();
            ($this->report)($this->author, $reply->id)->assertForbidden();
            ($this->report)($this->author, $replyOfSecond->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });

        it('refuses a reply whose writer is deactivated', function () {
            $reply = ($this->reply)($this->first, $this->formerCoAuthor->id, '<p>Cachée</p>');
            $this->actingAs(admin($this));
            app(AuthPublicApi::class)->deactivateUserById($this->formerCoAuthor->id);
            auth()->logout();

            ($this->report)($this->author, $reply->id)->assertForbidden();

            expect(ModerationReport::query()->count())->toBe(0);
        });
    });

    describe('snapshot and metadata', function () {
        it('freezes passage and body for a root annotation', function () {
            ($this->report)($this->author, $this->first->id)->assertOk();

            $snapshot = ModerationReport::query()->sole()->content_snapshot;
            expect($snapshot['highlighted_text'])->toBe('premier passage')
                ->and($snapshot['body'])->toContain('Premier avis')
                ->and($snapshot)->not->toHaveKey('parent');
        });

        it('freezes the reply body and its parent passage and body', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Merci</p>');

            ($this->report)($this->reader, $reply->id)->assertOk();

            $snapshot = ModerationReport::query()->sole()->content_snapshot;
            expect($snapshot['body'])->toContain('Merci')
                ->and($snapshot['parent']['highlighted_text'])->toBe('premier passage')
                ->and($snapshot['parent']['body'])->toContain('Premier avis');
        });

        it('sets reported_user_id to the annotation writer', function () {
            $reply = ($this->reply)($this->first, $this->author->id, '<p>Merci</p>');

            ($this->report)($this->reader, $reply->id)->assertOk();
            ($this->report)($this->author, $this->first->id)->assertOk();

            expect(ModerationReport::query()->orderBy('id')->pluck('reported_user_id')->all())
                ->toBe([$this->author->id, $this->reader->id]);
        });

        it('links the report to the root comment URL', function () {
            ($this->report)($this->author, $this->first->id)->assertOk();

            $expected = app(CommentPolicyRegistry::class)->getUrl('chapter', $this->chapter->id, $this->comment->id);
            expect($expected)->not->toBeNull()
                ->and(ModerationReport::query()->sole()->content_url)->toBe($expected);
        });

        it('keeps the report and its snapshot after the annotation is deleted', function () {
            ($this->report)($this->author, $this->first->id)->assertOk();
            $moderator = moderator($this);

            app(AnnotationPublicApi::class)->moderatorDelete($this->first->id, $moderator->id);

            $report = ModerationReport::query()->sole();
            expect($report->content_snapshot['highlighted_text'])->toBe('premier passage');
            $this->actingAs($moderator)
                ->get(route('moderation.admin.moderation-reports.show', $report))
                ->assertOk()
                ->assertSee('premier passage');
        });

        it('seeds an Other reason for the annotation topic', function () {
            $this->seed(ModerationSeeder::class);

            expect(ModerationReason::where('topic_key', 'annotation')->where('label', 'Other')->count())->toBe(1);
        });
    });
});
