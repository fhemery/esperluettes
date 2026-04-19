<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Hard safety: ensure tests always use SQLite even if config was cached
        if ($app->environment('testing')) {
            $app['config']->set('database.default', 'sqlite');
        }

        // Give each worker process its own compiled view directory to prevent
        // race conditions when multiple workers write to the same cache file.
        $compiledPath = '/tmp/laravel-views-testing-' . getmypid();
        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }
        $app['config']->set('view.compiled', $compiledPath);

        return $app;
    }
}
