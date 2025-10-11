<?php

namespace App\Domains\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogDownloadController
{
    public function __invoke(Request $request)
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->hasRole('tech-admin'), 403);

        $file = (string) $request->query('file', '');
        $clean = basename($file);
        $path = storage_path('logs' . DIRECTORY_SEPARATOR . $clean);

        abort_unless($clean !== '' && is_file($path) && is_readable($path), 404);

        $downloadName = $clean;
        return response()->download($path, $downloadName, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
