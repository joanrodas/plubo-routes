<?php

namespace PluboRoutes\Middleware;

class CorsMiddleware implements MiddlewareInterface
{
    private $allowedOrigins;
    private $allowedMethods;
    private $allowedHeaders;

    public function __construct($allowedOrigins = '*', $allowedMethods = ['GET', 'POST', 'OPTIONS'], $allowedHeaders = ['Content-Type', 'Authorization'])
    {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }

    public function handle($request, $next)
    {
        // Set CORS headers
        header("Access-Control-Allow-Origin: {$this->allowedOrigins}");
        header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));

        // If it's a preflight request (OPTIONS), allow it without running further middleware
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }

        // Enforce allowed methods for the actual request
        if (!in_array($_SERVER['REQUEST_METHOD'], $this->allowedMethods)) {
            return new \WP_REST_Response(['error' => 'Method not allowed'], 405); // 405 Method Not Allowed
        }

        return $next($request);
    }
}
