<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('purges soft-deleted quotes and keeps live ones', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Story', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $live = createQuote($reader->id, $chapter->id, $story->id);
    $hidden = createQuote($reader->id, $chapter->id, $story->id);
    $hidden->delete();

    (require base_path('app/Domains/Quote/Database/Migrations/2026_10_24_000000_purge_soft_deleted_quotes.php'))->up();

    $this->assertDatabaseMissing('quotes', ['id' => $hidden->id]);
    $this->assertDatabaseHas('quotes', ['id' => $live->id]);
});
