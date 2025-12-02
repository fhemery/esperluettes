<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Mature Content Gate', function () {

    describe('Display Age Verification Overlay', function () {

        it('shows overlay for chapter of mature story (18+)', function () {
            $author = alice($this);
            
            // Create a mature audience with threshold_age=18
            $matureAudience = makeRefAudience('Adults Only', [
                'slug' => 'adults-only',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
            
            $story = publicStory('Mature Story', $author->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Mature Chapter']);

            Auth::logout();
            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            
            // Should see the mature gate component
            $resp->assertSee('matureContentGate', false);
            
            // Should see the age badge "-18"
            $resp->assertSee('-18');
            
            // Should see the gate translations
            $resp->assertSee(__('story::mature_gate.title'));
            $resp->assertSee(__('story::mature_gate.checkbox_label', ['age' => 18]));
            $resp->assertSee(__('story::mature_gate.continue_button'));
            $resp->assertSee(__('story::mature_gate.leave_link'));
            
            // Content should be marked for blur
            $resp->assertSee('data-mature-content', false);
            
            // Chapter content should still be in the HTML (SEO requirement)
            $resp->assertSee('Mature Chapter');
        });

        it('shows overlay for chapter of mature story (16+)', function () {
            $author = alice($this);
            
            $matureAudience = makeRefAudience('Young Adults', [
                'slug' => 'young-adults',
                'is_mature_audience' => true,
                'threshold_age' => 16,
            ]);
            
            $story = publicStory('Teen+ Story', $author->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Teen+ Chapter']);

            Auth::logout();
            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            
            // Should see the age badge "-16"
            $resp->assertSee('-16');
            $resp->assertSee(__('story::mature_gate.checkbox_label', ['age' => 16]));
        });

        it('shows leave link pointing to stories index', function () {
            $author = alice($this);
            
            $matureAudience = makeRefAudience('Adults 18+', [
                'slug' => 'adults-18',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
            
            $story = publicStory('Mature Story 2', $author->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Mature Chapter 2']);

            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            $resp->assertSee(route('stories.index'));
        });
    });

    describe('Non-Mature Content Unaffected', function () {

        it('does not show overlay for chapter of non-mature story', function () {
            $author = alice($this);
            
            // Create a non-mature audience
            $allAgesAudience = makeRefAudience('All Ages', [
                'slug' => 'all-ages',
                'is_mature_audience' => false,
                'threshold_age' => null,
            ]);
            
            $story = publicStory('Family Story', $author->id, [
                'story_ref_audience_id' => $allAgesAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Family Chapter']);

            Auth::logout();
            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            
            // Should NOT see the mature gate
            $resp->assertDontSee('matureContentGate', false);
            $resp->assertDontSee(__('story::mature_gate.title'));
            $resp->assertDontSee('data-mature-content', false);
            
            // Chapter content should be visible normally
            $resp->assertSee('Family Chapter');
        });

        it('does not show overlay for story without audience', function () {
            $author = alice($this);
            
            // Story with no audience set (default from helper)
            $story = publicStory('No Audience Story', $author->id);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Normal Chapter']);

            Auth::logout();
            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            
            // Should NOT see the mature gate
            $resp->assertDontSee('matureContentGate', false);
            $resp->assertDontSee('data-mature-content', false);
        });

        it('does not show overlay when is_mature_audience is true but threshold_age is null (invalid data, fail-open)', function () {
            $author = alice($this);
            
            // Edge case: is_mature_audience=true but no threshold_age (should be prevented by validation, but we fail-open)
            $invalidAudience = makeRefAudience('Invalid Mature', [
                'slug' => 'invalid-mature',
                'is_mature_audience' => true,
                'threshold_age' => null, // This combination should not happen in production
            ]);
            
            $story = publicStory('Invalid Audience Story', $author->id, [
                'story_ref_audience_id' => $invalidAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Invalid Chapter']);

            $resp = $this->get(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            $resp->assertOk();
            
            // Should NOT show gate (fail-open behavior for invalid data)
            $resp->assertDontSee('matureContentGate', false);
        });
    });

    describe('Mature content gate for authenticated users', function () {

        it('shows overlay to logged-in users as well', function () {
            $author = alice($this);
            $reader = bob($this);
            
            $matureAudience = makeRefAudience('Adults 18+ Auth', [
                'slug' => 'adults-18-auth',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
            
            $story = publicStory('Mature Story Auth', $author->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Mature Chapter Auth']);

            // Authenticated reader should still see the gate
            $resp = $this->actingAs($reader)
                ->get(route('chapters.show', [
                    'storySlug' => $story->slug,
                    'chapterSlug' => $chapter->slug,
                ]));

            $resp->assertOk();
            $resp->assertSee('matureContentGate', false);
            $resp->assertSee('-18');
        });

        it('does not show overlay to author of the story', function () {
            $author = alice($this);
            
            $matureAudience = makeRefAudience('Adults Author View', [
                'slug' => 'adults-author-view',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
            
            $story = publicStory('Mature Story Own', $author->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Own Mature Chapter']);

            // Author should NOT see the gate - they created this content
            $resp = $this->actingAs($author)
                ->get(route('chapters.show', [
                    'storySlug' => $story->slug,
                    'chapterSlug' => $chapter->slug,
                ]));

            $resp->assertOk();
            $resp->assertDontSee('matureContentGate', false);
            $resp->assertDontSee('data-mature-content', false);
        });

        it('does not show overlay to credited co-author of the story', function () {
            $mainAuthor = alice($this);
            $coAuthor = bob($this);
            
            $matureAudience = makeRefAudience('Adults CoAuthor View', [
                'slug' => 'adults-coauthor-view',
                'is_mature_audience' => true,
                'threshold_age' => 18,
            ]);
            
            $story = publicStory('Mature Story CoAuthor', $mainAuthor->id, [
                'story_ref_audience_id' => $matureAudience->id,
            ]);
            
            // Add co-author credit
            addCollaborator($story->id, $coAuthor->id, 'author');
            
            $chapter = createPublishedChapter($this, $story, $mainAuthor, ['title' => 'CoAuthor Mature Chapter']);

            // Co-author should NOT see the gate
            $resp = $this->actingAs($coAuthor)
                ->get(route('chapters.show', [
                    'storySlug' => $story->slug,
                    'chapterSlug' => $chapter->slug,
                ]));

            $resp->assertOk();
            $resp->assertDontSee('matureContentGate', false);
        });
    });
});
