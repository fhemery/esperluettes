<?php

namespace App\Domains\News\Private\View\Components;

use App\Domains\News\Private\Models\News;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;

class Carousel extends Component
{
    /**
     * size mode: null|compact
     */
    public ?string $size;

    /** @var \Illuminate\Support\Collection<int,\App\Domains\News\Private\Models\News> */
    public $items;

    public function __construct(?string $size = null)
    {
        $this->size = $size;
        $this->items = News::query()
            ->where('status', 'published')
            ->where('is_pinned', true)
            ->orderBy('display_order')
            ->orderByDesc('published_at')
            ->get();
    }

    public function render(): ViewContract
    {
        return view('news::components.carousel', [
            'items' => $this->items,
            'size' => $this->size,
        ]);
    }
}
