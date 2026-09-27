<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->author = alice($this);
    $this->actingAs($this->author);
    $this->story = createStoryForAuthor($this->author->id, ['title' => 'Choice Story']);
});

/**
 * Advanced payload: one text block followed by one chapter-choice block.
 *
 * @param array<int, array<string, mixed>> $choices
 */
function choicePayload(array $choices, array $overrides = []): array
{
    return array_merge([
        'title' => 'Holding Chapter',
        'mode' => 'advanced',
        'blocks_order' => 'b0,b1',
        'blocks' => [
            'b0' => ['type' => 'text', 'html' => '<p>Que faites-vous ?</p>'],
            'b1' => ['type' => 'chapter-choice', 'choices' => $choices],
        ],
        'published' => '1',
    ], $overrides);
}

function storeChoiceChapter(TestCase $t, Story $story, array $choices, array $overrides = []): TestResponse
{
    return $t->post(route('chapters.store', ['storySlug' => $story->slug]), choicePayload($choices, $overrides));
}

function updateChoiceChapter(TestCase $t, Story $story, Chapter $chapter, array $choices, array $overrides = []): TestResponse
{
    return $t->put(route('chapters.update', [
        'storySlug' => $story->slug,
        'chapterSlug' => $chapter->slug,
    ]), choicePayload($choices, $overrides));
}

function latestChapter(): Chapter
{
    return Chapter::query()->latest('id')->firstOrFail();
}

function chapterHref(Story $story, Chapter $chapter): string
{
    return route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug], false);
}

describe('Chapter choice block — storage', function () {

    it('stores a choice block with the normalised shape', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => '  Ouvrir la porte  ', 'enabled' => '1'],
            ['chapter_id' => (string) $b->id, 'label' => '   ', 'enabled' => '0'],
            ['chapter_id' => (string) $a->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(latestChapter()->content_blocks[1])->toBe([
            'type' => 'chapter-choice',
            'choices' => [
                ['chapter_id' => $a->id, 'label' => 'Ouvrir la porte', 'enabled' => true],
                ['chapter_id' => $b->id, 'label' => null, 'enabled' => false],
                ['chapter_id' => $a->id, 'label' => null, 'enabled' => true],
            ],
        ]);
    });

    it('drops a choice without target and a block left without choice', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => '', 'label' => 'Rien'],
            ['chapter_id' => (string) $a->id, 'label' => 'Aller'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(latestChapter()->content_blocks[1]['choices'])->toBe([
            ['chapter_id' => $a->id, 'label' => 'Aller', 'enabled' => true],
        ]);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => '', 'label' => 'Rien'],
            ['label' => 'Toujours rien'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $chapter = latestChapter();
        expect($chapter->content_blocks)->toHaveCount(1);
        expect($chapter->content_blocks[0]['type'])->toBe('text');
    });

    it('rejects a label longer than 120 characters', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);
        $before = Chapter::count();

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => str_repeat('a', 121)],
        ])->assertSessionHasErrors('blocks.b1.choices.0.label');

        expect(Chapter::count())->toBe($before);
    });

    it('does not count labels in word and character counts', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);

        $this->post(route('chapters.store', ['storySlug' => $this->story->slug]), [
            'title' => 'Sans choix',
            'mode' => 'advanced',
            'blocks_order' => 'b0',
            'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Que faites-vous ?</p>']],
            'published' => '1',
        ])->assertRedirect();
        $without = latestChapter();

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Beaucoup de mots dans ce libellé'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $with = latestChapter();
        expect($with->id)->not->toBe($without->id);
        expect($with->content)->toContain('Beaucoup de mots');

        expect($with->word_count)->toBe($without->word_count);
        expect($with->character_count)->toBe($without->character_count);
    });
});

describe('Chapter choice block — target check', function () {

    it('rejects a choice pointing to another story\'s chapter', function () {
        $otherStory = createStoryForAuthor($this->author->id, ['title' => 'Other Story']);
        $foreign = createPublishedChapter($this, $otherStory, $this->author);
        $before = Chapter::count();

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $foreign->id, 'label' => 'Ailleurs'],
        ])->assertRedirect()->assertSessionHasErrors('blocks');

        expect(Chapter::count())->toBe($before);
    });

    it('rejects a foreign target on update and leaves the chapter untouched', function () {
        $otherStory = createStoryForAuthor($this->author->id, ['title' => 'Other Story']);
        $foreign = createPublishedChapter($this, $otherStory, $this->author);
        $chapter = createPublishedChapter($this, $this->story, $this->author);
        $content = $chapter->content;

        updateChoiceChapter($this, $this->story, $chapter, [
            ['chapter_id' => (string) $foreign->id],
        ])->assertRedirect()->assertSessionHasErrors('blocks');

        $chapter->refresh();
        expect($chapter->content_blocks)->toBeNull();
        expect($chapter->content)->toBe($content);
    });

    it('rejects a choice pointing to a non-existent id never stored', function () {
        $before = Chapter::count();

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => '999999', 'label' => 'Nulle part'],
        ])->assertRedirect()->assertSessionHasErrors('blocks');

        expect(Chapter::count())->toBe($before);
    });

    it('keeps a stored choice whose target was deleted, and renders nothing for it', function () {
        $target = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Condamné']);
        $kept = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Survivant']);
        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $target->id, 'label' => 'Vers le condamné'],
            ['chapter_id' => (string) $kept->id, 'label' => 'Vers le survivant'],
        ])->assertRedirect();
        $holding = latestChapter();

        $this->delete(route('chapters.destroy', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $target->slug,
        ]))->assertRedirect();

        updateChoiceChapter($this, $this->story, $holding, [
            ['chapter_id' => (string) $target->id, 'label' => 'Vers le condamné'],
            ['chapter_id' => (string) $kept->id, 'label' => 'Vers le survivant'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $holding->refresh();
        expect($holding->content_blocks[1]['choices'][0]['chapter_id'])->toBe($target->id);
        expect($holding->content)->not->toContain('Vers le condamné');
        expect($holding->content)->toContain('Vers le survivant');
    });

    it('lets a chapter target itself on update', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Boucle']);

        updateChoiceChapter($this, $this->story, $chapter, [
            ['chapter_id' => (string) $chapter->id, 'label' => 'Recommencer'],
        ], ['title' => 'Boucle'])->assertRedirect()->assertSessionHasNoErrors();

        $chapter->refresh();
        expect($chapter->content_blocks[1]['choices'][0]['chapter_id'])->toBe($chapter->id);
        expect($chapter->content)->toContain('href="' . chapterHref($this->story, $chapter) . '"');
    });
});

describe('Chapter choice block — rendering', function () {

    it('renders each enabled choice as a link to the target chapter', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Gauche'],
            ['chapter_id' => (string) $b->id, 'label' => 'Droite'],
        ])->assertRedirect();

        $content = latestChapter()->content;
        $block = Str::after($content, 'ce-block--chapter-choice');
        expect($content)->toContain('ce-block--chapter-choice');
        expect($block)->toContain('href="' . chapterHref($this->story, $a) . '"');
        expect($block)->toContain('href="' . chapterHref($this->story, $b) . '"');
        expect(chapterHref($this->story, $a))->toStartWith('/');
    });

    it('uses the label as link text and falls back to the target title when empty', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Ouvrir la porte'],
            ['chapter_id' => (string) $b->id, 'label' => ''],
        ])->assertRedirect();

        $content = latestChapter()->content;
        expect($content)->toContain('Ouvrir la porte');
        expect($content)->not->toContain('Porte A');
        expect($content)->toContain('Porte B');
    });

    it('keeps the fallback title of the last save after the target is renamed', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Ancien titre']);
        storeChoiceChapter($this, $this->story, [['chapter_id' => (string) $a->id]])->assertRedirect();
        $holding = latestChapter();

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $a->slug,
        ]), validChapterPayload(['title' => 'Nouveau titre', 'published' => '1']))->assertRedirect();

        $holding->refresh();
        expect($holding->content)->toContain('Ancien titre');
        expect($holding->content)->not->toContain('Nouveau titre');

        updateChoiceChapter($this, $this->story, $holding, [['chapter_id' => (string) $a->id]])->assertRedirect();
        expect($holding->fresh()->content)->toContain('Nouveau titre');
    });

    it('does not render a disabled choice', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Visible', 'enabled' => '1'],
            ['chapter_id' => (string) $b->id, 'label' => 'Caché', 'enabled' => '0'],
        ])->assertRedirect();

        $content = latestChapter()->content;
        expect($content)->toContain('Visible');
        expect($content)->not->toContain('Caché');
        expect($content)->not->toContain('href="' . chapterHref($this->story, $b) . '"');
    });

    it('renders nothing for a block whose choices are all disabled but keeps it in content_blocks', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Plus tard', 'enabled' => '0'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $chapter = latestChapter();
        expect($chapter->content_blocks)->toHaveCount(2);
        expect($chapter->content_blocks[1]['choices'][0]['enabled'])->toBeFalse();
        expect($chapter->content)->not->toContain('ce-block--chapter-choice');
        expect($chapter->content)->not->toContain('Plus tard');
    });

    it('renders a choice to an unpublished chapter', function () {
        $draft = createUnpublishedChapter($this, $this->story, $this->author, ['title' => 'Brouillon']);

        storeChoiceChapter($this, $this->story, [['chapter_id' => (string) $draft->id]])->assertRedirect();

        $content = latestChapter()->content;
        expect($content)->toContain('href="' . chapterHref($this->story, $draft) . '"');
        expect($content)->toContain('Brouillon');
    });

    it('never wraps a choice block in ce-block--text', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Choisir'],
        ])->assertRedirect();

        $content = latestChapter()->content;
        $choiceStart = strpos($content, 'ce-block--chapter-choice');
        $textStart = strpos($content, 'ce-block--text');
        expect($choiceStart)->not->toBeFalse();
        // The text block closes before the choice block opens: not nested.
        expect(substr_count(substr($content, $textStart, $choiceStart - $textStart), '</div>'))->toBeGreaterThanOrEqual(1);
        // The choice wrapper's own class list never carries ce-block--text.
        preg_match('/<div class="([^"]*ce-block--chapter-choice[^"]*)"/', $content, $m);
        expect($m[1] ?? '')->not->toContain('ce-block--text');
        expect(substr_count($content, 'ce-block--text'))->toBe(1);
    });

    it('escapes labels in the rendered HTML', function () {
        $a = createPublishedChapter($this, $this->story, $this->author);

        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => '<script>alert(1)</script>'],
        ])->assertRedirect();

        $chapter = latestChapter();
        expect($chapter->content_blocks[1]['choices'][0]['label'])->toBe('<script>alert(1)</script>');
        expect($chapter->content)->not->toContain('<script>');
        expect($chapter->content)->toContain('&lt;script&gt;');
    });
});

describe('Chapter choice block — reader page', function () {

    it('shows the choice links on the reader page', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        storeChoiceChapter($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Franchir la porte'],
        ])->assertRedirect();
        $holding = latestChapter();

        $this->actingAs(bob($this));
        $html = $this->get(route('chapters.show', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $holding->slug,
        ]))->assertOk()->getContent();

        $article = Str::between($html, 'data-quote-article', '</article>');
        expect($article)->toContain('ce-block--chapter-choice');
        expect($article)->toContain('href="' . chapterHref($this->story, $a) . '"');
        expect($article)->toContain('Franchir la porte');
    });
});
