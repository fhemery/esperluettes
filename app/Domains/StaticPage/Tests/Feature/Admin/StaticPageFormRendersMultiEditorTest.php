<?php

use App\Domains\StaticPage\Private\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StaticPage admin form renders the multi-editor', function () {
    it('renders the create form with the multi-editor wired', function () {
        $this->actingAs(admin($this))
            ->get(route('static.admin.create'))
            ->assertOk()
            ->assertSee('multiEditor(', false)
            ->assertSee('name="mode"', false)
            ->assertSee('name="blocks_order"', false);
    });

    it('opens an advanced page in advanced mode', function () {
        $page = StaticPage::factory()->create([
            'content' => '<p>cache</p>',
            'content_blocks' => [['type' => 'text', 'html' => '<p>Body</p>']],
        ]);

        $this->actingAs(admin($this))
            ->get(route('static.admin.edit', $page))
            ->assertOk()
            ->assertSee("mode: 'advanced'", false)
            ->assertSee('name="blocks[b0][html]"', false);
    });

    it('opens a legacy simple page in simple mode with its content intact', function () {
        $page = StaticPage::factory()->create([
            'content' => '<p>Legacy body content</p>',
            'content_blocks' => null,
        ]);

        $this->actingAs(admin($this))
            ->get(route('static.admin.edit', $page))
            ->assertOk()
            ->assertSee("mode: 'simple'", false)
            ->assertSee('Legacy body content', false);
    });

    it('orders the fields title, slug, header image, summary, body', function () {
        $expected = [
            'name="title"',
            'name="slug"',
            'name="header_image[path]"',
            'name="summary"',
            'multiEditor(',
        ];

        $this->actingAs(admin($this))
            ->get(route('static.admin.create'))
            ->assertOk()
            ->assertSeeInOrder($expected, false);

        $page = StaticPage::factory()->create();

        $this->actingAs(admin($this))
            ->get(route('static.admin.edit', $page))
            ->assertOk()
            ->assertSeeInOrder($expected, false);
    });

    it('no longer renders a standalone media section heading', function () {
        // zz locale: assert the raw key (removed from fr; __() would fail TranslationKeysExistTest).
        $this->actingAs(admin($this))
            ->get(route('static.admin.create'))
            ->assertOk()
            ->assertDontSee('static::admin.form.media_section');
    });
});
