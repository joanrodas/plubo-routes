<?php
namespace PluboRoutes\Endpoint;

/**
 * Route Interface.
 *
 */
interface EndpointInterface extends \Serializable
{
    public function getNamespace();
    public function getPath();
    public function getConfig();
    public function addArg($arg);
    public function getArgs();
}
