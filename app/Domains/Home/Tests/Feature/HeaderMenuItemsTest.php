<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class, RefreshDatabase::class);

function extractFirstDivById(string $html, string $id): string
{
    $start = strpos($html, $id);
    expect($start)->not()->toBeFalse("Start marker not found: {$id}");
    // Find the beginning of the div tag that contains this class fragment
    $divOpen = strrpos(substr($html, 0, $start), '<div');
    expect($divOpen)->not()->toBeFalse('Enclosing div not found');

    $offset = $divOpen;
    $depth = 0;
    $len = strlen($html);
    for ($i = $offset; $i < $len; $i++) {
        if (substr($html, $i, 4) === '<div') {
            $depth++;
            $i += 3; // skip 'div'
            continue;
        }
        if (substr($html, $i, 6) === '</div>') {
            $depth--;
            if ($depth === 0) {
                // include closing tag
                $end = $i + 6;
                return substr($html, $offset, $end - $offset);
            }
            $i += 5; // skip '/div>'
        }
    }
    Assert::fail('Closing </div> not found for section');
}

function countClickables(string $html): int
{
    $anchors = preg_match_all('/<a\s[^>]*href=/i', $html) ?: 0;
    return $anchors;
}

function extractClickables(string $html): array
{
    $items = [];
    if (preg_match_all('/<a\s[^>]*href=(\"|\')(.*?)(\1)[^>]*>(.*?)<\/a>/is', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $href = html_entity_decode(trim($match[2]));
            // Strip tags inside link, collapse whitespace
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($match[4])));
            $items[] = '[a] ' . $text . ' => ' . $href;
        }
    }
    return $items;
}

function assertSameClickableCountOrDiff(string $desktopHtml, string $responsiveHtml, string $scenario): void
{
    $dc = countClickables($desktopHtml);
    $rc = countClickables($responsiveHtml);
    if ($dc !== $rc) {
        $d = extractClickables($desktopHtml);
        $r = extractClickables($responsiveHtml);
        $msg = "Menu clickable count (links + buttons) differs for {$scenario}.\n" .
            "Desktop ({$dc}) vs Responsive ({$rc})\n" .
            "Desktop items:\n- " . implode("\n- ", $d) . "\n" .
            "Responsive items:\n- " . implode("\n- ", $r);
        Assert::fail($msg);
    }
    expect($dc)->toBe($rc);
}

it('unverified: desktop and responsive header have same number of top-level links', function () {
    $user = alice($this, [], false); // unverified
    $this->actingAs($user);

    $html = $this->get(route('verification.notice'))
        ->assertOk()
        ->getContent();

    // Desktop: left-side main nav links
    $desktopSection = extractFirstDivById($html, 'desktop-nav');

    // Responsive: authenticated responsive top links
    $responsiveSection = extractFirstDivById($html, 'mobile-nav');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'unverified');
});

it('verified: desktop and responsive header have same number of top-level links', function () {
    $user = alice($this); // verified
    $this->actingAs($user);

    $html = $this->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    $desktopSection = extractFirstDivById($html, 'desktop-nav');

    $responsiveSection = extractFirstDivById($html, 'mobile-nav');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'verified');
});

it('admins: desktop and responsive header have same number of top-level links', function () {
    $user = admin($this);
    $this->actingAs($user);

    $html = $this->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    $desktopSection = extractFirstDivById($html, 'desktop-nav');

    $responsiveSection = extractFirstDivById($html, 'mobile-nav');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'admins');
});
