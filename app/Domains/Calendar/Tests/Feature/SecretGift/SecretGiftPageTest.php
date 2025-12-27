<?php

declare(strict_types=1);

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Secret Gift Page Display', function () {
    it('should display countdown timer on gift preparation page', function () {
        // Create test users
        $user1 = alice($this);
        $user2 = bob($this);

        // Create an active Secret Gift activity with participants and assignments
        $result = createShuffledSecretGift($this, [$user1->id, $user2->id], [
            'active_ends_at' => now()->addDays(7)->addHours(18)->addMinutes(23),
        ]);

        // Visit the gift preparation page (part of activity show page)
        $response = $this->actingAs($user1)
            ->get(route('calendar.activities.show', $result->activity->slug));

        $response->assertOk();

        // Assert the countdown timer is present
        $response
            ->assertSee('Bob')
            ->assertSee('x-data="countdownTimer"', false)
            ->assertSee('data-end-time', false)
            ->assertSee(__('secret-gift::secret-gift.time_remaining'));

        // Assert the component has the correct end time
        $response->assertSee('data-end-time="' . $result->activity->active_ends_at->toIso8601String() . '"', false);
    });

    it('should enable editor and save button while secret gift is active', function () {
        // Create test users
        $user1 = alice($this);
        $user2 = bob($this);

        // Create an active Secret Gift activity
        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        // Visit the page
        $response = $this->actingAs($user1)
            ->get(route('calendar.activities.show', $result->activity->slug));

        $response->assertOk();

        // Assert editor is enabled (form is present)
        $response->assertSee('<form', false)
            ->assertSee('name="gift_text"', false);

        // Assert save button is present and enabled
        $response->assertSee(__('secret-gift::secret-gift.save_gift'))
            ->assertSee('type="submit"', false);
    });

    it('should show gift will be revealed message in received gift tab when activity is not ended', function () {
        // Create test users
        $user1 = alice($this);
        $user2 = bob($this);

        // Create an active Secret Gift activity
        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        // Visit the page
        $response = $this->actingAs($user1)
            ->get(route('calendar.activities.show', $result->activity->slug));

        $response->assertOk();

        // Assert received gift tab shows "gift will be revealed" message
        $response->assertSee(__('secret-gift::secret-gift.gift_will_be_revealed'));
    });

    it('should not show save button and display received gift when activity is finished', function () {
        // Create test users
        $user1 = alice($this);
        $user2 = bob($this);

        // Create a Secret Gift activity
        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        
        // User2 saves a gift for user1
        $this->actingAs($user2)
            ->post(route('secret-gift.save-gift', $result->activity), [
                'gift_text' => '<p>Happy holidays!</p>',
            ]);

        // End the activity
        $result->activity->update([
            'active_ends_at' => now()->subHour(),
        ]);

        // Visit the page as user1
        $response = $this->actingAs($user1)
            ->get(route('calendar.activities.show', $result->activity->slug));

        $response->assertOk();

        // Assert save button is not present
        $response->assertDontSee(__('secret-gift::secret-gift.save_gift'))
            ->assertDontSee('<button type="submit"', false);

        // Assert received gift is displayed
        $response->assertSee('Happy holidays!')
            ->assertDontSee(__('secret-gift::secret-gift.no_gift_yet'));
    });

    it('should show no gift received when activity is finished and no gift was contributed', function () {
        // Create test users
        $user1 = alice($this);
        $user2 = bob($this);

        // Create a Secret Gift activity
        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        
        // End the activity without anyone saving a gift
        $result->activity->update([
            'active_ends_at' => now()->subHour(),
        ]);

        // Visit the page as user1
        $response = $this->actingAs($user1)
            ->get(route('calendar.activities.show', $result->activity->slug));

        $response->assertOk();

        // Assert no gift received message
        $response->assertSee(__('secret-gift::secret-gift.no_gift_received'))
            ->assertDontSee('Happy holidays!');

        // Also check as user2
        $response2 = $this->actingAs($user2)
            ->get(route('calendar.activities.show', $result->activity->slug));
        
        $response2->assertSee(__('secret-gift::secret-gift.no_gift_received'));
    });
});
