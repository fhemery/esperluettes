<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('ReadList cleanup on user deletion', function () {
    it('removes all readlist entries for the deleted user', function () {
        $author = alice($this);
        $story1 = publicStory('S1', $author->id);
        $story2 = publicStory('S2', $author->id);

        $reader = bob($this);
        $other = carol($this);

        // Reader adds two stories
        $this->actingAs($reader);
        addToReadList($this, $story1->id);
        addToReadList($this, $story2->id);

        // Other adds one story (should remain)
        $this->actingAs($other);
        addToReadList($this, $story1->id);

        // Sanity
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $reader->id, 'story_id' => $story1->id]);
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $reader->id, 'story_id' => $story2->id]);
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $other->id, 'story_id' => $story1->id]);

        // Delete reader
        deleteUser($this, $reader);

        // Reader entries should be gone
        $this->assertDatabaseMissing('read_list_entries', ['user_id' => $reader->id, 'story_id' => $story1->id]);
        $this->assertDatabaseMissing('read_list_entries', ['user_id' => $reader->id, 'story_id' => $story2->id]);
        // Other user's entry remains
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $other->id, 'story_id' => $story1->id]);
    });
});
