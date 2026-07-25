<?php

use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News admin form renders the multi-editor', function () {
    it('renders the create form with the multi-editor wired', function () {
        $this->actingAs(admin($this))
            ->get(route('news.admin.create'))
            ->assertOk()
            ->assertSee('multiEditor(', false)
            ->assertSee('name="mode"', false)
            ->assertSee('name="blocks_order"', false);
    });

    it('opens an advanced article in advanced mode', function () {
        $news = News::create([
            'title' => 'Advanced', 'slug' => 'advanced-' . uniqid(), 'summary' => 's',
            'status' => 'draft', 'content' => '<p>cache</p>',
            'content_blocks' => [['type' => 'text', 'html' => '<p>Body</p>']],
        ]);

        $this->actingAs(admin($this))
            ->get(route('news.admin.edit', $news))
            ->assertOk()
            ->assertSee("mode: 'advanced'", false)
            ->assertSee('name="blocks[b0][html]"', false);
    });
});
