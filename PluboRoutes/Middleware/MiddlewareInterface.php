<?php

namespace PluboRoutes\Middleware;

use WP_REST_Request;

interface MiddlewareInterface
{
    public function handle(array $request, callable $next);
}
