<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Flash block error list', function () {
    it('lists a message shared by two fields once', function () {
        $html = (string) $this->withViewErrors([
            'blocks.0.image' => 'Same',
            'blocks.1.image' => 'Same',
        ])->blade('<x-shared::flash-block />');

        expect(substr_count($html, '<li>Same</li>'))->toBe(1);
    });

    it('lists every distinct message in first-occurrence order', function () {
        $html = (string) $this->withViewErrors([
            'a' => 'First',
            'b' => 'Second',
            'c' => 'First',
        ])->blade('<x-shared::flash-block />');

        expect(substr_count($html, '<li>First</li>'))->toBe(1)
            ->and(substr_count($html, '<li>Second</li>'))->toBe(1)
            ->and(strpos($html, '<li>First</li>'))->toBeLessThan(strpos($html, '<li>Second</li>'));
    });

    it('renders no error box without errors', function () {
        $html = (string) $this->withViewErrors([])->blade('<x-shared::flash-block />');

        expect($html)->not->toContain('list-disc');
    });
});
