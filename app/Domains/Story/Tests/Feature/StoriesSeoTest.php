<?php

use App\Domains\Shared\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders SEO tags on stories index', function () {
    // Act
    $resp = $this->get('/stories');

    // Assert
    $resp->assertOk();

    // <title>
    $resp->assertTextContains('head > title', __("story::seo.index.title") . ' – ' . config('app.name'));

    // meta description
    $resp->assertHasAttribute('head > meta[name="description"]', 'content', __("story::seo.index.description"));

    // OG basic
    $resp->assertHasAttribute('head > meta[property="og:type"]', 'content', 'website');
    $resp->assertHasAttribute('head > meta[property="og:title"]', 'content', __("story::seo.index.title"));
    $resp->assertHasAttribute('head > meta[property="og:description"]', 'content', __("story::seo.index.description"));
    $resp->assertAttributeContains('head > meta[property="og:image"]', 'content', '/images/story/default-cover.svg');

    // Twitter
    $resp->assertHasAttribute('head > meta[name="twitter:card"]', 'content', 'summary_large_image');
    $resp->assertHasAttribute('head > meta[name="twitter:title"]', 'content', __("story::seo.index.title"));
    $resp->assertHasAttribute('head > meta[name="twitter:description"]', 'content', __("story::seo.index.description"));
    $resp->assertAttributeContains('head > meta[name="twitter:image"]', 'content', '/images/story/default-cover.svg');
});

it('renders SEO tags on story show', function () {
    // Arrange
    $author = alice();
    $story = publicStory('Epic Tale', $author, [
        'description' => '<p>This is a <strong>great</strong> story with a long description that should be trimmed for meta purposes.</p>'
    ]);

    // Act
    $resp = $this->get('/stories/' . $story->slug);

    // Assert
    $resp->assertOk();

    $expectedTitle = $story->title . ' – ' . config('app.name');
    $expectedDesc  = Seo::excerpt($story->description);

    // <title>
    $resp->assertTextContains('head > title', $expectedTitle);

    // meta description
    $resp->assertHasAttribute('head > meta[name="description"]', 'content', $expectedDesc);

    // OG
    $resp->assertHasAttribute('head > meta[property="og:type"]', 'content', 'article');
    $resp->assertHasAttribute('head > meta[property="og:title"]', 'content', $story->title);
    $resp->assertHasAttribute('head > meta[property="og:description"]', 'content', $expectedDesc);
    $resp->assertAttributeContains('head > meta[property="og:image"]', 'content', '/images/story/default-cover.svg');

    // Twitter
    $resp->assertHasAttribute('head > meta[name="twitter:card"]', 'content', 'summary_large_image');
    $resp->assertHasAttribute('head > meta[name="twitter:title"]', 'content', $story->title);
    $resp->assertHasAttribute('head > meta[name="twitter:description"]', 'content', $expectedDesc);
    $resp->assertAttributeContains('head > meta[name="twitter:image"]', 'content', '/images/story/default-cover.svg');
});
