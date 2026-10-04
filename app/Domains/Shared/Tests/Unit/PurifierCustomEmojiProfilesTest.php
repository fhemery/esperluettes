<?php

declare(strict_types=1);

use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;

uses(TestCase::class);

describe('Purifier profiles carrying the custom emoji classes', function () {
    it('keeps both custom emoji classes', function (string $profile, string $name) {
        $html = Purifier::clean(
            '<span class="ql-custom-emoji ql-custom-emoji-'.$name.'">x</span>',
            $profile,
        );

        expect($html)->toContain('<span class="ql-custom-emoji ql-custom-emoji-'.$name.'">x</span>');
    })->with(
        ['strict', 'strict-with-links', 'multiedit-narrative', 'quote-note', 'annotation'],
        [
            'esperamour', 'esperbravo', 'esperclindoeil', 'espercolere', 'esperfourire',
            'esperlunettes', 'espersnob', 'espersourire', 'espertriste',
        ],
    );
});
