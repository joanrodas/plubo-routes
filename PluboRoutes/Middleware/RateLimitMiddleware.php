<?php

namespace PluboRoutes\Middleware;

use WP_REST_Response;
use WP_Error;

class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Maximum number of allowed requests within the window time.
     *
     * @var int
     */
    private $maxRequests;

    /**
     * Maximum number of allowed requests within the window time.
     *
     * @var int
     */
    private $windowTime;

    /**
     * Prefix for storing transients related to rate limiting.
     *
     * @var string
     */
    private $prefix = 'rate_limit_';

    /**
     * Type of rate limiting: 'ip', 'user', or 'endpoint'.
     *
     * @var string
     */
    private $type;

    public function __construct(
        int $maxRequests = 10,
        int $windowTime = 60,
        string $type = 'ip'
    ) {
        $this->maxRequests = max(1, intval($maxRequests));
        $this->windowTime = max(1, intval($windowTime));
        $this->type = in_array($type, ['ip', 'user', 'endpoint']) ? $type : 'ip';
    }

    /**
     * Handles the incoming request and enforces rate limiting.
     *
     * @param mixed    $request The incoming request object.
     * @param callable $next    The next middleware to execute.
     *
     * @return WP_REST_Response|WP_Error The response after rate limiting.
     */
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
        if ($response instanceof WP_REST_Response) {
            $response->set_headers([
                'X-RateLimit-Limit'     => $this->maxRequests,
                'X-RateLimit-Remaining' => max(0, $this->maxRequests - ($requestCount + 1)),
                'X-RateLimit-Reset'     => time() + $this->windowTime,
            ]);
        }

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

    /**
     * Retrieves the client's IP address securely.
     *
     * @return string The sanitized client IP or 'unknown'.
     */
    protected function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                // Handle multiple IPs (e.g., X-Forwarded-For: client, proxy1, proxy2)
                $ips = explode(',', wp_unslash($_SERVER[$key]));
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return sanitize_text_field($ip);
                    }
                }
            }
        }

        return 'unknown';
    }
}
