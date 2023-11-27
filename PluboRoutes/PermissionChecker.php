<?php

namespace PluboRoutes;

use PluboRoutes\Route\Route;

/**
 * The PermissionChecker class is responsible for checking roles, capabilities and custom permissions
 *
 */
class PermissionChecker
{

    /**
     * The matched route found by the router.
     *
     * @var Route
     */
    private $matched_route;

    /**
     * The matched args found by the router.
     *
     * @var array
     */
    private $matched_args;

    /**
     * The matched args found by the router.
     *
     * @var \WP_User
     */
    private $current_user;

    /**
     * Constructor.
     *
     */
    public function __construct(Route $route, array $args)
    {
        $this->matched_route = $route;
        $this->matched_args = $args;
        $this->current_user = wp_get_current_user();
    }

    public function checkPermissions()
    {
        $permission_callback = $this->matched_route->getPermissionCallback();
        if (!$permission_callback || !is_callable($permission_callback)) {
            return;
        }
        $has_access = call_user_func($permission_callback, $this->matched_args);
        if (!$has_access) {
            $this->forbidAccess();
        }

        if ($this->checkLoggedIn()) {
            $this->checkRoles();
            $this->checkCapabilities();
        }
    }

    private function checkLoggedIn()
    {
        $is_logged_in = $this->current_user->exists();
        if (
            !$this->matched_route->guestHasAccess() && !$is_logged_in
            || !$this->matched_route->memberHasAccess() && $is_logged_in
        ) {
            $this->forbidAccess();
        }
        return $is_logged_in;
    }

    private function checkRoles()
    {
        $allowed_roles = $this->matched_route->getRoles();
        if ($this->matched_route->hasRolesCallback()) {
            $allowed_roles = call_user_func($allowed_roles, $this->matched_args);
        }
        if ($allowed_roles !== false && !array_intersect((array)$this->current_user->roles, (array)$allowed_roles)) {
            $this->forbidAccess();
        }
    }

    private function checkCapabilities()
    {
        $allowed_caps = $this->getAllowedCapabilities();
        if ($allowed_caps === false) {
            return;
        }
        $is_allowed = false;
        foreach ((array)$allowed_caps as $allowed_cap) {
            if ($this->current_user->has_cap($allowed_cap)) {
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            $this->forbidAccess();
        }
    }

    private function getAllowedCapabilities()
    {
        $allowed_caps = $this->matched_route->getCapabilities();
        if ($this->matched_route->hasCapabilitiesCallback()) {
            $allowed_caps = call_user_func($allowed_caps, $this->matched_args);
        }
        return $allowed_caps;
    }

    private function forbidAccess()
    {
        if ($this->matched_route->hasRedirect()) {
            wp_redirect($this->matched_route->getRedirect(), $this->matched_route->getNotAllowedStatus());
            exit;
        }
        status_header($this->matched_route->getNotAllowedStatus());
        exit;
    }
}
