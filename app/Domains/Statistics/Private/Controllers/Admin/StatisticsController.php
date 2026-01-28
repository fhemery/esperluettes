<?php

namespace App\Domains\Statistics\Private\Controllers\Admin;

use App\Domains\Statistics\Private\Services\StatisticQueryService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticQueryService $queryService,
    ) {}

    public function index(): View
    {
        return view('statistics::pages.admin.index');
    }
}
