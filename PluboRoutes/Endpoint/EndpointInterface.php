<?php

namespace PluboRoutes\Endpoint;

/**
 * Route Interface.
 *
 */
interface EndpointInterface
{
    public function getNamespace();
    public function getPath();
    public function getConfig();
    public function getPermissionCallback();
    public function getMethod();

    public function useMiddleware(callable $middleware);
    public function getMiddlewareStack();
}
