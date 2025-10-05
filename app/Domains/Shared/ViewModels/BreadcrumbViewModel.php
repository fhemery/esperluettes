<?php

namespace App\Domains\Shared\ViewModels;

class BreadcrumbViewModel
{
    /** @var array<int,BreadcrumbPartViewModel> */
    private array $items = [];

    public static function FromHome(bool $isLoggedIn): self
    {
        $trail = new self();
        if ($isLoggedIn) {
            $trail->push('', route('dashboard'), false, 'home');
        } else {
            $trail->push('', route('home'), false, 'home');
        }
        return $trail;
    }

    public function push(string $label, ?string $url = null, bool $active = false, ?string $icon = null): self
    {
        $this->items[] = new BreadcrumbPartViewModel($label, $url, $active, $icon);
        return $this;
    }

    /**
     * @return array<int,BreadcrumbPartViewModel>
     */
    public function all(): array
    {
        return $this->items;
    }
}
