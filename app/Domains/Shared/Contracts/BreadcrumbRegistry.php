<?php

namespace App\Domains\Shared\Contracts;

use App\Domains\Shared\Support\BreadcrumbTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreadcrumbRegistry
{
    /** @var array<string, callable> */
    private array $builders = [];

    /**
     * Register a breadcrumb builder for a given route name.
     * @param string $routeName
     * @param callable $builder fn(BreadcrumbTrail $trail, array $params): void
     */
    public function for(string $routeName, callable $builder): void
    {
        $this->builders[$routeName] = $builder;
    }

    /**
     * Generate breadcrumbs for the given request.
     * @return array<int, \App\Domains\Shared\Support\Breadcrumb>
     */
    public function generateForRequest(Request $request): array
    {
        $trail = new BreadcrumbTrail();

        // Root crumb: Home for guests, Dashboard for authenticated users
        if (Auth::check()) {
            $trail->push(__('Dashboard'), route('dashboard'));
        } else {
            $trail->push(__('Home'), route('home'));
        }

        $route = $request->route();
        if (!$route) {
            $trail->markLastActive();
            return $trail->all();
        }

        $name = $route->getName();
        $params = $route->parameters();

        if ($name && isset($this->builders[$name])) {
            ($this->builders[$name])($trail, $params);
        }

        // Ensure last crumb is marked active
        $trail->markLastActive();

        return $trail->all();
    }
}
