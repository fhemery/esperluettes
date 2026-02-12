<?php

declare(strict_types=1);

use App\Domains\Shared\Support\HtmlLinkUtils;
use Tests\TestCase;

uses(TestCase::class);

describe('HtmlLinkUtils::stripExternalLinks', function () {
    it('returns null for null input', function () {
        expect(HtmlLinkUtils::stripExternalLinks(null))->toBeNull();
    });

    it('returns empty string for empty input', function () {
        expect(HtmlLinkUtils::stripExternalLinks(''))->toBe('');
    });

    it('preserves internal links (same host as app.url)', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p>Visit <a href="https://example.com/stories/my-story-1">this story</a> now.</p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->toContain('<a href="https://example.com/stories/my-story-1">');
        expect($result)->toContain('this story</a>');
    });

    it('strips external links but keeps their text content', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p>Check <a href="https://evil.com/phishing">this link</a> out.</p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->not->toContain('<a ');
        expect($result)->not->toContain('evil.com');
        expect($result)->toContain('this link');
        expect($result)->toContain('Check ');
        expect($result)->toContain(' out.');
    });

    it('preserves relative links', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p><a href="/stories/my-story-1">relative link</a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->toContain('<a href="/stories/my-story-1">');
        expect($result)->toContain('relative link</a>');
    });

    it('preserves anchor links', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p><a href="#section">anchor</a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->toContain('<a href="#section">');
    });

    it('strips links with no href', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p><a>no href</a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->not->toContain('<a>');
        expect($result)->toContain('no href');
    });

    it('handles mixed internal and external links', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p><a href="https://example.com/page">internal</a> and <a href="https://other.com">external</a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->toContain('<a href="https://example.com/page">internal</a>');
        expect($result)->not->toContain('other.com');
        expect($result)->toContain('external');
    });

    it('preserves child formatting inside stripped external links', function () {
        config(['app.url' => 'https://example.com']);
        $html = '<p><a href="https://evil.com"><strong>bold text</strong></a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->not->toContain('<a ');
        expect($result)->toContain('<strong>bold text</strong>');
    });

    it('is case-insensitive on host comparison', function () {
        config(['app.url' => 'https://Example.COM']);
        $html = '<p><a href="https://example.com/page">link</a></p>';
        $result = HtmlLinkUtils::stripExternalLinks($html);
        expect($result)->toContain('<a href="https://example.com/page">link</a>');
    });
});
