<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * What the activity page renders around enrolment. Purely presentational: the
 * write endpoints enforce the window themselves (`EnrolmentTest`), so a missing
 * button here is a UX fact, never the security boundary.
 */
describe('Secret Gift enrolment page', function () {

    it('offers the join form with the preferences template to a confirmed non-participant during the open window', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);

        $response = $this->actingAs($alice)->get($gift->url);

        $response->assertOk()
            ->assertSee(route('secret-gift.participants.store', $gift->id), false)
            ->assertSee('name="preferences"', false)
            ->assertSee(__('secret-gift::secret-gift.enrolment.join_button'))
            // The starter template is seeded raw into the editor's textarea.
            ->assertSee(__('secret-gift::secret-gift.preferences_template'), false)
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.leave_button'));
    });

    it('shows the edit form and the leave action to an enrolled user during the open window', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Alice aime les livres</p>');

        $response = $this->actingAs($alice)->get($gift->url);

        $response->assertOk()
            ->assertSee(route('secret-gift.participants.update', $gift->id), false)
            ->assertSee('name="preferences"', false)
            ->assertSee('Alice aime les livres', false)
            ->assertSee(__('secret-gift::secret-gift.enrolment.save_preferences'))
            ->assertSee(__('secret-gift::secret-gift.enrolment.leave_button'))
            ->assertSee(route('secret-gift.participants.destroy', $gift->id), false)
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.join_button'));
    });

    it('shows neither the join form nor the leave action once the deadline has passed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Alice aime les livres</p>');

        $response = $this->actingAs($alice)->get($gift->url);

        $response->assertOk()
            ->assertSee(__('secret-gift::secret-gift.enrolment.registration_closed_participant'))
            ->assertDontSee('name="preferences"', false)
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.save_preferences'))
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.leave_button'))
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.join_button'));
    });

    it('shows neither once the activity has been shuffled', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id]);
        shuffleSecretGift($gift->activity);

        $response = $this->actingAs($alice)->get($gift->url);

        $response->assertOk()
            ->assertDontSee('name="preferences"', false)
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.save_preferences'))
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.leave_button'));

        // A non-participant arriving after the shuffle cannot join either.
        $carol = carol($this);
        $this->actingAs($carol)->get($gift->url)
            ->assertOk()
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.join_button'))
            ->assertSee(__('secret-gift::secret-gift.enrolment.registration_closed'));
    });

    it('shows the participant list only to enrolled users', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id]);
        $carol = carol($this);

        $this->actingAs($alice)->get($gift->url)
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.enrolment.participants_title'))
            ->assertSee('Bob');

        $this->actingAs($carol)->get($gift->url)
            ->assertOk()
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.participants_title'))
            ->assertDontSee('Bob');
    });

    it('never renders another participant\'s preferences in the list', function (string $window) {
        $settings = $window === 'closed' ? ['registration_ends_at' => now()->subMinute()] : [];
        $gift = createPreviewSecretGift($this, settings: $settings);

        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipant($gift->id, $alice->id, '<p>Alice aime les livres</p>');
        registerSecretGiftParticipant($gift->id, $bob->id, '<p>PREFERENCE-SECRETE-DE-BOB</p>');

        $this->actingAs($alice)->get($gift->url)
            ->assertOk()
            ->assertSee('Bob')
            ->assertDontSee('PREFERENCE-SECRETE-DE-BOB', false);
    })->with(['open', 'closed']);

    it('shows the alone state when the caller is the only participant', function () {
        $gift = createPreviewSecretGift($this);
        $alice = alice($this);
        registerSecretGiftParticipant($gift->id, $alice->id);

        $this->actingAs($alice)->get($gift->url)
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.enrolment.participants_alone'));
    });

    it('still shows the participant list to an enrolled user after registration closed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);
        $alice = alice($this);
        $bob = bob($this);
        registerSecretGiftParticipants($gift->id, [$alice->id, $bob->id]);

        $this->actingAs($alice)->get($gift->url)
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.enrolment.participants_title'))
            ->assertSee('Bob');
    });

    it('leaves the active-state gift tabs untouched', function () {
        $alice = alice($this);
        $bob = bob($this);
        $gift = createShuffledSecretGift($this, [$alice->id, $bob->id]);

        $this->actingAs($alice)->get($gift->url)
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.tab_my_gift'))
            ->assertSee(__('secret-gift::secret-gift.save_gift'))
            ->assertSee('name="gift_text"', false)
            ->assertDontSee('name="preferences"', false)
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.join_button'))
            ->assertDontSee(__('secret-gift::secret-gift.enrolment.leave_button'));
    });
});
