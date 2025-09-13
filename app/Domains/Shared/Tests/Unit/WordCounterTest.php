<?php

declare(strict_types=1);

use App\Domains\Shared\Support\WordCounter;
use Tests\TestCase;

uses(TestCase::class);

describe('WordCounter', function () {
    it('counts words in simple English text', function () {
        expect(WordCounter::count('Hello, world!'))->toBe(2);
        expect(WordCounter::count('One two three.'))->toBe(3);
    });

    it('strips HTML and counts words', function () {
        $html = '<p>Hello <strong>world</strong> &amp; friends</p>';
        // tokens: Hello, world, friends => 3
        expect(WordCounter::count($html))->toBe(3);

        $html2 = '<div><em>Only</em> <span>HTML</span></div>';
        expect(WordCounter::count($html2))->toBe(2);
    });

    it('handles French punctuation spacing correctly', function () {
        // French often uses spaces before colon/semicolon/question mark/exclamation
        $fr = 'Bonjour : comment ça va ?';
        expect(WordCounter::count($fr))->toBe(4);

        $fr2 = 'Attention ; ceci est un test !';
        expect(WordCounter::count($fr2))->toBe(5);
    });

    it('splits on hyphens into multiple words', function () {
        expect(WordCounter::count('state-of-the-art'))->toBe(4);
        expect(WordCounter::count("état-de-l'art"))->toBe(4);
    });

    it('splits on quotes/apostrophes including typographic ones', function () {
        // Typographic apostrophe U+2019
        expect(WordCounter::count("aujourd’hui"))->toBe(2);
        // ASCII apostrophe
        expect(WordCounter::count("l'amour"))->toBe(2);
    });

    it('treats numbers as words', function () {
        expect(WordCounter::count('Version 2.0'))->toBe(3); // Version, 2, 0
        expect(WordCounter::count('2025-09-13'))->toBe(3); // 2025, 09, 13
    });

    it('returns 0 for empty or whitespace-only strings', function () {
        expect(WordCounter::count(''))->toBe(0);
        expect(WordCounter::count('   '))->toBe(0);
        expect(WordCounter::count("\n\t"))->toBe(0);
    });
});
