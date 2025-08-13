<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Ensure SQLite test database file exists
        $db = $app['config']['database.connections.sqlite.database'] ?? null;
        if (is_string($db) && str_contains($db, 'database/testing') && $db !== ':memory:') {
            $fs = new Filesystem();
            $path = base_path($db);
            $dir = dirname($path);
            if (! $fs->exists($dir)) {
                $fs->makeDirectory($dir, 0755, true);
            }
            if (! $fs->exists($path)) {
                $fs->put($path, '');
            }
        }

        return $app;
    }
}
