<?php

namespace App\Domains\FAQ\Private\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\ServiceProvider;

class FaqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/FAQ/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/FAQ/Private/routes.php'));
        $this->loadViewsFrom(app_path('Domains/FAQ/Private/Resources/views'), 'faq');
        $this->loadTranslationsFrom(app_path('Domains/FAQ/Private/Resources/lang'), 'faq');

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerPage(
            key: 'faq.categories',
            group: 'faq',
            labelTranslationKey: 'faq::admin.categories.nav_label',
            target: AdminRegistryTarget::route('faq.admin.faq-categories.index'),
            icon: 'folder',
            permissions: [Roles::ADMIN, Roles::TECH_ADMIN],
            sortOrder: 1,
        );

        $registry->registerPage(
            key: 'faq.questions',
            group: 'faq',
            labelTranslationKey: 'faq::admin.questions.nav_label',
            target: AdminRegistryTarget::route('faq.admin.faq-questions.index'),
            icon: 'help',
            permissions: [Roles::ADMIN, Roles::TECH_ADMIN],
            sortOrder: 2,
        );
    }
}
