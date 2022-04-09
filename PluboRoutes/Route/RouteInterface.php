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
    public function setArgs($args);
    public function getArgs();
}
