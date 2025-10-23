<?php

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Delete moderation report', function () {
    it('requires admin/tech-admin/moderator role', function () {
        $reporter = alice($this);
        $reason = createReason('comment', 'Harassment');

        // Create pending report via HTTP endpoint
        $reportId = $this->actingAs($reporter)
            ->postJson('/moderation/report', [
                'topic_key' => 'comment',
                'entity_id' => 42,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Regular user cannot delete
        $this->actingAs(bob($this));
        $api = app(ModerationPublicApi::class);
        expect(fn () => $api->deleteReport($reportId))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        // Ensure still exists
        $this->assertDatabaseHas('moderation_reports', [
            'id' => $reportId,
        ]);
    });

    it('moderator can delete a report and it is removed from DB', function () {
        $reporter = alice($this);
        $moderator = moderator($this);
        $reason = createReason('profile', 'Spam');

        $reportId = $this->actingAs($reporter)
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 1,
                'reason_id' => $reason->id,
            ])->json('report_id');

        // Sanity
        expect(ModerationReport::find($reportId))->not->toBeNull();

        // Delete
        $this->actingAs($moderator);
        app(ModerationPublicApi::class)->deleteReport($reportId);

        // Assert gone
        $this->assertDatabaseMissing('moderation_reports', [
            'id' => $reportId,
        ]);
    });

    it('clears pending count cache when deleting', function () {
        $reason = createReason('profile', 'Off-topic');

        // Create two pending reports
        $id1 = $this->actingAs(alice($this))->postJson('/moderation/report', [
            'topic_key' => 'profile',
            'entity_id' => 1,
            'reason_id' => $reason->id,
        ])->json('report_id');
        $id2 = $this->actingAs(alice($this))->postJson('/moderation/report', [
            'topic_key' => 'profile',
            'entity_id' => 2,
            'reason_id' => $reason->id,
        ])->json('report_id');

        // Warm the cached count (should be 2)
        $count1 = app(\App\Domains\Moderation\Private\Services\ModerationService::class)->getPendingReportsCount();
        expect($count1)->toBe(2);

        // Delete one report
        $this->actingAs(admin($this));
        app(ModerationPublicApi::class)->deleteReport($id1);

        // Count should now be 1 (cache invalidated)
        $count2 = app(\App\Domains\Moderation\Private\Services\ModerationService::class)->getPendingReportsCount();
        expect($count2)->toBe(1);

        // DB also reflects
        $this->assertDatabaseMissing('moderation_reports', ['id' => $id1]);
        $this->assertDatabaseHas('moderation_reports', ['id' => $id2, 'status' => 'pending']);
    });
});
