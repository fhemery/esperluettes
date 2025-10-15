<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('moderation report form display', function () {
    it('renders form with active reasons only', function () {
        $user = alice($this);

        seedReasons('profile', ['Inappropriate image', 'Spam']);
        createReason('profile', 'Old reason', isActive: false);

        $resp = $this->actingAs($user)
            ->get('/moderation/report-form/profile/123');

        $resp->assertOk()
            ->assertSee('Inappropriate image')
            ->assertSee('Spam')
            ->assertDontSee('Old reason');
    });

    it('contains all required form fields and buttons', function () {
        $user = alice($this);
        seedReasons('profile', ['Other']);

        $resp = $this->actingAs($user)
            ->get('/moderation/report-form/profile/456');

        $resp->assertOk();

        // Select for reason
        $resp->assertSee('id="reason_id"', false);
        // Textarea for description
        $resp->assertSee('id="description"', false);
        // Submit button label
        $resp->assertSee(__('moderation::report.submit'));
        // Cancel button label
        $resp->assertSee(__('moderation::report.cancel'));
        // Modal title
        $resp->assertSee(__('moderation::report.modal_title'));
    });

    it('returns 404 for invalid topic', function () {
        $user = alice($this);

        $resp = $this->actingAs($user)
            ->get('/moderation/report-form/invalid-topic/123');

        $resp->assertNotFound();
    });

    it('requires authentication to get form', function () {
        $resp = $this->get('/moderation/report-form/profile/123');

        $resp->assertRedirect('/login');
    });
});
