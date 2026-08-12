<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The admin-side shuffle: the panel embedded in the activity edit page and the
 * single POST behind it.
 *
 * The role gate is the route's `role:` middleware, which denies by redirecting
 * to the dashboard (same as QuoteContest's admin category routes) rather than
 * by a 403 — what matters here is that the request never reaches the service.
 */
function shuffleRequest(TestCase $t, int $activityId)
{
    return $t->post(route('calendar.admin.secret-gift.shuffle', $activityId));
}

describe('Secret Gift shuffle action', function () {

    it('allows a moderator to shuffle', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(moderator($this));
        shuffleRequest($this, $gift->id)->assertRedirect();

        $assignments = SecretGiftAssignment::query()->where('activity_id', $gift->id)->get();

        expect($assignments)->toHaveCount(3);
        foreach ($assignments as $assignment) {
            expect($assignment->giver_user_id)->not->toBe($assignment->recipient_user_id);
        }
    });

    it('allows an admin to shuffle', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(admin($this));
        shuffleRequest($this, $gift->id)->assertRedirect();

        expect(SecretGiftAssignment::query()->where('activity_id', $gift->id)->count())->toBe(3);
    });

    it('allows a tech admin to shuffle', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(techAdmin($this));
        shuffleRequest($this, $gift->id)->assertRedirect();

        expect(SecretGiftAssignment::query()->where('activity_id', $gift->id)->count())->toBe(3);
    });

    it('refuses a confirmed user', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(daniel($this));
        shuffleRequest($this, $gift->id)->assertRedirect(route('dashboard'));

        expect(SecretGiftAssignment::query()->count())->toBe(0);
    });

    it('refuses a plain user', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(daniel($this, roles: [\App\Domains\Auth\Public\Api\Roles::USER]));
        shuffleRequest($this, $gift->id)->assertRedirect(route('dashboard'));

        expect(SecretGiftAssignment::query()->count())->toBe(0);
    });

    it('redirects a guest to login', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id]);

        shuffleRequest($this, $gift->id)->assertRedirect(route('login'));

        expect(SecretGiftAssignment::query()->count())->toBe(0);
    });

    it('refuses to shuffle fewer than 2 participants', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipant($gift->id, alice($this)->id);

        $this->actingAs(moderator($this));
        shuffleRequest($this, $gift->id)
            ->assertRedirect()
            ->assertSessionHas('error', __('secret-gift::secret-gift.flash.shuffle_not_enough_participants'));

        expect(SecretGiftAssignment::query()->count())->toBe(0);
    });

    it('refuses to shuffle once the activity is active', function () {
        $alice = alice($this);
        $bob = bob($this);
        $gift = createShuffledSecretGift($this, [$alice->id, $bob->id]);

        $before = SecretGiftAssignment::query()->where('activity_id', $gift->id)->pluck('id')->sort()->values()->all();

        $this->actingAs(moderator($this));
        shuffleRequest($this, $gift->id)->assertForbidden();

        $after = SecretGiftAssignment::query()->where('activity_id', $gift->id)->pluck('id')->sort()->values()->all();

        expect($after)->toBe($before);
    });

    it('replaces the previous assignments when re-shuffled while still in preview', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id, carol($this)->id]);

        $this->actingAs(admin($this));
        shuffleRequest($this, $gift->id)->assertRedirect();
        $first = SecretGiftAssignment::query()->where('activity_id', $gift->id)->pluck('id')->all();

        shuffleRequest($this, $gift->id)->assertRedirect();
        $second = SecretGiftAssignment::query()->where('activity_id', $gift->id)->pluck('id')->all();

        expect($first)->toHaveCount(3)
            ->and($second)->toHaveCount(3)
            ->and(array_intersect($first, $second))->toBe([]);
    });
});

describe('Secret Gift shuffle panel', function () {

    it('shows the participant list and count on the admin edit page', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id]);

        $this->actingAs(moderator($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.config.shuffle_title'))
            ->assertSee(__('secret-gift::secret-gift.config.participants_count', ['count' => 2]))
            ->assertSee('Alice')
            ->assertSee('Bob')
            ->assertSee(route('calendar.admin.secret-gift.shuffle', $gift->id), false)
            ->assertSee(__('secret-gift::secret-gift.config.shuffle_confirm_body'));
    });

    it('never shows a participant\'s preferences on the admin edit page', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipant($gift->id, alice($this)->id, '<p>PREFERENCE-SECRETE-ALICE</p>');
        registerSecretGiftParticipant($gift->id, bob($this)->id, '<p>PREFERENCE-SECRETE-BOB</p>');

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertDontSee('PREFERENCE-SECRETE-ALICE')
            ->assertDontSee('PREFERENCE-SECRETE-BOB');
    });

    it('renders no shuffle panel on the create page', function () {
        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.create'))
            ->assertOk()
            ->assertSee('secret_gift[registration_ends_at]', false)
            ->assertDontSee(__('secret-gift::secret-gift.config.shuffle_title'))
            ->assertDontSee(__('secret-gift::secret-gift.config.shuffle_button'));
    });

    it('explains why the shuffle button is disabled below two participants', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipant($gift->id, alice($this)->id);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.config.shuffle_disabled_not_enough'))
            ->assertDontSee(__('secret-gift::secret-gift.config.participants_empty'));
    });

    it('shows an empty state when nobody is enrolled', function () {
        $gift = createPreviewSecretGift($this);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.config.participants_empty'));
    });

    it('explains why the shuffle button is disabled once the activity is active', function () {
        $alice = alice($this);
        $bob = bob($this);
        $gift = createShuffledSecretGift($this, [$alice->id, $bob->id]);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertSee(__('secret-gift::secret-gift.config.shuffle_disabled_active'))
            ->assertSee(__('secret-gift::secret-gift.config.already_shuffled'));
    });

    it('words every shuffle-panel message in French', function () {
        $fr = fn (string $key, array $replace = []) => trans('secret-gift::secret-gift.' . $key, $replace, 'fr');

        expect($fr('config.shuffle_title'))->toBe('Attribution des cadeaux')
            ->and($fr('config.shuffle_button'))->toBe('Lancer le tirage')
            ->and($fr('flash.shuffled', ['count' => 3]))->toBe('Le tirage a été effectué pour 3 participant(e)s.')
            ->and($fr('flash.shuffle_not_enough_participants'))
            ->toBe('Il faut au moins 2 inscrit(e)s pour lancer le tirage.');
    });
});
