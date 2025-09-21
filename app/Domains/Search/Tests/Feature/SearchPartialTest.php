<?php

namespace App\Domains\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Search results partial', function () {
    it('should return empty arrays for less than 2 characters', function () {
        $resp = $this->get('/search/partial?q=a');
        $resp->assertOk();
        $resp->assertSee(__('search::results.empty.label'));
    });

    describe('Regarding stories', function () {

        describe('Visibility', function () {
            it('should return only public stories to non-confirmed users', function () {
                // Create users
                $author = alice($this);

                // Create stories
                publicStory('Public Story', $author->id);
                communityStory('Community Story', $author->id);
                privateStory('Private Story', $author->id);


                // Confirmed but NOT collaborator should not see private
                Auth::logout();
                $response = $this->get('/search/partial?q=Story');

                $response->assertOk()
                    ->assertSeeText('Public Story')
                    ->assertDontSeeText('Community Story')
                    ->assertDontSeeText('Private Story');
            });

            it('should return public and community stories to confirmed users', function () {
                // Create users
                $author = alice($this);
                $viewer = bob($this);

                // Create stories
                publicStory('Public Story', $author->id);
                communityStory('Community Story', $author->id);
                privateStory('Private Story', $author->id);

                // Confirmed but NOT collaborator should not see private
                $this->actingAs($viewer);
                $response = $this->get('/search/partial?q=Story');

                $response->assertOk()
                    ->assertSeeText('Public Story')
                    ->assertSeeText('Community Story')
                    ->assertDontSeeText('Private Story');
            });

            it('should return public, community and private stories to confirmed collaborators (such as initial author)', function () {
                // Create users
                $author = alice($this);

                // Create stories
                publicStory('Public Story', $author->id);
                communityStory('Community Story', $author->id);
                privateStory('Private Story', $author->id);

                // Confirmed and collaborator should see private
                $this->actingAs($author);
                $response = $this->get('/search/partial?q=Story');

                $response->assertOk()
                    ->assertSeeText('Public Story')
                    ->assertSeeText('Community Story')
                    ->assertSeeText('Private Story');
            });
        });

        describe('Regarding description', function () {

            it('should work on story description if search term is 4 or more characters long', function () {
                $author = alice($this);
                publicStory('Title Foo', $author->id, ['description' => 'The hero chercher finds a clé.']);

                // length 3 -> should not match summary
                $this->get('/search/partial?q=clé')
                    ->assertOk()
                    ->assertDontSeeText('Title Foo');

                // length 5 -> should match summary
                $this->get('/search/partial?q=chercher')
                    ->assertOk()
                    ->assertSeeText('Title Foo');
            });
        });

        it('should highlight search terms', function () {
            $author = alice($this);
            publicStory('My Special Story', $author->id);

            $resp = $this->get('/search/partial?q=Special');

            $resp->assertOk();
            $resp->assertSee('<mark>Special</mark>', false);
        });
    });

    describe('Regarding profiles', function () {
        it('should return matching users', function () {
            alice($this);
            bob($this);

            $response = $this->get('/search/partial?q=Alice');

            $response->assertOk()
                ->assertSeeText('Alice')
                ->assertDontSeeText('Bob');
        });

        it('should highlight search terms', function () {
            alice($this);
            bob($this);

            $response = $this->get('/search/partial?q=Alice');

            $response->assertOk()
                ->assertSee('<mark>Alice</mark>', false);
        });
    });
});
