<?php

namespace PluboRoutes\Middleware;

use WP_REST_Request;

interface MiddlewareInterface
{
    public function handle(WP_REST_Request $request, callable $next);
}
