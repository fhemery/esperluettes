<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Private\Controllers;

use App\Domains\Config\Public\Api\ConfigPublicApi;
use Illuminate\Contracts\View\View as ViewContract;

class DashboardController
{
    public function index(ConfigPublicApi $config): ViewContract
    {
        $calendarEnabled = $config->isToggleEnabled('enabled', 'calendar');

        return view('dashboard::index', [
            'calendarEnabled' => $calendarEnabled,
        ]);
    }
}
