<?php

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

    it('compact mode exposes an aria-label and hides the icon', function () {
        $html = view('moderation::components.report-button', [
            'topicKey' => 'profile',
            'entityId' => 456,
            'compact' => true,
        ])->render();

        expect($html)
            ->toContain('aria-label="' . __('moderation::report.button') . '"')
            ->toContain('title="' . __('moderation::report.button') . '"')
            ->toContain('<span class="material-symbols-outlined" aria-hidden="true">flag</span>');
    });

    it('non-compact mode has no aria-label', function () {
        $html = view('moderation::components.report-button', [
            'topicKey' => 'profile',
            'entityId' => 456,
        ])->render();

        expect($html)->not->toContain('aria-label=');
    });
});
