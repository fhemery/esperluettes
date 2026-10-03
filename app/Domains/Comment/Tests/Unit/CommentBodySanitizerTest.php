<?php

use App\Domains\Comment\Private\Support\CommentBodySanitizer;
use Tests\TestCase;

uses(TestCase::class);

describe('CommentBodySanitizer', function () {
    beforeEach(function () {
        $this->sanitizer = new CommentBodySanitizer();
    });

    it('strict profile is the default and still keeps lists and blockquotes', function () {
        $html = $this->sanitizer->sanitizeToHtml('<ul><li>one</li></ul><blockquote>quoted</blockquote>');

        expect($html)->toContain('<ul><li>one</li></ul>')
            ->and($html)->toContain('<blockquote>');
        expect(CommentBodySanitizer::STRICT)->toBe('strict');
    });

    it('annotation profile keeps strong, em, paragraphs, line breaks and custom-emoji spans', function () {
        $body = '<p><strong>bold</strong> <em>italic</em><br />'
            . '<span class="ql-custom-emoji ql-custom-emoji-esperamour">x</span></p>';

        $html = $this->sanitizer->sanitizeToHtml($body, CommentBodySanitizer::ANNOTATION);

        expect($html)->toContain('<p>')
            ->and($html)->toContain('<strong>bold</strong>')
            ->and($html)->toContain('<em>italic</em>')
            ->and($html)->toContain('<br>')
            ->and($html)->toContain('<span class="ql-custom-emoji ql-custom-emoji-esperamour">x</span>');
    });

    it('annotation profile strips lists, blockquotes, links, underline, alignment classes and scripts', function () {
        $body = '<p class="ql-align-center">centred</p>'
            . '<ul><li>item</li></ul>'
            . '<blockquote>quoted</blockquote>'
            . '<p><a href="https://example.com">link</a> <u>under</u></p>'
            . '<script>alert(1)</script>';

        $html = $this->sanitizer->sanitizeToHtml($body, CommentBodySanitizer::ANNOTATION);

        expect($html)->not->toContain('<ul')
            ->and($html)->not->toContain('<li')
            ->and($html)->not->toContain('<blockquote')
            ->and($html)->not->toContain('<a ')
            ->and($html)->not->toContain('href')
            ->and($html)->not->toContain('<u>')
            ->and($html)->not->toContain('ql-align-center')
            ->and($html)->not->toContain('script')
            ->and($html)->not->toContain('alert')
            ->and($html)->toContain('centred')
            ->and($html)->toContain('item')
            ->and($html)->toContain('quoted')
            ->and($html)->toContain('link')
            ->and($html)->toContain('under');
    });

    it('plainTextLength counts plain characters under the annotation profile', function () {
        $body = '<p><strong>abc</strong> <em>dé</em></p><ul><li>fg</li></ul>';

        expect($this->sanitizer->plainTextLength($body, CommentBodySanitizer::ANNOTATION))->toBe(8);
    });
});
