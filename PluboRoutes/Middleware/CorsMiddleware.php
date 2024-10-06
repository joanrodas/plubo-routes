<?php

namespace PluboRoutes\Middleware;

class CorsMiddleware implements MiddlewareInterface
{
    private $allowedOrigins;
    private $allowedMethods;
    private $allowedHeaders;
    private $maxAge = 3600;

    public function __construct($allowedOrigins = '*', $allowedMethods = ['GET', 'POST', 'OPTIONS'], $allowedHeaders = ['Content-Type', 'Authorization'])
    {
        $this->allowedOrigins = is_array($allowedOrigins) ? $allowedOrigins : [$allowedOrigins];
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);
        $this->allowedHeaders = array_map('strtolower', $allowedHeaders);
    }

    public function handle($request, $next)
    {
        $origin = $request->get_header('origin');

        if ($this->isAllowedOrigin($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
            header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));
            header("Access-Control-Max-Age: {$this->maxAge}");
        }

        if ($request->get_method() === 'OPTIONS') {
            return new \WP_REST_Response(null, 204);
        }

        if (!in_array($request->get_method(), $this->allowedMethods)) {
            return new \WP_REST_Response(['error' => 'Method not allowed'], 405);
        }

        return $next($request);
    }

    protected function isAllowedOrigin($origin)
    {
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }
        return in_array($origin, $this->allowedOrigins);
    }
}
