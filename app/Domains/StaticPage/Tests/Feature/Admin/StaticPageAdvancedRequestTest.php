<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function advancedStaticPagePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Page avancée',
        'slug' => 'page-avancee-' . uniqid(),
        'summary' => 'Résumé',
        'status' => 'draft',
        'mode' => 'advanced',
        'blocks_order' => 'b0,b1',
        'blocks' => [
            'b0' => ['type' => 'text', 'html' => '<p>Intro</p>'],
            'b1' => [
                'type' => 'image',
                'file' => UploadedFile::fake()->image('body.jpg', 800, 600),
                'alt' => 'Image du corps',
            ],
        ],
    ], $overrides);
}

describe('Static page advanced mode — HTTP request', function () {
    it('saves an advanced page through the admin store endpoint', function () {
        $response = $this->actingAs(admin($this))
            ->post(route('static.admin.store'), advancedStaticPagePayload([
                'slug' => 'store-avance',
            ]));

        $response->assertRedirect(route('static.admin.index'));
        $response->assertSessionHasNoErrors();

        $page = StaticPage::where('slug', 'store-avance')->firstOrFail();
        expect($page->content_blocks)->toHaveCount(2);
        expect($page->content)->not->toBeEmpty();
    });

    it('saves an advanced page through the admin update endpoint', function () {
        $page = StaticPage::factory()->create(['slug' => 'update-avance']);

        $response = $this->actingAs(admin($this))
            ->put(route('static.admin.update', $page), advancedStaticPagePayload([
                'slug' => 'update-avance',
                'title' => 'Mise à jour avancée',
            ]));

        $response->assertRedirect(route('static.admin.index'));
        $response->assertSessionHasNoErrors();

        $page->refresh();
        expect($page->title)->toBe('Mise à jour avancée');
        expect($page->content_blocks)->toHaveCount(2);
        expect($page->content)->not->toBeEmpty();
    });

    it('rejects an advanced payload with no blocks', function () {
        $response = $this->actingAs(admin($this))
            ->post(route('static.admin.store'), [
                'title' => 'Sans blocs',
                'slug' => 'sans-blocs',
                'status' => 'draft',
                'mode' => 'advanced',
            ]);

        $response->assertSessionHasErrors(['blocks']);
    });

    it('still requires content in simple mode', function () {
        $response = $this->actingAs(admin($this))
            ->post(route('static.admin.store'), [
                'title' => 'Simple',
                'slug' => 'simple-sans-contenu',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors(['content']);
    });

    it('accepts an advanced image block without alt text', function () {
        $response = $this->actingAs(admin($this))
            ->post(route('static.admin.store'), advancedStaticPagePayload([
                'slug' => 'sans-alt',
                'blocks_order' => 'b0',
                'blocks' => [
                    'b0' => [
                        'type' => 'image',
                        'file' => UploadedFile::fake()->image('no-alt.jpg', 400, 300),
                        'alt' => '',
                    ],
                ],
            ]));

        $response->assertRedirect();
        $page = StaticPage::query()->where('slug', 'sans-alt')->firstOrFail();
        expect($page->content_blocks[0]['alt'])->toBe('');
        expect($page->content_blocks[0]['path'])->not->toBeEmpty();
    });

    it('denies a non-admin posting an advanced payload', function () {
        $before = StaticPage::count();

        $response = $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
            ->post(route('static.admin.store'), advancedStaticPagePayload([
                'slug' => 'interdit-avance',
            ]));

        $response->assertRedirect(route('dashboard'));
        expect(StaticPage::count())->toBe($before);
        // alice() may write a profile picture; the advanced body upload must not land.
        expect(Storage::disk('public')->allFiles('static-pages'))->toBeEmpty();
    });
});
