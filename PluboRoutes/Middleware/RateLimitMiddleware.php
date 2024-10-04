<?php

namespace PluboRoutes\Middleware;

class RateLimitMiddleware implements MiddlewareInterface
{
    private $maxRequests;
    private $windowTime;

    public function __construct($maxRequests = 10, $windowTime = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowTime = $windowTime;
    }

    public function handle($request, $next)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $requestCount = get_transient("rate_limit_{$ip}");

        if ($requestCount === false) {
            set_transient("rate_limit_{$ip}", 1, $this->windowTime);
        } else {
            if ($requestCount >= $this->maxRequests) {
                return new \WP_REST_Response(['error' => 'Too many requests'], 429);
            }
            set_transient("rate_limit_{$ip}", $requestCount + 1, $this->windowTime);
        }

        return $next($request);
    }
}
