<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('annotation pop-up report button', function () {
    it('renders the report button template bound to row and reply can_report', function () {
        $user = alice($this);
        $this->actingAs($user);

        $html = view('comment::components.partials.annotation-modal', [
            'entityType' => 'chapter',
            'entityId' => 1,
        ])->render();

        expect($html)
            ->toContain('x-if="row.can_report"')
            ->toContain("reportButton('annotation', row.id)")
            ->toContain('x-if="reply.can_report"')
            ->toContain("reportButton('annotation', reply.id)")
            ->toContain('data-testid="annotation-report"')
            ->toContain('|| row.can_report')
            ->toContain(__('moderation::report.button'));
    });
});
