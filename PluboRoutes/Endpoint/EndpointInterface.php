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
}
