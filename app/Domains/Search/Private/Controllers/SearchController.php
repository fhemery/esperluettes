<?php

namespace App\Domains\Search\Private\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Search\Private\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function partial(Request $request)
    {
        $q = (string) $request->query('q', '');
        $storiesPage = (int) $request->query('stories_page', 1);
        $profilesPage = (int) $request->query('profiles_page', 1);
        $perPage = min(5, max(1, (int) $request->query('per_page', 5)));

        $vm = $this->search->buildViewModel($q, $storiesPage, $profilesPage, $perPage);

        return view('search::partials.search-results', $vm);
    }
}
