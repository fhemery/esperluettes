<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->author = alice($this);
    $this->actingAs($this->author);
    $this->story = createStoryForAuthor($this->author->id, ['title' => 'Choice Editor Story']);
});

function cceEditUrl(Story $story, Chapter $chapter): string
{
    return route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]);
}

/** A holding chapter: one text block, then one chapter-choice block. */
function cceStoreHolding(TestCase $t, Story $story, array $choices): Chapter
{
    $t->post(route('chapters.store', ['storySlug' => $story->slug]), [
        'title' => 'Holding',
        'mode' => 'advanced',
        'blocks_order' => 'b0,b1',
        'blocks' => [
            'b0' => ['type' => 'text', 'html' => '<p>Que faites-vous ?</p>'],
            'b1' => ['type' => 'chapter-choice', 'choices' => $choices],
        ],
        'published' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    return Chapter::query()->latest('id')->firstOrFail();
}

function cceDom(string $html): \Dom\HTMLDocument
{
    return \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
}

/** Option texts of the select of the chapter-choice template (new blocks). */
function cceTemplateOptions(string $html): array
{
    $template = cceDom($html)->querySelector('template[data-block-template="chapter-choice"]');
    expect($template)->not->toBeNull();
    $fragment = cceDom('<!DOCTYPE html><body>' . $template->innerHTML . '</body>');

    return array_map(
        fn ($o) => trim($o->textContent),
        iterator_to_array($fragment->querySelectorAll('select option')),
    );
}

/**
 * The choices of the stored chapter-choice blocks as the form renders them:
 * [['chapter_id' => selected value, 'option' => selected text, 'label' => …, 'enabled' => bool, 'row' => text], …].
 */
function cceRenderedChoices(string $html): array
{
    $rows = [];
    foreach (cceDom($html)->querySelectorAll('.multi-editor__blocks > [data-type="chapter-choice"] [data-choice]') as $row) {
        $selected = $row->querySelector('select option[selected]');
        $rows[] = [
            'chapter_id' => $selected?->getAttribute('value'),
            'option' => $selected ? trim($selected->textContent) : null,
            'label' => $row->querySelector('input[type="text"]')?->getAttribute('value'),
            'enabled' => $row->querySelector('input[type="checkbox"]')?->hasAttribute('checked'),
            'row' => $row->textContent,
        ];
    }

    return $rows;
}

/**
 * What the browser would submit for the rendered advanced blocks, untouched:
 * `mode`, `blocks_order` (the Alpine-bound order, from the rendered data-uid)
 * and every `blocks[…]` field outside the hidden templates, last value winning.
 */
function cceRenderedBlocksPayload(string $html): array
{
    $dom = cceDom($html);
    $pairs = [];
    foreach ($dom->querySelectorAll('.multi-editor__blocks [name^="blocks["]') as $field) {
        $name = $field->getAttribute('name');
        $tag = strtolower($field->localName);
        if ($tag === 'textarea') {
            $pairs[] = [$name, $field->textContent];
        } elseif ($tag === 'select') {
            $pairs[] = [$name, $field->querySelector('option[selected]')?->getAttribute('value') ?? ''];
        } elseif ($field->getAttribute('type') === 'checkbox') {
            if ($field->hasAttribute('checked')) {
                $pairs[] = [$name, $field->getAttribute('value') ?? 'on'];
            }
        } elseif ($field->getAttribute('type') !== 'file') {
            $pairs[] = [$name, $field->getAttribute('value') ?? ''];
        }
    }
    $query = implode('&', array_map(fn ($p) => urlencode($p[0]) . '=' . urlencode($p[1]), $pairs));
    parse_str($query, $payload);

    $uids = array_map(
        fn ($b) => $b->getAttribute('data-uid'),
        iterator_to_array($dom->querySelectorAll('.multi-editor__blocks > [data-block]')),
    );

    return $payload + ['mode' => 'advanced', 'blocks_order' => implode(',', $uids)];
}

describe('Chapter choice block — editor', function () {

    it('offers the chapter choice block in the chapter edit and create forms', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author);

        foreach ([
            route('chapters.create', ['storySlug' => $this->story->slug]),
            cceEditUrl($this->story, $chapter),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertSee("appendBlock('chapter-choice')", false)
                ->assertSee('data-block-template="chapter-choice"', false)
                ->assertSee('story::chapters.choice.block_label');
        }
    });

    it('lists every chapter of the story in reading order, drafts marked non publié, current chapter included', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Alpha']);
        createUnpublishedChapter($this, $this->story, $this->author, ['title' => 'Bravo']);
        $c = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Charlie']);
        // Alpha moved last: reading order is sort_order, not id.
        Chapter::query()->whereKey($a->id)->update(['sort_order' => 1000]);

        $html = $this->get(cceEditUrl($this->story, $c))->assertOk()->getContent();

        expect(cceTemplateOptions($html))->toBe([
            '',
            'Bravo (story::chapters.choice.unpublished)',
            'Charlie',
            'Alpha',
        ]);
    });

    it('does not list chapters of another story', function () {
        $chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Maison']);
        $other = createStoryForAuthor($this->author->id, ['title' => 'Other Story']);
        createPublishedChapter($this, $other, $this->author, ['title' => 'Étranger']);

        $html = $this->get(cceEditUrl($this->story, $chapter))->assertOk()->getContent();

        expect(cceTemplateOptions($html))->toBe(['', 'Maison']);
    });

    it('renders a stored choice block with its target, label and enabled state', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);
        $holding = cceStoreHolding($this, $this->story, [
            ['chapter_id' => (string) $b->id, 'label' => 'Ouvrir B', 'enabled' => '1'],
            ['chapter_id' => (string) $a->id, 'label' => '', 'enabled' => '0'],
        ]);

        $html = $this->get(cceEditUrl($this->story, $holding))->assertOk()->getContent();
        $rows = cceRenderedChoices($html);

        expect($rows)->toHaveCount(2);
        expect([$rows[0]['chapter_id'], $rows[0]['option'], $rows[0]['label'], $rows[0]['enabled']])
            ->toBe([(string) $b->id, 'Porte B', 'Ouvrir B', true]);
        expect([$rows[1]['chapter_id'], $rows[1]['option'], $rows[1]['label'], $rows[1]['enabled']])
            ->toBe([(string) $a->id, 'Porte A', '', false]);
        expect($rows[0]['row'])->not->toContain('story::chapters.choice.deleted_warning');
    });

    it('flags a stored choice whose target was deleted as Chapitre supprimé', function () {
        $doomed = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Condamné']);
        $holding = cceStoreHolding($this, $this->story, [
            ['chapter_id' => (string) $doomed->id, 'label' => 'Vers le condamné'],
        ]);
        $this->delete(route('chapters.destroy', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $doomed->slug,
        ]))->assertRedirect();

        $html = $this->get(cceEditUrl($this->story, $holding))->assertOk()->getContent();
        $rows = cceRenderedChoices($html);

        expect($rows)->toHaveCount(1);
        expect($rows[0]['chapter_id'])->toBe((string) $doomed->id);
        expect($rows[0]['option'])->toBe('story::chapters.choice.deleted');
        expect($rows[0]['row'])->toContain('story::chapters.choice.deleted_warning');
        // The deleted chapter is not offered to new blocks.
        expect(cceTemplateOptions($html))->not->toContain('Condamné');
    });

    it('keeps choice blocks on a failed validation re-render', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $b = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte B']);
        $holding = cceStoreHolding($this, $this->story, [
            ['chapter_id' => (string) $a->id, 'label' => 'Aller en A'],
        ]);
        $editUrl = cceEditUrl($this->story, $holding);

        $html = $this->from($editUrl)->followingRedirects()->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $holding->slug,
        ]), [
            'title' => 'Holding',
            'mode' => 'advanced',
            'blocks_order' => 'n3,n1',
            'blocks' => [
                'n1' => ['type' => 'text', 'html' => '<p>Texte</p>'],
                'n3' => ['type' => 'chapter-choice', 'choices' => [
                    ['chapter_id' => (string) $b->id, 'label' => 'Vers B', 'enabled' => '0'],
                    ['chapter_id' => (string) $a->id, 'label' => str_repeat('x', 121), 'enabled' => '1'],
                ]],
            ],
            'published' => '1',
        ])->assertOk()->getContent();

        $rows = cceRenderedChoices($html);
        expect($rows)->toHaveCount(2);
        expect([$rows[0]['chapter_id'], $rows[0]['label'], $rows[0]['enabled']])
            ->toBe([(string) $b->id, 'Vers B', false]);
        expect([$rows[1]['chapter_id'], $rows[1]['label'], $rows[1]['enabled']])
            ->toBe([(string) $a->id, str_repeat('x', 121), true]);
        // Submitted order kept: the choice block comes first.
        $types = array_map(
            fn ($b) => $b->getAttribute('data-type'),
            iterator_to_array(cceDom($html)->querySelectorAll('.multi-editor__blocks > [data-block]')),
        );
        expect($types)->toBe(['chapter-choice', 'text']);
    });

    it('round-trips an edit without changes', function () {
        $a = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Porte A']);
        $doomed = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Condamné']);
        $holding = cceStoreHolding($this, $this->story, [
            ['chapter_id' => (string) $doomed->id, 'label' => 'Vers le condamné'],
            ['chapter_id' => (string) $a->id, 'label' => '', 'enabled' => '0'],
            ['chapter_id' => (string) $a->id, 'label' => 'Encore A', 'enabled' => '1'],
        ]);
        $this->delete(route('chapters.destroy', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $doomed->slug,
        ]))->assertRedirect();
        $before = $holding->refresh()->content_blocks;

        $html = $this->get(cceEditUrl($this->story, $holding))->assertOk()->getContent();

        $this->put(route('chapters.update', [
            'storySlug' => $this->story->slug,
            'chapterSlug' => $holding->slug,
        ]), cceRenderedBlocksPayload($html) + ['title' => 'Holding', 'published' => '1'])
            ->assertRedirect()->assertSessionHasNoErrors();

        expect($holding->refresh()->content_blocks)->toBe($before);
    });
});
