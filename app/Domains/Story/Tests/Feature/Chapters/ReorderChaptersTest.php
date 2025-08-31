<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Domains\Story\Models\Chapter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('returns 404 for non-author attempting reorder', function () {
    $author = alice($this);
    $other = bob($this);
    $story = publicStory('Reorder Story', $author->id);

    // Create chapters
    createPublishedChapter($this, $story, $author, ['title' => 'A']);
    createPublishedChapter($this, $story, $author, ['title' => 'B']);
    createPublishedChapter($this, $story, $author, ['title' => 'C']);

    $chapters = Chapter::where('story_id', $story->id)->orderBy('sort_order')->get();
    $orderedIds = $chapters->pluck('id')->reverse()->values()->all();

    $this->actingAs($other);
    $this->putJson(route('chapters.reorder', ['storySlug' => $story->slug]), [
        'ordered_ids' => $orderedIds,
    ])->assertNotFound();
});

it('returns 422 when ordered_ids is not a permutation', function () {
    $author = alice($this);
    $story = publicStory('Reorder Story 2', $author->id);

    $a = createPublishedChapter($this, $story, $author, ['title' => 'A']);
    $b = createPublishedChapter($this, $story, $author, ['title' => 'B']);

    $this->actingAs($author);
    // Missing one id, duplicate another
    $payload = ['ordered_ids' => [$a->id, $a->id]];
    $this->putJson(route('chapters.reorder', ['storySlug' => $story->slug]), $payload)
        ->assertStatus(422);
});

it('reorders chapters and returns changes (sparse midpoint) for author', function () {
    $author = alice($this);
    $story = publicStory('Reorder Story 3', $author->id);

    $a = createPublishedChapter($this, $story, $author, ['title' => 'A']); // sort 100
    $b = createPublishedChapter($this, $story, $author, ['title' => 'B']); // sort 200
    $c = createPublishedChapter($this, $story, $author, ['title' => 'C']); // sort 300

    $this->actingAs($author);
    // New order: C, A, B
    $ordered = [$c->id, $a->id, $b->id];
    $res = $this->putJson(route('chapters.reorder', ['storySlug' => $story->slug]), [
        'ordered_ids' => $ordered,
    ])->assertOk()->json();

    expect($res)->toHaveKey('changes');
    // Should at least move C before A (right-step), or use midpoint for A/B
    $changes = $res['changes'];
    expect($changes)->toBeArray()->not->toBeEmpty();

    // Persisted order reflects new order
    $orderedTitles = Chapter::where('story_id', $story->id)
        ->orderBy('sort_order')
        ->pluck('title')
        ->all();
    expect($orderedTitles)->toEqual(['C', 'A', 'B']);
});
