<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('report role gate', function () {
    it('lets staff-only accounts load the form and submit a report', function (string $role) {
        $user = alice($this, [], true, [$role]);
        $reason = createReason('profile', 'Spam');

        $this->actingAs($user)->get('/moderation/report-form/profile/123')->assertOk();

        $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 123,
                'reason_id' => $reason->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('moderation_reports', [
            'topic_key' => 'profile',
            'entity_id' => 123,
            'reported_by_user_id' => $user->id,
        ]);
    })->with([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]);

    it('still refuses an account with no role', function () {
        $user = alice($this, [], true, []);

        $reason = createReason('profile', 'Spam');

        $this->actingAs($user)->get('/moderation/report-form/profile/123')->assertRedirect();
        $this->actingAs($user)
            ->postJson('/moderation/report', ['topic_key' => 'profile', 'entity_id' => 123, 'reason_id' => $reason->id])
            ->assertStatus(302);
        $this->assertDatabaseCount('moderation_reports', 0);
    });

    it('still rejects guests', function () {
        $this->postJson('/moderation/report', ['topic_key' => 'profile', 'entity_id' => 123, 'reason_id' => 1])
            ->assertUnauthorized();
    });
});
