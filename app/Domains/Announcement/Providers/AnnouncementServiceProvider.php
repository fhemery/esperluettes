<?php

namespace App\Domains\Announcement\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Announcement\Models\Announcement;
use App\Domains\Announcement\Observers\AnnouncementObserver;

class AnnouncementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load PHP namespaced translations for the Announcement public domain
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'announcement');

        // Model observers
        Announcement::observe(AnnouncementObserver::class);
    }
}
