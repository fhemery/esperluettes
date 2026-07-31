<?php

use App\Domains\Editor\Public\Api\EditorPublicApi;
use Tests\TestCase;

uses(TestCase::class);

function editorApi(): EditorPublicApi
{
    return app(EditorPublicApi::class);
}

describe('EditorPublicApi', function () {
    it('resolves from the container without an explicit binding', function () {
        expect(editorApi())->toBeInstanceOf(EditorPublicApi::class);
    });

    it('renders a text block exactly as the renderer does', function () {
        $blocks = [['type' => 'text', 'html' => '<p>Hello <strong>world</strong></p>']];

        expect(editorApi()->render($blocks))
            ->toBe(app(\App\Domains\Editor\Private\Support\ContentBlocksRenderer::class)->render($blocks))
            ->toContain('<div class="ce-block ce-block--text">')
            ->toContain('<p>Hello <strong>world</strong></p>');
    });

    it('renders an image block exactly as the renderer does', function () {
        $blocks = [['type' => 'image', 'path' => 'news/sep-abc.jpg', 'alt' => 'A map', 'caption' => 'Fig. 1']];

        expect(editorApi()->render($blocks))
            ->toBe(app(\App\Domains\Editor\Private\Support\ContentBlocksRenderer::class)->render($blocks))
            ->toContain('ce-block ce-block--image')
            ->toContain('<picture')
            ->toContain('alt="A map"');
    });

    it('renders a mixed document in order', function () {
        $html = editorApi()->render([
            ['type' => 'text', 'html' => '<p>One</p>'],
            ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'a'],
            ['type' => 'text', 'html' => '<p>Two</p>'],
        ]);

        expect($html)->toContain('ce-block--text');
        expect($html)->toContain('ce-block--image');
        expect(strpos($html, 'One'))->toBeLessThan(strpos($html, 'storage/news/a'));
        expect(strpos($html, 'storage/news/a'))->toBeLessThan(strpos($html, 'Two'));
    });

    it('delegates sanitizeText, stripping images', function () {
        $html = editorApi()->sanitizeText('<p>Before<img src="/x.jpg" alt="x">After</p>');

        expect($html)->not->toContain('<img');
        expect($html)->toContain('Before');
        expect($html)->toContain('After');
    });

    it('delegates plainTextLength over text blocks only', function () {
        $len = editorApi()->plainTextLength([
            ['type' => 'text', 'html' => '<p>Hello</p>'],
            ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'ignored caption'],
            ['type' => 'text', 'html' => '<p>World!</p>'],
        ]);

        expect($len)->toBe(11);
    });
});
