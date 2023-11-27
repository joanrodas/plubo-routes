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
    public function getConfig();
    public function addArg($arg);
    public function getArgs();
    public function getExtraVars();
    public function guestHasAccess();
    public function memberHasAccess();
    public function hasRedirect();
    public function getNotAllowedStatus();
    public function getRedirect();
    public function hasRolesCallback();
    public function getRoles();
    public function hasCapabilitiesCallback();
    public function getCapabilities();
    public function getPermissionCallback();

    public function getAction();
    public function hasCallback();
    public function getStatus();
}
