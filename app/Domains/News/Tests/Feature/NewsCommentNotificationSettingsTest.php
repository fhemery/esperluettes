<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    // Static SettingsRegistryService state must be cleared so the next test's app boot
    // can re-register the notification tab without a "already registered" exception.
    clearSettingsRegistry();
});

describe('News comment notification settings', function () {
    it('shows the news comments group and its toggle on the notification settings tab', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertSee('news::notification.settings.group_comments') // raw key — no locale
            ->assertSee('name="prefs[news.reply_comment][website]"', false);
    });

    it('lets a user turn the news reply notification off', function () {
        $author = admin($this);
        $news = News::factory()->published()->create(['created_by' => $author->id]);

        $rootCommenter = alice($this);
        $replier = bob($this);

        $this->actingAs($rootCommenter);
        $rootId = createComment('news', $news->id, generateDummyText(30), null);

        $this->actingAs($rootCommenter)
            ->post(route('notification.preferences.save'), [
                'prefs' => ['news.reply_comment' => ['website' => '0']],
            ]);

        $this->actingAs($replier);
        $replyId = createComment('news', $news->id, generateDummyText(10), $rootId);

        dispatchEvent(new CommentPosted(new CommentSnapshot(
            commentId: $replyId,
            entityType: 'news',
            entityId: $news->id,
            authorId: $replier->id,
            isReply: true,
            parentCommentId: $rootId,
            wordCount: 3,
            charCount: 10,
        )));

        $notif = getLatestNotificationByKey('news.reply_comment');
        expect($notif)->not->toBeNull();
        expect(getNotificationTargetUserIds((int) $notif->id))->not->toContain($rootCommenter->id);
    });
});
