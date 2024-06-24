<?php

namespace Ominity\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use Ominity\Api\OminityApiClient;
use Ominity\Api\Resources\Cms\Route;

class OminityRouterService
{
    protected OminityApiClient $ominity;

    protected array $config;

    public function __construct(OminityApiClient $ominity, array $config)
    {
        $this->ominity = $ominity;
        $this->config = $config;
    }

    /**
     * Get the route
     *
     * @param  Route|\stdClass  $route
     * @return string
     */
    public function route($route)
    {
        $route = app('router')->getRoutes()->getByName($route->name);
        $requiredParameters = $route->parameterNames();

        $filteredParams = array_filter($route->parameters, function ($key) use ($requiredParameters) {
            return in_array($key, $requiredParameters);
        }, ARRAY_FILTER_USE_KEY);

        return route($route->name, $filteredParams);
    }

    /**
     * Get a cached route or fetch from the API if not cached.
     *
     * @param  string  $name
     * @param  array  $filter
     * @return string|null
     */
    public function get($name, $filter)
    {
        $filter['name'] = $name;

        if ($this->config['cache']['enabled']) {
            $cacheKey = 'route-'.md5(json_encode($filter)).'-'.app()->getLocale();

            return Cache::store($this->config['cache']['store'])->remember(
                $cacheKey,
                $this->config['cache']['expiration'],
                function () use ($filter) {
                    return $this->fetchRoute($filter);
                }
            );
        }

        return $this->fetchRoute($filter);
    }

    /**
     * Fetch route from the API.
     *
     * @param  array  $filter
     * @return string|null
     */
    protected function fetchRoute($filter)
    {
        $routes = $this->ominity->cms->routes->all([
            'limit' => 1,
            'filter' => $filter,
        ]);

        $route = $routes->first();
        if ($route) {
            return $this->route($route);
        }

        return null;
    }
}
