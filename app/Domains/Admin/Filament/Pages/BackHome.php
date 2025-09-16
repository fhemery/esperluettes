<?php

namespace App\Domains\Admin\Filament\Pages;

use Filament\Pages\Page;

class BackHome extends Page
{
    // A view is required by Filament Pages, but we redirect immediately in mount,
    // so this will never actually render.
    protected static string $view = 'filament-panels::pages.dashboard';

    protected static ?string $slug = 'back-home';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function getNavigationLabel(): string
    {
        return __('admin::menu.back-home');
    }

    public static function getNavigationSort(): ?int
    {
        // Place near the top of the sidebar
        return -1;
    }

    public function mount()
    {
        // Redirect to the public home route
        return redirect()->to(route('home'));
    }
}
