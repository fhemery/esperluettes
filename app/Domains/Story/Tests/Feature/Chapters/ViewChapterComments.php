<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

describe('Viewing chapter comments', function () {
    it('should show the comment form to verified users that are not authors', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);
        
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($reader);
 
        $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        
        $resp->assertSeeInOrder(['comment-list', 'form'], false);
        $resp->assertSee(__('shared::editor.min-characters', ['min' => 140]));
    });

    it('should not show the comment form to guests', function() {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $this->actingAsGuest();
        $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        
        $resp->assertSeeInOrder(['comment-list', __('comment::comments.members_only'), __('comment::comments.actions.login')], false);
        $resp->assertDontSee('form');
    });

    it('should not show the comment form to authors', function() {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $this->actingAs($author);
        $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        
        $resp->assertSee('comment-list', false);
        $resp->assertDontSee('form');
    });
});