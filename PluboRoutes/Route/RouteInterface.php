<?php
namespace PluboRoutes\Route;

/**
 * Route Interface.
 *
 */
interface RouteInterface
{
    public function getName();
    public function getTitle();
    public function getPath();
    public function getAction();
    public function getConfig();
    public function hasCallback();
    public function addArg($arg);
    public function getArgs();
    public function getExtraVars();
}
