<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('moderation report submission', function () {
    it('creates report successfully', function () {
        $user = alice($this);

        $reason = createReason('profile', 'Spam');

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 123,
                'reason_id' => $reason->id,
                'description' => 'This profile contains spam',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['report_id', 'message']);

        $this->assertDatabaseHas('moderation_reports', [
            'topic_key' => 'profile',
            'entity_id' => 123,
            'reported_by_user_id' => $user->id,
            'reason_id' => $reason->id,
            'description' => 'This profile contains spam',
            'status' => 'pending',
        ]);
    });

    it('creates report without description', function () {
        $user = alice($this);

        $reason = createReason('comment', 'Harassment');

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'comment',
                'entity_id' => 456,
                'reason_id' => $reason->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('moderation_reports', [
            'topic_key' => 'comment',
            'entity_id' => 456,
            'reason_id' => $reason->id,
            'description' => null,
        ]);
    });

    it('validates required fields', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['topic_key', 'entity_id', 'reason_id']);
    });

    it('validates reason exists', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 123,
                'reason_id' => 99999, // Non-existent
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason_id']);
    });

    it('requires verified email to submit report', function () {
        $unverifiedUser = bob($this, isVerified: false);

        $reason = createReason('profile', 'Spam');

        $response = $this->actingAs($unverifiedUser)
            ->postJson('/moderation/report', [
                'topic_key' => 'profile',
                'entity_id' => 123,
                'reason_id' => $reason->id,
            ]);

        $response->assertForbidden();
    });

    it('rejects invalid topic', function () {
        $user = alice($this);

        $reason = createReason('profile', 'Spam');

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'invalid-topic',
                'entity_id' => 123,
                'reason_id' => $reason->id,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    });

    it('rejects mismatched topic and reason', function () {
        $user = alice($this);

        $profileReason = createReason('profile', 'Spam');

        $response = $this->actingAs($user)
            ->postJson('/moderation/report', [
                'topic_key' => 'story',
                'entity_id' => 123,
                'reason_id' => $profileReason->id,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    });
});
