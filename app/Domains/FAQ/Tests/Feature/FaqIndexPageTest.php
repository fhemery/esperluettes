<?php

namespace App\Domains\FAQ\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('FAQ index page', function () {
    it('shows the FAQ page with translated title for guests', function () {
        $response = $this->get('/faq');

        $response->assertOk();
        $response->assertSee(__('faq::index.title'));
    });

    it('displays categories as tabs ordered by sort_order with the first highlighted', function () {
        $this->actingAs(admin($this));
        createFaqCategory('Alpha', null, true, 2);
        createFaqCategory('Beta', null, true, 1);
        createFaqCategory('Gamma', null, true, 3);
        createFaqCategory('Hidden', null, false, 0);

        Auth::logout();
        $response = $this->get('/faq');
        $response->assertOk();
        $response->assertSeeInOrder(['Beta', 'Alpha', 'Gamma']);
        $response->assertSeeInOrder(['/faq/beta', '/faq/alpha', '/faq/gamma'], false);
        $response->assertDontSee('/faq/hidden');
        $response->assertDontSee('Hidden');
    });

    it('shows only questions of the selected category in order, hidden answers', function () {
        $this->actingAs(admin($this));

        // Create categories
        $catA = createFaqCategory('Alpha', null, true, 2);
        $catB = createFaqCategory('Beta', null, true, 1);

        // Create questions via Public API
        $api = app(\App\Domains\FAQ\Public\Api\FaqPublicApi::class);
        $q1 = new \App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto(
            faqCategoryId: $catB->id,
            question: 'Beta Q1',
            answer: '<p>Answer B1</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 2,
        );
        $q2 = new \App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto(
            faqCategoryId: $catB->id,
            question: 'Beta Q0',
            answer: '<p>Answer B0</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );
        $qOther = new \App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto(
            faqCategoryId: $catA->id,
            question: 'Alpha QX',
            answer: '<p>Answer AX</p>',
            imagePath: null,
            imageAltText: null,
            isActive: true,
            sortOrder: 1,
        );
        $api->createQuestion($q1);
        $api->createQuestion($q2);
        $api->createQuestion($qOther);

        \Illuminate\Support\Facades\Auth::logout();

        // Access Beta category page
        $response = $this->get('/faq/beta');
        $response->assertOk();
        // Questions are ordered by sort_order (Beta Q0 then Beta Q1)
        $response->assertSeeInOrder(['Beta Q0', 'Beta Q1']);
        // Should not see other category question
        $response->assertDontSee('Alpha QX');
    });

    describe('FAQ SEO', function () {
        it('sets page title and description for first category on /faq', function () {
            $this->actingAs(admin($this));
            createFaqCategory('Beta', null, true, 1);
            createFaqCategory('Alpha', null, true, 2);
            \Illuminate\Support\Facades\Auth::logout();

            $response = $this->get('/faq');
            $response->assertOk();

            $site = config('app.name');
            $expectedTitle = sprintf('FAQ - %s - %s', 'Beta', $site);
            $expectedDesc = __('faq::index.seo_description', ['category' => 'Beta', 'site' => $site]);

            $response->assertSee("<title>{$expectedTitle}</title>", false);
            $response->assertSee('<meta name="description" content="' . e($expectedDesc) . '"', false);
        });

        it('sets page title and description for selected category on /faq/{slug}', function () {
            $this->actingAs(admin($this));
            createFaqCategory('Alpha', null, true, 2);
            createFaqCategory('Beta', null, true, 1);
            \Illuminate\Support\Facades\Auth::logout();

            $response = $this->get('/faq/alpha');
            $response->assertOk();

            $site = config('app.name');
            $expectedTitle = sprintf('FAQ - %s - %s', 'Alpha', $site);
            $expectedDesc = __('faq::index.seo_description', ['category' => 'Alpha', 'site' => $site]);

            $response->assertSee("<title>{$expectedTitle}</title>", false);
            $response->assertSee('<meta name="description" content="' . e($expectedDesc) . '"', false);
        });
    });
});
