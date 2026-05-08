<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $mock = Mockery::mock(ProfilePublicApi::class)->shouldIgnoreMissing();
    $mock->shouldReceive('getPublicProfiles')->andReturn([]);
    $mock->shouldReceive('getPublicProfile')->andReturn(null);
    app()->instance(ProfilePublicApi::class, $mock);
});

describe('ModerationReport Admin Controller', function () {

    describe('index', function () {
        it('displays the list for admins with ok status', function () {
            createReportForUser(admin($this), 'pending');

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reports.index'))
                ->assertOk();
        });

        it('applies the default pending filter', function () {
            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reports.index'))
                ->assertOk()
                ->assertSee('pending', false);
        });

        it('shows all reports when status filter is cleared to empty', function () {
            createReportForUser(admin($this), 'pending');
            createReportForUser(admin($this), 'confirmed');
            createReportForUser(admin($this), 'dismissed');

            $response = $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reports.index') . '?status=');

            $response->assertOk();
            $reports = $response->viewData('reports');
            expect($reports->total())->toBe(3);
        });

        it('shows all reports when topic filter is cleared to empty', function () {
            createReportForUser(admin($this), 'pending');

            $response = $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reports.index') . '?status=&topic_key=');

            $response->assertOk();
            $reports = $response->viewData('reports');
            expect($reports->total())->toBe(1);
        });

        it('displays the list for moderators', function () {
            $this->actingAs(moderator($this))
                ->get(route('moderation.admin.moderation-reports.index'))
                ->assertOk();
        });

        it('denies access to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('moderation.admin.moderation-reports.index'))
                ->assertRedirect();
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('moderation.admin.moderation-reports.index'))
                ->assertRedirect(route('login'));
        });
    });

    describe('show', function () {
        it('displays the report detail for admins', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reports.show', $report))
                ->assertOk();
        });

        it('displays the report detail for moderators', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(moderator($this))
                ->get(route('moderation.admin.moderation-reports.show', $report))
                ->assertOk();
        });

        it('denies access to non-admin users', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('moderation.admin.moderation-reports.show', $report))
                ->assertRedirect();
        });
    });

    describe('approve', function () {
        it('approves a pending report', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->post(route('moderation.admin.moderation-reports.approve', $report))
                ->assertRedirect(route('moderation.admin.moderation-reports.index'));

            $this->assertDatabaseHas('moderation_reports', ['id' => $reportId, 'status' => 'confirmed']);
        });

        it('denies approval to non-admin users', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->post(route('moderation.admin.moderation-reports.approve', $report))
                ->assertRedirect();
        });
    });

    describe('dismiss', function () {
        it('dismisses a pending report', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->post(route('moderation.admin.moderation-reports.dismiss', $report))
                ->assertRedirect(route('moderation.admin.moderation-reports.index'));

            $this->assertDatabaseHas('moderation_reports', ['id' => $reportId, 'status' => 'dismissed']);
        });

        it('denies dismissal to non-admin users', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->post(route('moderation.admin.moderation-reports.dismiss', $report))
                ->assertRedirect();
        });
    });

    describe('updateComment', function () {
        it('saves a review comment', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->put(route('moderation.admin.moderation-reports.update-comment', $report), [
                    'review_comment' => 'Un commentaire de modération.',
                ])
                ->assertRedirect(route('moderation.admin.moderation-reports.show', $report));

            $this->assertDatabaseHas('moderation_reports', [
                'id' => $reportId,
                'review_comment' => 'Un commentaire de modération.',
            ]);
        });

        it('clears the review comment when empty', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->put(route('moderation.admin.moderation-reports.update-comment', $report), [
                    'review_comment' => '',
                ])
                ->assertRedirect(route('moderation.admin.moderation-reports.show', $report));
        });

        it('validates review_comment max length', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->put(route('moderation.admin.moderation-reports.update-comment', $report), [
                    'review_comment' => str_repeat('a', 1001),
                ])
                ->assertSessionHasErrors(['review_comment']);
        });
    });

    describe('destroy', function () {
        it('deletes a report', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);

            $this->actingAs(admin($this))
                ->delete(route('moderation.admin.moderation-reports.destroy', $report))
                ->assertRedirect(route('moderation.admin.moderation-reports.index'));

            $this->assertDatabaseMissing('moderation_reports', ['id' => $reportId]);
        });

        it('denies deletion to non-admin users', function () {
            $reportId = createReportForUser(admin($this), 'pending');
            $report = ModerationReport::find($reportId);
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->delete(route('moderation.admin.moderation-reports.destroy', $report))
                ->assertRedirect();

            $this->assertDatabaseHas('moderation_reports', ['id' => $reportId]);
        });
    });
});
