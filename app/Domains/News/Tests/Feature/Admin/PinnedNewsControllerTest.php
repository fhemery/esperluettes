<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Pinned News Admin Controller', function () {
    describe('index', function () {
        it('displays the pinned news list', function () {
            $user = admin($this);
            News::factory()->create([
                'title' => 'Pinned One',
                'is_pinned' => true,
                'display_order' => 1,
                'status' => 'published',
            ]);
            News::factory()->create([
                'title' => 'Pinned Two',
                'is_pinned' => true,
                'display_order' => 2,
                'status' => 'published',
            ]);
            News::factory()->create([
                'title' => 'Not Pinned',
                'is_pinned' => false,
                'status' => 'published',
            ]);

            $response = $this->actingAs($user)
                ->get(route('news.admin.pinned.index'));

            $response->assertOk();
            $response->assertSee('Pinned One');
            $response->assertSee('Pinned Two');
            $response->assertDontSee('Not Pinned');
        });

        it('denies access to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('news.admin.pinned.index'));

            $response->assertRedirect(route('dashboard'));
        });
    });

    describe('reorder', function () {
        it('reorders pinned news via PUT request', function () {
            $user = admin($this);
            $news1 = News::factory()->create([
                'title' => 'First',
                'is_pinned' => true,
                'display_order' => 1,
            ]);
            $news2 = News::factory()->create([
                'title' => 'Second',
                'is_pinned' => true,
                'display_order' => 2,
            ]);
            $news3 = News::factory()->create([
                'title' => 'Third',
                'is_pinned' => true,
                'display_order' => 3,
            ]);

            // Reorder: Third -> First -> Second
            $response = $this->actingAs($user)
                ->putJson(route('news.admin.pinned.reorder'), [
                    'ordered_ids' => [$news3->id, $news1->id, $news2->id],
                ]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            // Verify new order
            expect($news3->fresh()->display_order)->toBe(1);
            expect($news1->fresh()->display_order)->toBe(2);
            expect($news2->fresh()->display_order)->toBe(3);
        });

        it('validates ordered_ids array', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->putJson(route('news.admin.pinned.reorder'), [
                    'ordered_ids' => 'not-an-array',
                ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('ordered_ids');
        });

        it('validates that IDs exist in the database', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->putJson(route('news.admin.pinned.reorder'), [
                    'ordered_ids' => [999, 998, 997],
                ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('ordered_ids.0');
        });
    });
});
