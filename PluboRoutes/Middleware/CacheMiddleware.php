<?php

namespace PluboRoutes\Middleware;

class CacheMiddleware implements MiddlewareInterface
{
    private $cacheTime;

    public function __construct($cacheTime = 300)
    {
        $this->cacheTime = $cacheTime;
    }

    public function handle($request, $next)
    {
        $cacheKey = 'route_cache_' . md5($_SERVER['REQUEST_URI']);
        $cachedResponse = get_transient($cacheKey);

        if ($cachedResponse) {
            return $cachedResponse;
        }

        $response = $next($request);
        set_transient($cacheKey, $response, $this->cacheTime);

        return $response;
    }
}
