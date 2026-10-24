<?php

use App\Domains\Comment\Private\Support\Moderation\AnnotationSnapshotFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AnnotationSnapshotFormatter', function () {
    beforeEach(function () {
        $this->formatter = app(AnnotationSnapshotFormatter::class);
    });

    it('captures an empty array for a missing annotation', function () {
        expect($this->formatter->capture(987654))->toBe([]);
    });

    it('returns 0 as reported user and / as url for a missing annotation', function () {
        expect($this->formatter->getReportedUserId(987654))->toBe(0)
            ->and($this->formatter->getContentUrl(987654))->toBe('/');
    });

    it('renders the passage as escaped text and the parent block for a reply', function () {
        $html = $this->formatter->render([
            'highlighted_text' => '',
            'body' => '<p>Ma réponse</p>',
            'parent' => ['highlighted_text' => '<script>alert(1)</script>', 'body' => '<p>Avis parent</p>'],
        ]);

        expect($html)->toContain(__('comment::moderation.annotation_parent'))
            ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->and($html)->not->toContain('<script>')
            ->and($html)->toContain('<p>Ma réponse</p>')
            ->and($html)->toContain('<p>Avis parent</p>');
    });

    it('renders an empty snapshot without error', function () {
        expect($this->formatter->render([]))->toBeString();
    });
});
