<?php

declare(strict_types=1);

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Auth\Private\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

class RoleLookupController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 25);
        if (mb_strlen($q) < 2) {
            return response()->json(['roles' => []]);
        }

        $models = $this->roles->searchByName($q, $limit);
        
        $items = array_map(fn($r) => ['name' => (string) $r->name, 'slug' => (string) $r->slug], $models);
        return response()->json(['roles' => $items]);
    }

    public function bySlugs(Request $request): JsonResponse
    {
        $slugsParam = (string) $request->query('slugs', '');
        $slugs = array_values(array_unique(array_filter(array_map(function ($s) {
            return is_string($s) ? trim($s) : '';
        }, explode(',', $slugsParam)), fn ($s) => $s !== '')));
        if (empty($slugs)) {
            return response()->json(['roles' => []]);
        }

        $models = $this->roles->getBySlugs($slugs);
        
        $items = array_map(fn($r) => ['name' => (string) $r->name, 'slug' => (string) $r->slug], $models);
        return response()->json(['roles' => $items]);
    }
}
