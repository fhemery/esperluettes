<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function secretGiftParticipantRow(int $activityId, int $userId): ?SecretGiftParticipant
{
    return SecretGiftParticipant::query()
        ->where('activity_id', $activityId)
        ->where('user_id', $userId)
        ->first();
}

describe('Secret Gift enrolment', function () {

    it('creates a participant row with purified preferences when a confirmed user joins', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $response = $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>J\'aime les <strong>chats</strong>.</p>'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $participant = secretGiftParticipantRow($gift->id, $alice->id);
        expect($participant)->not->toBeNull();
        expect($participant->preferences)->toContain('chats');
        expect($participant->preferences)->toContain('<strong>');
    });

    it('stores a null preferences column when the submitted text is blank', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '   '],
        )->assertRedirect();

        expect(secretGiftParticipantRow($gift->id, $alice->id)->preferences)->toBeNull();
    });

    it('strips a script tag from submitted preferences', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Bonjour</p><script>alert("xss")</script><img src="x" onerror="alert(1)">'],
        )->assertRedirect();

        $preferences = secretGiftParticipantRow($gift->id, $alice->id)->preferences;
        expect($preferences)->toContain('Bonjour');
        expect($preferences)->not->toContain('<script>');
        expect($preferences)->not->toContain('onerror');
    });

    it('is idempotent when the same user posts twice', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Premier envoi</p>'],
        )->assertRedirect();

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Second envoi</p>'],
        )->assertRedirect();

        $rows = SecretGiftParticipant::query()
            ->where('activity_id', $gift->id)
            ->where('user_id', $alice->id)
            ->get();

        expect($rows)->toHaveCount(1);
        expect($rows->first()->preferences)->toContain('Second envoi');
    });

    it('updates only the caller\'s own preferences', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        $bob = bob($this);

        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Alice originale</p>');
        registerSecretGiftParticipant($gift->id, $bob->id, '<p>Bob intouchable</p>');

        $response = $this->actingAs($alice)->put(
            route('secret-gift.participants.update', $gift->id),
            ['preferences' => '<p>Alice mise a jour</p>'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(secretGiftParticipantRow($gift->id, $alice->id)->preferences)->toContain('Alice mise a jour');
        expect(secretGiftParticipantRow($gift->id, $bob->id)->preferences)->toContain('Bob intouchable');
    });

    it('deletes the participant row when the user leaves', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Peu importe</p>');

        $response = $this->actingAs($alice)->delete(route('secret-gift.participants.destroy', $gift->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(secretGiftParticipantRow($gift->id, $alice->id))->toBeNull();
    });

    it('refuses joining once the registration deadline has passed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);
        $alice = alice($this);

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Trop tard</p>'],
        )->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id))->toBeNull();
    });

    it('refuses joining once the activity has been shuffled', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id]);
        shuffleSecretGift($gift->activity);

        $carol = carol($this);

        $this->actingAs($carol)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Trop tard aussi</p>'],
        )->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $carol->id))->toBeNull();
    });

    it('refuses joining once the activity is active', function () {
        // Deadline still in the future: only the state closes the window.
        $gift = createActiveSecretGift($this, settings: ['registration_ends_at' => now()->addDay()]);
        $alice = alice($this);

        $this->actingAs($alice)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Trop tard</p>'],
        )->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id))->toBeNull();
    });

    it('refuses editing preferences after registration closed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Figé</p>');

        $this->actingAs($alice)->put(
            route('secret-gift.participants.update', $gift->id),
            ['preferences' => '<p>Tentative</p>'],
        )->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id)->preferences)->toContain('Figé');
    });

    it('refuses leaving after registration closed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Figé</p>');

        $this->actingAs($alice)
            ->delete(route('secret-gift.participants.destroy', $gift->id))
            ->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id))->not->toBeNull();
    });

    // The deadline is asserted above; the shuffle and the active state close the
    // window just as hard, and they have to be asserted on the edit and leave
    // verbs too — not only on joining.
    it('refuses editing preferences and leaving once the window is closed another way', function (string $closedBy) {
        $alice = alice($this);
        $bob = bob($this);

        if ($closedBy === 'shuffle') {
            $gift = createPreviewSecretGift($this);
            registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id], '<p>Figé</p>');
            shuffleSecretGift($gift->activity);
        } else {
            $gift = createActiveSecretGift($this, settings: ['registration_ends_at' => now()->addDay()]);
            registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id], '<p>Figé</p>');
        }

        $this->actingAs($alice)->put(
            route('secret-gift.participants.update', $gift->id),
            ['preferences' => '<p>Tentative</p>'],
        )->assertStatus(403);

        $this->actingAs($alice)
            ->delete(route('secret-gift.participants.destroy', $gift->id))
            ->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id)->preferences)->toContain('Figé');
    })->with(['shuffle', 'active state']);

    it('refuses editing preferences for a user who never joined', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $this->actingAs($alice)->put(
            route('secret-gift.participants.update', $gift->id),
            ['preferences' => '<p>Je ne suis pas inscrit</p>'],
        )->assertStatus(403);

        expect(secretGiftParticipantRow($gift->id, $alice->id))->toBeNull();
    });

    it('404s for a user whose role is excluded by the activity role_restrictions', function () {
        $gift = createPreviewSecretGift($this, ['role_restrictions' => [Roles::USER_CONFIRMED]]);
        $plainUser = alice($this, roles: [Roles::USER]);
        registerSecretGiftParticipant($gift->id, $plainUser->id, '<p>Ne devrait pas compter</p>');

        $this->actingAs($plainUser)->post(
            route('secret-gift.participants.store', $gift->id),
            ['preferences' => '<p>Laissez-moi entrer</p>'],
        )->assertStatus(404);

        $this->actingAs($plainUser)->put(
            route('secret-gift.participants.update', $gift->id),
            ['preferences' => '<p>Laissez-moi entrer</p>'],
        )->assertStatus(404);

        $this->actingAs($plainUser)
            ->delete(route('secret-gift.participants.destroy', $gift->id))
            ->assertStatus(404);

        // Nothing was written and nothing was removed.
        expect(secretGiftParticipantRow($gift->id, $plainUser->id)->preferences)
            ->toContain('Ne devrait pas compter');
    });

    it('redirects a guest to login', function () {
        $gift = createPreviewSecretGift($this);
        Auth::logout();   // the fixture authenticates an admin to create the activity

        $this->post(route('secret-gift.participants.store', $gift->id), ['preferences' => '<p>Anonyme</p>'])
            ->assertRedirect(route('login'));
        $this->put(route('secret-gift.participants.update', $gift->id), ['preferences' => '<p>Anonyme</p>'])
            ->assertRedirect(route('login'));
        $this->delete(route('secret-gift.participants.destroy', $gift->id))
            ->assertRedirect(route('login'));

        expect(SecretGiftParticipant::query()->count())->toBe(0);
        expect(SecretGiftAssignment::query()->count())->toBe(0);
    });
});
