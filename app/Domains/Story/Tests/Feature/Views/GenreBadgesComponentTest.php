<?php

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

describe('GenreBadges component', function () {
    it('renders nothing special when genres are empty', function () {
        $html = Blade::render('<x-story::genre-badges :genres="$genres" />', [
            'genres' => [],
        ]);

        expect($html)->not()->toContain('+');
    });

    it('shows all when they fit within width (no +X)', function () {
        $genres = ['AA', 'BB', 'CC']; // very short -> should fit
        $html = Blade::render('<x-story::genre-badges :genres="$genres" />', compact('genres'));

        expect($html)->toContain('AA')
            ->toContain('BB')
            ->toContain('CC')
            ->not()->toContain('+');
    });

    it('shows +X when overflow occurs, keeping room for +X (may hide all when none fits)', function () {
        $genres = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon'];
        // Force overflow quickly by inflating badgeBase
        $html = Blade::render('<x-story::genre-badges :genres="$genres" :badge-base="200" />', compact('genres'));

        // With badgeBase=200 and totalWidth=250, no badge can fit in the visible row while reserving +X
        // 5 remain hidden (allow for arbitrary wrapping); hidden badges are listed inside the popover content
        expect($html)->toContain('+5');
    });
});
