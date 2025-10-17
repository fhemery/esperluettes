<?php

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
use App\Domains\Moderation\Public\Events\ReportApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Approve moderation report', function () {
    it('requires admin/moderator role', function () {
        $reporter = alice($this);
        $reason = createReason('comment', 'Harassment');

        // Create pending report
        $reportId = $this->actingAs($reporter)
            ->postJson('/moderation/report', [
                'topic_key' => 'comment',
                'entity_id' => 42,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Regular user cannot approve
        $this->actingAs(bob($this));
        $api = app(ModerationPublicApi::class);
        expect(fn() => $api->approveReport($reportId))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        // Moderator can approve
        $this->actingAs(moderator($this));
        $api->approveReport($reportId);

        $this->assertDatabaseHas('moderation_reports', [
            'id' => $reportId,
            'status' => 'confirmed',
        ]);

        $event = latestEventOf(ReportApproved::name(), ReportApproved::class);
        expect($event)->not->toBeNull();
        expect($event->reportId)->toBe($reportId);
    });

    it('admin can approve pending report and it updates cache via service flow', function () {
        $admin = admin($this);
        $reason = createReason('profile', 'Spam');

        // Create two pending reports
        $reportId1 = $this->actingAs(alice($this))
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 1,
                'reason_id' => $reason->id,
            ])->json('report_id');
        $reportId2 = $this->actingAs(alice($this))
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 2,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Approve one
        $this->actingAs($admin);
        app(ModerationPublicApi::class)->approveReport($reportId1);

        // Verify DB change
        $this->assertDatabaseHas('moderation_reports', ['id' => $reportId1, 'status' => 'confirmed']);
        $this->assertDatabaseHas('moderation_reports', ['id' => $reportId2, 'status' => 'pending']);

        // Sanity: ensure model reflects
        expect(ModerationReport::find($reportId1)->status)->toBe('confirmed');
    });

    describe('events', function () {


        it('should emit event on approval', function () {
            $admin = admin($this);
            $reason = createReason('profile', 'Spam');

            // Create two pending reports
            $reportId1 = $this->actingAs(alice($this))
                ->postJson('/moderation/report', [
                    'topic_key' => 'profile',
                    'entity_id' => 1,
                    'reason_id' => $reason->id,
                ])->json('report_id');

            // Approve one
            $this->actingAs($admin);
            app(ModerationPublicApi::class)->approveReport($reportId1);

            $event = latestEventOf(ReportApproved::name(), ReportApproved::class);
            expect($event)->not->toBeNull();
            expect($event->reportId)->toBe($reportId1);
        });
    });
});
