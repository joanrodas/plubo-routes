<?php
namespace PluboRoutes\Route;

/**
 * Route Interface.
 *
 */
interface RouteInterface extends \Serializable
{
    public function getName();
    public function getPath();
    public function getAction();
    public function getConfig();
    public function hasCallback();
    public function addArg($arg);
    public function getArgs();
    public function getExtraVars();
}
