<?php

use App\Domains\Editor\Private\Blocks\ImageBlockType;
use App\Domains\Editor\Private\Blocks\TextBlockType;
use App\Domains\Editor\Private\Support\ContentBlocksRenderer;
use App\Domains\Editor\Public\Blocks\EditorBlockRegistry;
use App\Domains\Editor\Public\Blocks\EditorBlockType;
use Tests\TestCase;

uses(TestCase::class);

function renderer(): ContentBlocksRenderer
{
    return app(ContentBlocksRenderer::class);
}

describe('ContentBlocksRenderer::render', function () {
    it('passes through sanitized text blocks', function () {
        $html = renderer()->render([
            ['type' => 'text', 'html' => '<p>Hello <strong>world</strong></p>'],
        ]);

        expect($html)->toContain('<p>Hello <strong>world</strong></p>');
        expect($html)->toContain('ce-block--text');
    });

    it('strips <img> from text blocks (multiedit-text profile)', function () {
        $html = renderer()->render([
            ['type' => 'text', 'html' => '<p>Before<img src="/x.jpg" alt="x">After</p>'],
        ]);

        expect($html)->not->toContain('<img');
        expect($html)->toContain('Before');
        expect($html)->toContain('After');
    });

    it('renders image blocks as a responsive picture with caption', function () {
        $html = renderer()->render([
            ['type' => 'image', 'path' => 'news/sep-abc.jpg', 'alt' => 'A map', 'caption' => 'Fig. 1'],
        ]);

        expect($html)->toContain('<picture');
        expect($html)->toContain('storage/news/sep-abc-800w.webp');
        expect($html)->toContain('alt="A map"');
        expect($html)->toContain('Fig. 1');
    });

    it('renders a keep_original image as a raw original (no srcset)', function () {
        $html = renderer()->render([
            ['type' => 'image', 'path' => 'news/small.png', 'alt' => 'Small', 'keep_original' => true],
        ]);

        expect($html)->not->toContain('<picture');
        expect($html)->not->toContain('srcset');
        expect($html)->toContain('storage/news/small.png');
        expect($html)->toContain('alt="Small"');
    });

    it('skips image blocks without a path', function () {
        $html = renderer()->render([
            ['type' => 'image', 'path' => null, 'alt' => 'x'],
        ]);

        expect($html)->not->toContain('<picture');
    });

    it('renders blocks in order', function () {
        $html = renderer()->render([
            ['type' => 'text', 'html' => '<p>One</p>'],
            ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'a'],
            ['type' => 'text', 'html' => '<p>Two</p>'],
        ]);

        expect(strpos($html, 'One'))->toBeLessThan(strpos($html, 'storage/news/a'));
        expect(strpos($html, 'storage/news/a'))->toBeLessThan(strpos($html, 'Two'));
    });
});

describe('ContentBlocksRenderer profiles', function () {
    $document = [
        ['type' => 'text', 'html' => '<p class="ql-align-center">Hi <span class="ql-spoiler">boo</span></p>'],
    ];

    it('defaults to the multiedit-text profile', function () use ($document) {
        expect(renderer()->render($document))
            ->toBe('<div class="ce-block ce-block--text"><p>Hi <span>boo</span></p></div>');
    });

    it('honours a passed profile', function () use ($document) {
        expect(renderer()->render($document, 'multiedit-narrative'))
            ->toBe('<div class="ce-block ce-block--text"><p class="ql-align-center">Hi <span class="ql-spoiler">boo</span></p></div>');
    });

    it('preserves alignment, spoiler and emoji classes under multiedit-narrative', function () {
        $html = renderer()->sanitizeText(
            '<p class="ql-align-right">A<span class="ql-spoiler">s</span>'
                . '<span class="ql-custom-emoji ql-custom-emoji-esperamour">e</span></p>',
            'multiedit-narrative'
        );

        expect($html)
            ->toContain('class="ql-align-right"')
            ->toContain('class="ql-spoiler"')
            ->toContain('ql-custom-emoji-esperamour');
    });

    it('strips <img> under multiedit-narrative', function () {
        $html = renderer()->sanitizeText('<p>Before<img src="/x.jpg" alt="x">After</p>', 'multiedit-narrative');

        expect($html)->not->toContain('<img');
        expect($html)->toContain('Before');
        expect($html)->toContain('After');
    });

    it('still permits internal anchor markup under multiedit-narrative', function () {
        $html = renderer()->sanitizeText('<p><a href="/stories/1">Chapitre</a></p>', 'multiedit-narrative');

        expect($html)->toContain('<a href="/stories/1">Chapitre</a>');
    });
});

/**
 * Bind a fresh registry holding the built-ins plus a fake `fake` type that
 * echoes its block and context, so the fake never leaks into other tests.
 */
function bindRendererFakeRegistry(): void
{
    $registry = new EditorBlockRegistry();
    $registry->register(new TextBlockType());
    $registry->register(new ImageBlockType());
    $registry->register(new class implements EditorBlockType {
        public function key(): string
        {
            return 'fake';
        }

        public function labelKey(): string
        {
            return 'fake.label';
        }

        public function icon(): string
        {
            return 'star';
        }

        public function editorView(): string
        {
            return 'fake::view';
        }

        public function render(array $block, array $context): string
        {
            return '<fake data-value="' . e($block['value'] ?? '') . '" data-context="'
                . e(json_encode($context)) . '"></fake>';
        }
    });
    app()->instance(EditorBlockRegistry::class, $registry);
}

describe('ContentBlocksRenderer block-type delegation', function () {
    it('delegates a registered plugin type to its render() with the consumer context', function () {
        bindRendererFakeRegistry();

        $html = renderer()->render([
            ['type' => 'text', 'html' => '<p>One</p>'],
            ['type' => 'fake', 'value' => 'x'],
            ['type' => 'text', 'html' => '<p>Two</p>'],
        ], 'multiedit-text', ['storyId' => 7]);

        $expectedContext = e(json_encode(['profile' => 'multiedit-text', 'storyId' => 7]));
        expect($html)->toContain('<fake data-value="x" data-context="' . $expectedContext . '"></fake>');
        expect(strpos($html, 'One'))->toBeLessThan(strpos($html, '<fake'));
        expect(strpos($html, '<fake'))->toBeLessThan(strpos($html, 'Two'));
    });

    it('passes the profile to plugin types under the reserved profile key', function () {
        bindRendererFakeRegistry();

        $html = renderer()->render(
            [['type' => 'fake', 'value' => 'x']],
            'multiedit-narrative',
            ['profile' => 'consumer-attempt'],
        );

        expect($html)->toContain(e(json_encode(['profile' => 'multiedit-narrative'])));
        expect($html)->not->toContain('consumer-attempt');
    });

    it('still drops a block whose type is not registered', function () {
        bindRendererFakeRegistry();

        $html = renderer()->render([
            ['type' => 'unknown', 'html' => '<p>Ghost</p>'],
            ['html' => '<p>No type</p>'],
            ['type' => 'text', 'html' => '<p>Kept</p>'],
        ]);

        expect($html)->toBe('<div class="ce-block ce-block--text"><p>Kept</p></div>');
    });
});

describe('ContentBlocksRenderer::plainText', function () {
    it('returns text blocks only, in order', function () {
        $text = renderer()->plainText([
            ['type' => 'text', 'html' => '<p>One</p>'],
            ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'ignored caption'],
            ['type' => 'text', 'html' => '<p>Two</p>'],
        ]);

        expect($text)->toBe('<p>One</p><p>Two</p>');
    });

    it('does not collapse or trim whitespace', function () {
        $html = "  <p>Deux   mots</p>\n\n<p>Et\tune tabulation</p>  ";

        expect(renderer()->plainText([['type' => 'text', 'html' => $html]]))->toBe($html);
    });
});

describe('ContentBlocksRenderer::plainTextLength', function () {
    it('sums plain-text length of text blocks only', function () {
        $len = renderer()->plainTextLength([
            ['type' => 'text', 'html' => '<p>Hello</p>'],       // 5
            ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'ignored caption'],
            ['type' => 'text', 'html' => '<p>World!</p>'],      // 6
        ]);

        expect($len)->toBe(11);
    });
});
