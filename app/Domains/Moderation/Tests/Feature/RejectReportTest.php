<?php

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
use App\Domains\Moderation\Public\Events\ReportRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Reject (dismiss) moderation report', function () {
    it('requires admin/moderator role', function () {
        $reporter = alice($this);
        $reason = createReason('story', 'Offensive');

        // Create pending report
        $reportId = $this->actingAs($reporter)
            ->postJson('/moderation/report', [
                'topic_key' => 'story',
                'entity_id' => 77,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Regular user cannot reject
        $this->actingAs(bob($this));
        $api = app(ModerationPublicApi::class);
        expect(fn() => $api->rejectReport($reportId))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        // Tech-admin can reject
        $this->actingAs(techAdmin($this));
        $api->rejectReport($reportId);

        $this->assertDatabaseHas('moderation_reports', [
            'id' => $reportId,
            'status' => 'dismissed',
        ]);

        $event = latestEventOf(ReportRejected::name(), ReportRejected::class);
        expect($event)->not->toBeNull();
        expect($event->reportId)->toBe($reportId);
    });

    it('moderator can reject pending report and it updates cache via service flow', function () {
        $moderator = moderator($this);
        $reason = createReason('comment', 'Harassment');

        // Create two pending reports
        $reportId1 = $this->actingAs(alice($this))
            ->postJson('/moderation/report', [
                'topic_key' => 'comment',
                'entity_id' => 100,
                'reason_id' => $reason->id,
            ])->json('report_id');
        $reportId2 = $this->actingAs(alice($this))
            ->postJson('/moderation/report', [
                'topic_key' => 'comment',
                'entity_id' => 101,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Reject one
        $this->actingAs($moderator);
        app(ModerationPublicApi::class)->rejectReport($reportId2);

        // Verify DB change
        $this->assertDatabaseHas('moderation_reports', [ 'id' => $reportId2, 'status' => 'dismissed' ]);
        $this->assertDatabaseHas('moderation_reports', [ 'id' => $reportId1, 'status' => 'pending' ]);

        // Sanity: ensure model reflects
        expect(ModerationReport::find($reportId2)->status)->toBe('dismissed');
    });

    describe('Events', function () {
        it('should emit event on rejection', function () {
            $moderator = moderator($this);
            $reason = createReason('profile', 'Spam');

            // Create two pending reports
            $reportId1 = $this->actingAs(alice($this))
                ->postJson('/moderation/report', [
                    'topic_key' => 'profile',
                    'entity_id' => 1,
                    'reason_id' => $reason->id,
                ])->json('report_id');

            // Approve one
            $this->actingAs($moderator);
            app(ModerationPublicApi::class)->rejectReport($reportId1);

            $event = latestEventOf(ReportRejected::name(), ReportRejected::class);
            expect($event)->not->toBeNull();
            expect($event->reportId)->toBe($reportId1);
        });
    });
});
