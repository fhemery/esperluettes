<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Public\Api\AnnotationPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GET /comments/annotations/in-entity', function () {
    beforeEach(function () {
        Cache::flush();

        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->bob = bob($this);
        $this->carol = carol($this);

        // A root comment by $user on the chapter, with one root annotation per given passage.
        $this->rootWith = function ($user, array $passages): Comment {
            $this->actingAs($user);
            $commentId = createComment('chapter', $this->chapter->id, generateDummyText(140));
            foreach ($passages as $passage) {
                CommentAnnotation::query()->create([
                    'comment_id' => $commentId,
                    'author_id' => $user->id,
                    'body' => '<p>Avis sur ' . $passage . '</p>',
                    'highlighted_text' => $passage,
                ]);
            }
            auth()->logout();

            return Comment::query()->findOrFail($commentId);
        };
        $this->reply = function (CommentAnnotation $root, int $writerId, string $body): CommentAnnotation {
            return CommentAnnotation::query()->forceCreate([
                'comment_id' => $root->comment_id,
                'parent_annotation_id' => $root->id,
                'author_id' => $writerId,
                'body' => $body,
                'highlighted_text' => '',
            ]);
        };
        $this->url = fn (string $type = 'chapter', ?int $id = null) => '/comments/annotations/in-entity?entity_type=' . $type
            . '&entity_id=' . ($id ?? $this->chapter->id);

        $this->bobRoot = ($this->rootWith)($this->bob, ['passage de bob']);
        $this->carolRoot = ($this->rootWith)($this->carol, ['passage de carol', 'autre passage de carol']);
    });

    it('the author sees every root\'s annotations with is_processed, a co-author too', function () {
        CommentAnnotation::query()->where('highlighted_text', 'passage de bob')->update(['is_processed' => true]);
        $coAuthor = daniel($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');

        foreach ([$this->author, $coAuthor] as $viewer) {
            $response = $this->actingAs($viewer)->getJson(($this->url)())->assertOk();

            $response->assertJsonCount(2, 'lists');
            $lists = collect($response->json('lists'))->keyBy('comment_id');
            expect($lists[$this->bobRoot->id]['viewer_role'])->toBe('author')
                ->and($lists[$this->bobRoot->id]['items'])->toHaveCount(1)
                ->and($lists[$this->bobRoot->id]['items'][0]['is_processed'])->toBeTrue()
                ->and($lists[$this->bobRoot->id]['items'][0]['can_mark_as_processed'])->toBeTrue()
                ->and($lists[$this->carolRoot->id]['viewer_role'])->toBe('author')
                ->and($lists[$this->carolRoot->id]['items'])->toHaveCount(2)
                ->and($lists[$this->carolRoot->id]['items'][0]['is_processed'])->toBeFalse();
        }
    });

    it('the commenter sees only their own root, with is_processed null and never true', function () {
        CommentAnnotation::query()->update(['is_processed' => true]);

        $response = $this->actingAs($this->bob)->getJson(($this->url)())->assertOk();

        $response->assertJsonCount(1, 'lists');
        $response->assertJsonPath('lists.0.comment_id', $this->bobRoot->id);
        $response->assertJsonPath('lists.0.viewer_role', 'commenter');
        $response->assertJsonCount(1, 'lists.0.items');
        $response->assertJsonPath('lists.0.items.0.is_processed', null);
        $response->assertJsonPath('lists.0.items.0.can_edit', true);
        expect($response->getContent())->not->toContain('carol')
            ->and($response->getContent())->not->toContain('"is_processed":true');
    });

    it('a beta reader without root, another reader and a moderator who is not a commenter get no list', function () {
        $beta = daniel($this);
        addCollaborator($this->story->id, $beta->id, 'beta-reader');
        $other = registerUserThroughForm($this, ['name' => 'Erin', 'email' => 'erin@example.com'], true, [Roles::USER_CONFIRMED]);

        foreach ([$beta, $other, moderator($this)] as $viewer) {
            $response = $this->actingAs($viewer)->getJson(($this->url)())->assertOk();

            $response->assertExactJson(['lists' => []]);
        }
    });

    it('a moderator who also commented gets only their own root, as commenter', function () {
        $moderator = moderator($this);
        $modRoot = ($this->rootWith)($moderator, ['passage du modo']);

        $response = $this->actingAs($moderator)->getJson(($this->url)())->assertOk();

        $response->assertJsonCount(1, 'lists');
        $response->assertJsonPath('lists.0.comment_id', $modRoot->id);
        $response->assertJsonPath('lists.0.viewer_role', 'commenter');
        $response->assertJsonPath('lists.0.items.0.can_delete', false);
        expect($response->getContent())->not->toContain('passage de bob');
    });

    it('a guest is rejected', function () {
        $this->getJson(($this->url)())->assertUnauthorized();
        $this->get(($this->url)())->assertRedirect(route('login'));
    });

    it('excludes a trashed root comment', function () {
        $this->bobRoot->delete();

        $response = $this->actingAs($this->author)->getJson(($this->url)())->assertOk();

        $response->assertJsonCount(1, 'lists');
        $response->assertJsonPath('lists.0.comment_id', $this->carolRoot->id);
    });

    it('omits the annotations of a root comment emptied by moderation', function () {
        $this->actingAs(moderator($this))
            ->post(route('comments.moderation.empty-content', ['commentId' => $this->bobRoot->id]))
            ->assertRedirect();

        $response = $this->actingAs($this->author)->getJson(($this->url)())->assertOk();

        $response->assertJsonCount(1, 'lists');
        $response->assertJsonPath('lists.0.comment_id', $this->carolRoot->id);
        expect($response->getContent())->not->toContain('passage de bob');
    });

    it('omits roots without annotations', function () {
        ($this->rootWith)(daniel($this), []);

        $this->actingAs($this->author)->getJson(($this->url)())->assertOk()->assertJsonCount(2, 'lists');
    });

    it('filters out the reply of a deactivated writer', function () {
        $formerCoAuthor = daniel($this);
        $root = CommentAnnotation::query()->where('highlighted_text', 'passage de bob')->sole();
        ($this->reply)($root, $formerCoAuthor->id, '<p>Réponse cachée</p>');
        ($this->reply)($root, $this->author->id, '<p>Réponse visible</p>');
        $this->actingAs(admin($this));
        app(AuthPublicApi::class)->deactivateUserById($formerCoAuthor->id);
        auth()->logout();

        $response = $this->actingAs($this->bob)->getJson(($this->url)())->assertOk();

        $response->assertJsonCount(1, 'lists.0.items.0.replies');
        $response->assertJsonPath('lists.0.items.0.replies.0.author_id', $this->author->id);
        expect($response->json('lists.0.items.0.replies.0.body'))->toContain('Réponse visible');
    });

    it('keeps an anonymised commenter\'s root with an empty-name profile', function () {
        deleteUser($this, $this->bob);

        $response = $this->actingAs($this->author)->getJson(($this->url)())->assertOk();

        $lists = collect($response->json('lists'))->keyBy('comment_id');
        expect($lists)->toHaveKey($this->bobRoot->id);
        $item = $lists[$this->bobRoot->id]['items'][0];
        expect($item['author_id'])->toBeNull()
            ->and($item['author_profile']['display_name'])->toBe('');
    });

    it('answers no list for a type without annotation support or an unknown type', function () {
        foreach (['news', 'nope'] as $type) {
            $this->actingAs($this->author)->getJson(($this->url)($type, 1))
                ->assertOk()
                ->assertExactJson(['lists' => []]);
        }
    });

    it('validates entity_type and entity_id', function () {
        $this->actingAs($this->author)
            ->getJson('/comments/annotations/in-entity?entity_type=chapter')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entity_id');
        $this->actingAs($this->author)
            ->getJson('/comments/annotations/in-entity?entity_type=chapter&entity_id=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entity_id');
        $this->actingAs($this->author)
            ->getJson('/comments/annotations/in-entity?entity_id=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entity_type');
    });

    it('flags an emptied row as is_emptied and not editable for the commenter', function () {
        CommentAnnotation::query()->where('highlighted_text', 'passage de bob')->update(['content_emptied_at' => now()]);

        $response = $this->actingAs($this->bob)->getJson(($this->url)())->assertOk();

        $response->assertJsonPath('lists.0.items.0.is_emptied', true);
        $response->assertJsonPath('lists.0.items.0.can_edit', false);
    });

    it('runs the same number of queries for 1 and 5 roots', function () {
        // A fresh chapter: rootWith() posts on $this->chapter.
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Second Chap']);

        $commenters = [$this->bob, $this->carol, daniel($this)];
        foreach (['erin', 'frank'] as $name) {
            $commenters[] = registerUserThroughForm($this, ['name' => ucfirst($name), 'email' => $name . '@example.com'], true, [Roles::USER_CONFIRMED]);
        }

        $countQueries = function (): int {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $lists = app(AnnotationPublicApi::class)->getForEntity('chapter', $this->chapter->id, $this->author->id);
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();
            $this->lastLists = $lists;

            return $count;
        };

        $addRoot = function ($user) {
            $comment = ($this->rootWith)($user, ['passage de ' . $user->name]);
            ($this->reply)(CommentAnnotation::query()->where('comment_id', $comment->id)->sole(), $this->author->id, '<p>Merci</p>');
        };

        $addRoot($commenters[0]);
        $one = $countQueries();
        expect($this->lastLists)->toHaveCount(1);

        foreach (array_slice($commenters, 1) as $user) {
            $addRoot($user);
        }
        $five = $countQueries();
        expect($this->lastLists)->toHaveCount(5);

        expect($five)->toBe($one);
    });
});
