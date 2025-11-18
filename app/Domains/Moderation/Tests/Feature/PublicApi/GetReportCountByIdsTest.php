<?php

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ModerationPublicApi::getReportCountsByUserIds', function () {

    it('should return report counts for given user ids', function () {
        $alice = alice($this);
        $bob = bob($this);
        $carol = carol($this); // Use the existing carol helper function
        
        // Create reports for alice (2 confirmed, 1 rejected)
        $aliceReport1Id = createReportForUser($alice);
        $aliceReport2Id = createReportForUser($alice);
        $aliceReport3Id = createReportForUser($alice);

        // Create reports for bob (1 confirmed, 2 rejected)
        $bobReport1Id = createReportForUser($bob);
        $bobReport2Id = createReportForUser($bob);
        $bobReport3Id = createReportForUser($bob);

        // Approve/reject reports using Public API
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);
        
        // Alice's reports: 2 confirmed, 1 rejected
        $api->approveReport($aliceReport1Id);
        $api->approveReport($aliceReport2Id);
        $api->rejectReport($aliceReport3Id);
        
        // Bob's reports: 1 confirmed, 2 rejected
        $api->approveReport($bobReport1Id);
        $api->rejectReport($bobReport2Id);
        $api->rejectReport($bobReport3Id);

        // No reports for carol

        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);

        $counts = $api->getReportCountsByUserIds([$alice->id, $bob->id, $carol->id]);
        
        expect($counts)->toBeArray();
        expect($counts[$alice->id])->toBeArray();
        expect($counts[$alice->id]['confirmed'])->toBe(2);
        expect($counts[$alice->id]['rejected'])->toBe(1);
        
        expect($counts[$bob->id])->toBeArray();
        expect($counts[$bob->id]['confirmed'])->toBe(1);
        expect($counts[$bob->id]['rejected'])->toBe(2);
        
        expect($counts[$carol->id])->toBeArray();
        expect($counts[$carol->id]['confirmed'])->toBe(0);
        expect($counts[$carol->id]['rejected'])->toBe(0);
    });

    it('should return empty array for empty input', function () {
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);

        $counts = $api->getReportCountsByUserIds([]);
        
        expect($counts)->toBeArray()->toBeEmpty();
    });

    it('should return zero counts for users with no reports', function () {
        $alice = alice($this);
        $bob = bob($this);
        
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);

        $counts = $api->getReportCountsByUserIds([$alice->id, $bob->id]);
        
        expect($counts[$alice->id])->toBeArray();
        expect($counts[$alice->id]['confirmed'])->toBe(0);
        expect($counts[$alice->id]['rejected'])->toBe(0);
        
        expect($counts[$bob->id])->toBeArray();
        expect($counts[$bob->id]['confirmed'])->toBe(0);
        expect($counts[$bob->id]['rejected'])->toBe(0);
    });

    it('should handle mixed existing and non-existent user ids', function () {
        $alice = alice($this);
        
        // Create report for alice using helper
        $reportId = createReportForUser($alice);

        // Approve the report using Public API
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);
        $api->approveReport($reportId);

        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);

        $counts = $api->getReportCountsByUserIds([$alice->id, 999]);
        
        expect($counts[$alice->id])->toBeArray();
        expect($counts[$alice->id]['confirmed'])->toBe(1);
        expect($counts[$alice->id]['rejected'])->toBe(0);
        
        expect($counts[999])->toBeArray();
        expect($counts[999]['confirmed'])->toBe(0);
        expect($counts[999]['rejected'])->toBe(0);
    });

    it('should only count confirmed and dismissed reports', function () {
        $alice = alice($this);

        // Create reports using helper (all start as pending)
        $report1Id = createReportForUser($alice);
        $report2Id = createReportForUser($alice);
        $report3Id = createReportForUser($alice);

        // Approve/reject reports using Public API (leave one as pending)
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);
        $api->approveReport($report1Id);  // confirmed
        $api->rejectReport($report2Id);   // dismissed
        // report3Id remains pending

        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);

        $counts = $api->getReportCountsByUserIds([$alice->id]);
        
        expect($counts[$alice->id]['confirmed'])->toBe(1);
        expect($counts[$alice->id]['rejected'])->toBe(1);
        // Pending reports should not be counted
    });

    it('requires moderator role to access', function () {
        $alice = alice($this);
        
        // Regular user cannot access
        $this->actingAs(bob($this));
        $api = app(ModerationPublicApi::class);
        expect(fn() => $api->getReportCountsByUserIds([$alice->id]))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        // Moderator can access
        $this->actingAs(moderator($this));
        $api = app(ModerationPublicApi::class);
        expect(fn() => $api->getReportCountsByUserIds([$alice->id]))->not->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
});
