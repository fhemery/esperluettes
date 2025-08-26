<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class, RefreshDatabase::class);

function extractFirstDivByClass(string $html, string $classFragment): string
{
    $start = strpos($html, $classFragment);
    expect($start)->not()->toBeFalse("Start marker not found: {$classFragment}");
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
    $buttons = preg_match_all('/<button\b[^>]*>/i', $html) ?: 0;
    return $anchors + $buttons;
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
    if (preg_match_all('/<button\b([^>]*)>(.*?)<\/button>/is', $html, $b, PREG_SET_ORDER)) {
        foreach ($b as $match) {
            $attrs = $match[1] ?? '';
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($match[2] ?? '')));
            $classes = '';
            if (preg_match('/class\s*=\s*(\"|\')(.*?)(\1)/i', $attrs, $cm)) {
                $classes = trim($cm[2]);
            }
            $items[] = '[button] ' . $text . ($classes ? ' {'.$classes.'}' : '');
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

it('guest: desktop and responsive header have same number of top-level links', function () {
    $this->assertGuest();

    $html = $this->get('/')
        ->assertOk()
        ->getContent();

    // Desktop: combine left main links (news, stories) + right guest actions (login, register)
    $desktopLeft = extractFirstDivByClass($html, 'hidden space-x-8 sm:-my-px sm:ms-10 sm:flex');
    $desktopRight = extractFirstDivByClass($html, 'hidden sm:flex sm:items-center sm:ms-6 gap-3');
    $desktopSection = $desktopLeft . $desktopRight;

    // Responsive: guest menu block (first links container inside guest responsive wrapper)
    [$before, $responsiveAll] = explode('<!-- Responsive Navigation Menu -->', $html, 2);
    $responsiveSection = extractFirstDivByClass($responsiveAll, 'pt-2 pb-3 space-y-1');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'guest');
});

it('unverified: desktop and responsive header have same number of top-level links', function () {
    $user = alice($this, [], false); // unverified
    $this->actingAs($user);

    $html = $this->get('/')
        ->assertOk()
        ->getContent();

    // Desktop: left-side main nav links
    $desktopSection = extractFirstDivByClass($html, 'hidden space-x-8 sm:-my-px sm:ms-10 sm:flex');

    // Responsive: authenticated responsive top links
    [$before, $responsiveAll] = explode('<!-- Responsive Navigation Menu -->', $html, 2);
    $responsiveSection = extractFirstDivByClass($responsiveAll, 'pt-2 pb-3 space-y-1');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'unverified');
});

it('verified: desktop and responsive header have same number of top-level links', function () {
    $user = alice($this); // verified
    $this->actingAs($user);

    $html = $this->get('/')
        ->assertOk()
        ->getContent();

    $desktopSection = extractFirstDivByClass($html, 'hidden space-x-8 sm:-my-px sm:ms-10 sm:flex');

    [$before, $responsiveAll] = explode('<!-- Responsive Navigation Menu -->', $html, 2);
    $responsiveSection = extractFirstDivByClass($responsiveAll, 'pt-2 pb-3 space-y-1');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'verified');
});

it('admins: desktop and responsive header have same number of top-level links', function () {
    $user = admin($this);
    $this->actingAs($user);

    $html = $this->get('/')
        ->assertOk()
        ->getContent();

    $desktopSection = extractFirstDivByClass($html, 'hidden space-x-8 sm:-my-px sm:ms-10 sm:flex');

    [$before, $responsiveAll] = explode('<!-- Responsive Navigation Menu -->', $html, 2);
    $responsiveSection = extractFirstDivByClass($responsiveAll, 'pt-2 pb-3 space-y-1');

    assertSameClickableCountOrDiff($desktopSection, $responsiveSection, 'admins');
});
