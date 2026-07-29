<?php

use App\Domains\Editor\Private\Support\ContentBlocksRenderer;
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
