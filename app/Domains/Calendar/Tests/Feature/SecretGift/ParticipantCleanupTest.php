<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Secret Gift participant cleanup on user removal', function () {

    it('removes a participant row when the user is deleted before the shuffle', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Des chats</p>');

        dispatchEvent(new UserDeleted($alice->id));

        expect(SecretGiftParticipant::query()->where('user_id', $alice->id)->count())->toBe(0);
    });

    it('removes a participant row when the user is deactivated before the shuffle', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Des chats</p>');

        dispatchEvent(new UserDeactivated($alice->id));

        expect(SecretGiftParticipant::query()->where('user_id', $alice->id)->count())->toBe(0);
    });

    it('leaves the participant row and the assignments alone after the shuffle', function () {
        $alice = alice($this);
        $bob = bob($this);
        $gift = createShuffledSecretGift($this, [$alice->id, $bob->id]);

        dispatchEvent(new UserDeleted($alice->id));

        $this->assertDatabaseHas('calendar_secret_gift_participants', [
            'activity_id' => $gift->id,
            'user_id' => $alice->id,
        ]);
        expect(getSecretGiftAssignmentAsGiver($gift->id, $alice->id))->not->toBeNull();
        expect(getSecretGiftAssignmentAsRecipient($gift->id, $alice->id))->not->toBeNull();
    });

    it('removes rows only for the removed user', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id]);

        dispatchEvent(new UserDeleted($alice->id));

        $this->assertDatabaseMissing('calendar_secret_gift_participants', [
            'activity_id' => $gift->id,
            'user_id' => $alice->id,
        ]);
        $this->assertDatabaseHas('calendar_secret_gift_participants', [
            'activity_id' => $gift->id,
            'user_id' => $bob->id,
        ]);
    });

    it('spans every activity the user was enrolled in', function () {
        $alice = alice($this);
        $first = createPreviewSecretGift($this, ['name' => 'Noël 2026']);
        $second = createPreviewSecretGift($this, ['name' => 'Noël 2027']);
        registerSecretGiftParticipant($first->id, $alice->id);
        registerSecretGiftParticipant($second->id, $alice->id);

        dispatchEvent(new UserDeactivated($alice->id));

        expect(SecretGiftParticipant::query()->where('user_id', $alice->id)->count())->toBe(0);
    });

    it('leaves a shuffled activity alone while cleaning an unshuffled one', function () {
        $alice = alice($this);
        $bob = bob($this);
        $shuffled = createShuffledSecretGift($this, [$alice->id, $bob->id]);
        $open = createPreviewSecretGift($this, ['name' => 'Noël prochain']);
        registerSecretGiftParticipant($open->id, $alice->id);

        dispatchEvent(new UserDeleted($alice->id));

        $this->assertDatabaseHas('calendar_secret_gift_participants', [
            'activity_id' => $shuffled->id,
            'user_id' => $alice->id,
        ]);
        $this->assertDatabaseMissing('calendar_secret_gift_participants', [
            'activity_id' => $open->id,
            'user_id' => $alice->id,
        ]);
    });
});
