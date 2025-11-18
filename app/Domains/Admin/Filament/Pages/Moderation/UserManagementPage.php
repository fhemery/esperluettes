<?php

namespace App\Domains\Admin\Filament\Pages\Moderation;

use App\Domains\Auth\Public\Api\Roles;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UserManagementPage extends Page
{
    protected static ?string $slug = 'user-management';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'admin::pages.moderation.user-management';

    public static function getNavigationLabel(): string
    {
        return __('admin::moderation.user_management.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin::moderation.navigation_group');
    }

    public function getTitle(): string
    {
        return __('admin::moderation.user_management.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]) ?? false;
    }
}
