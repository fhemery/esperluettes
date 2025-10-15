<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReportButton component', function () {
    it('does not render when moderation.reporting toggle is OFF', function () {
        // Ensure toggle is OFF
        createFeatureToggle($this, new FeatureToggle('reporting', 'moderation', access: FeatureToggleAccess::OFF));

        $html = view('moderation::components.report-button', [
            'topicKey' => 'profile',
            'entityId' => 123,
        ])->render();

        expect(trim($html))->toBe('');
    });

    it('renders when moderation.reporting toggle is ON', function () {
        // Enable toggle
        createFeatureToggle($this, new FeatureToggle('reporting', 'moderation', access: FeatureToggleAccess::ON));

        $html = view('moderation::components.report-button', [
            'topicKey' => 'profile',
            'entityId' => 456,
        ])->render();

        expect($html)->toContain(__('moderation::report.button'));
    });
});
