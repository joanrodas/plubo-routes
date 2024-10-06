<?php

namespace PluboRoutes\Middleware;

class RateLimitMiddleware implements MiddlewareInterface
{
    private $maxRequests;
    private $windowTime;
    private $prefix = 'rate_limit_';
    private $type;

    public function __construct($maxRequests = 10, $windowTime = 60, $type = 'ip')
    {
        $this->maxRequests = max(1, intval($maxRequests));
        $this->windowTime = max(1, intval($windowTime));
        $this->type = in_array($type, ['ip', 'user', 'endpoint']) ? $type : 'ip';
    }

    public function handle($request, $next)
    {
        $key = $this->getRateLimitKey($request);
        $requestCount = get_transient($key);

        if ($requestCount === false) {
            set_transient($key, 1, $this->windowTime);
        } else {
            if ($requestCount >= $this->maxRequests) {
                do_action('plubo/rate_limit_exceeded', $request, $this->type);
                return new \WP_REST_Response(['error' => 'Too many requests'], 429);
            }
            set_transient($key, $requestCount + 1, $this->windowTime);
        }

        $response = $next($request);

        // Add rate limit headers
        $response->header('X-RateLimit-Limit', $this->maxRequests);
        $response->header('X-RateLimit-Remaining', max(0, $this->maxRequests - ($requestCount + 1)));
        $response->header('X-RateLimit-Reset', time() + $this->windowTime);

        return $response;
    }

    protected function getRateLimitKey($request)
    {
        switch ($this->type) {
            case 'user':
                $identifier = get_current_user_id() ?: $this->getClientIp();
                break;
            case 'endpoint':
                $identifier = $request->get_route();
                break;
            case 'ip':
            default:
                $identifier = $this->getClientIp();
        }
        return $this->prefix . md5($identifier . $this->type);
    }

    protected function getClientIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }
}
