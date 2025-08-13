<?php

namespace App\Domains\Announcement\Providers;

use Illuminate\Support\ServiceProvider;

class AnnouncementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load PHP namespaced translations for the Announcement public domain
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'announcement');
    }
}
