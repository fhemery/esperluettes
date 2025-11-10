<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReportButton component', function () {
    it('renders properly', function () {
        $html = view('moderation::components.report-button', [
            'topicKey' => 'profile',
            'entityId' => 456,
        ])->render();

        expect($html)->toContain(__('moderation::report.button'));
    });
});
