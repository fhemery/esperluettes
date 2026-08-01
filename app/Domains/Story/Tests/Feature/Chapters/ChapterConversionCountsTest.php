<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * §4.6.2 acceptance criterion: converting a chapter without changing a word must
 * not move its word_count or character_count. Every assertion here compares
 * against values *captured* before the conversion — never against a constant.
 * A red run means the feature is broken, not the test.
 */
describe('Chapter conversion counts', function () {

    beforeEach(function () {
        $this->author = alice($this);
        $this->actingAs($this->author);
        $this->story = createStoryForAuthor($this->author->id, ['title' => 'Counting Story']);
    });

    it('keeps both counts when converting a chapter without changing a word', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), validChapterPayload([
            'title' => 'Convertible',
            'content' => '<p>Il était une fois un très long chapitre plein de mots.</p><p>Et un second paragraphe.</p>',
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();
        $storedContent = (string) $chapter->content;
        $wordsBefore = (int) $chapter->word_count;
        $charsBefore = (int) $chapter->character_count;
        expect($wordsBefore)->toBeGreaterThan(0);

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]), [
            'title' => 'Convertible',
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => $storedContent]],
        ])->assertRedirect();

        $chapter->refresh();
        expect($chapter->content_blocks)->toBeArray();
        expect((int) $chapter->word_count)->toBe($wordsBefore);
        expect((int) $chapter->character_count)->toBe($charsBefore);
    });

    it('does not count image alt text and captions', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), validChapterPayload([
            'title' => 'Illustrated',
            'content' => '<p>Un texte court.</p>',
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();
        $storedContent = (string) $chapter->content;
        $wordsBefore = (int) $chapter->word_count;
        $charsBefore = (int) $chapter->character_count;

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]), [
            'title' => 'Illustrated',
            'mode' => 'advanced',
            'blocks_order' => 'b0,b1',
            'blocks' => [
                'b0' => ['type' => 'text', 'html' => $storedContent],
                'b1' => [
                    'type' => 'image',
                    'path' => 'chapters/' . $this->author->id . '/sep.jpg',
                    'alt' => 'Une description alternative particulièrement longue et bavarde',
                    'caption' => 'Et une légende tout aussi longue, pleine de mots inutiles',
                ],
            ],
        ])->assertRedirect();

        $chapter->refresh();
        expect($chapter->content_blocks)->toHaveCount(2);
        expect((int) $chapter->word_count)->toBe($wordsBefore);
        expect((int) $chapter->character_count)->toBe($charsBefore);
    });

    it('keeps the counts when returning to simple mode', function () {
        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), validChapterPayload([
            'title' => 'Round Trip',
            'content' => '<p>Un aller et un retour.</p>',
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();
        $storedContent = (string) $chapter->content;
        $wordsBefore = (int) $chapter->word_count;
        $charsBefore = (int) $chapter->character_count;

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]), [
            'title' => 'Round Trip',
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => $storedContent]],
        ])->assertRedirect();

        $chapter->refresh();
        expect($chapter->content_blocks)->not->toBeNull();

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $chapter->slug,
        ]), [
            'title' => 'Round Trip',
            'mode' => 'simple',
            'content' => $storedContent,
        ])->assertRedirect();

        $chapter->refresh();
        expect($chapter->content_blocks)->toBeNull();
        expect((int) $chapter->word_count)->toBe($wordsBefore);
        expect((int) $chapter->character_count)->toBe($charsBefore);
    });
});
