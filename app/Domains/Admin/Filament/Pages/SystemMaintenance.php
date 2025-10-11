<?php

namespace App\Domains\Admin\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class SystemMaintenance extends Page
{
    protected static ?string $slug = 'system-maintenance';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'admin::pages.system-maintenance';

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('admin::pages.system_maintenance.nav_label');
    }

    public static function getNavigationSort(): ?int
    {
        // Put above BackHome (-1) so it stays at the very top
        return -2;
    }

    public function getTitle(): string
    {
        return __('admin::pages.system_maintenance.nav_label');
    }

    public function getHeading(): string
    {
        return __('admin::pages.system_maintenance.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin::pages.groups.tech');
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('tech-admin') ?? false;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('tech-admin') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clearCache')
                ->label(__('admin::pages.system_maintenance.actions.clear_cache'))
                ->icon('heroicon-o-bolt')
                ->requiresConfirmation()
                ->action(function () {
                    // Single call that clears config, route, view caches, etc.
                    Artisan::call('optimize:clear');

                    Notification::make()
                        ->title(__('admin::pages.system_maintenance.notifications.cache_cleared'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
