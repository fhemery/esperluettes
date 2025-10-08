<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Upon user deletion', function () {
    it('removes chapter credits row when the user is deleted', function () {
        // Arrange: create a confirmed user and ensure a credits row exists
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        /** @var ChapterCreditService $credits */
        $credits = app(ChapterCreditService::class);
        $credits->grantInitialOnRegistration($user->id);

        $this->assertDatabaseHas('story_chapter_credits', [
            'user_id' => $user->id,
        ]);

        // Act: delete the user (fires UserDeleted)
        deleteUser($this, $user);

        // Assert: chapter credits row is removed
        $this->assertDatabaseMissing('story_chapter_credits', [
            'user_id' => $user->id,
        ]);
    });
});
