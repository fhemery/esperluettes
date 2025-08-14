<?php

namespace App\Domains\StaticPage\Database\Factories;

use App\Domains\StaticPage\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StaticPage>
 */
class StaticPageFactory extends Factory
{
    protected $model = StaticPage::class;

    public function definition(): array
    {
        $title = 'Page ' . uniqid();
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => 'Résumé pour ' . $title,
            'content' => '<p>Contenu pour ' . e($title) . '</p>',
            'header_image_path' => null,
            'status' => 'draft',
            'meta_description' => 'Meta description for ' . $title,
            'published_at' => null,
            'created_by' => 1,
        ];
    }

    public function published(): self
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now(),
        ]);
    }
}
