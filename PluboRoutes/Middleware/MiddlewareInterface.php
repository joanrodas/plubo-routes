<?php

namespace PluboRoutes\Middleware;

interface MiddlewareInterface
{
    public function handle($request, $next);
}
