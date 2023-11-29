<?php

namespace PluboRoutes\Route;

/**
 * Common route functions.
 *
 */
trait RouteTrait
{

    /**
     * The URL path that the route needs to match.
     *
     * @var string
     */
    private $path;

    /**
     * The optional config of the route.
     *
     * @var array
     */
    private $config;

    /**
     * The matches of the route.
     *
     * @var array
     */
    private $args;

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName()
    {
        return $this->config['name'] ?? md5($this->path);
    }

    /**
     * Get the title of the route.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->config['title'] ?? '';
    }

    /**
     * Get the path to be matched.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the config parameters of the route.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the matches of the route.
     *
     * @param array
     */
    public function addArg($arg)
    {
        $this->args[] = $arg;
    }

    /**
     * Get the matches of the route.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Get extra query vars.
     *
     * @return array
     */
    public function getExtraVars()
    {
        $query_vars = $this->config['extra_vars'] ?? [];
        return $query_vars;
    }

    /**
     * Check if guests have access.
     *
     * @return boolean
     */
    public function guestHasAccess()
    {
        $guest_has_access = $this->config['guest'] ?? true;
        return filter_var($guest_has_access, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if a logged in user has access.
     *
     * @return boolean
     */
    public function memberHasAccess()
    {
        $member_has_access = $this->config['logged_in'] ?? true;
        return filter_var($member_has_access, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if the route has a redirect if access not allowed.
     *
     * @return boolean
     */
    public function hasRedirect()
    {
        $redirect = $this->config['redirect'] ?? false;
        return filter_var(($redirect != false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the http status if access not allowed.
     *
     * @return int
     */
    public function getNotAllowedStatus()
    {
        $status = $this->hasRedirect() ? 302 : 403;
        return $this->config['forbidden_status'] ?? $status;
    }

    /**
     * Get the redirect URL.
     *
     * @return int
     */
    public function getRedirect()
    {
        $redirect = $this->config['redirect'] ?? home_url();
        return $redirect;
    }

    /**
     * Check if the roles option is a callable.
     *
     * @return boolean
     */
    public function hasRolesCallback()
    {
        $roles = $this->config['allowed_roles'] ?? [];
        return is_callable($roles);
    }

    /**
     * Get the allowed roles.
     *
     * @return array|string
     */
    public function getRoles()
    {
        $roles = $this->config['allowed_roles'] ?? false;
        return $roles;
    }

    /**
     * Check if the capabilities option is a callable.
     *
     * @return boolean
     */
    public function hasCapabilitiesCallback()
    {
        $capabilities = $this->config['allowed_caps'] ?? [];
        return is_callable($capabilities);
    }

    /**
     * Get the allowed capabilities.
     *
     * @return array|string
     */
    public function getCapabilities()
    {
        $capabilities = $this->config['allowed_caps'] ?? false;
        return $capabilities;
    }

    /**
     * Get the permission callback.
     *
     * @return callable
     */
    public function getPermissionCallback()
    {
        $permission_callback = $this->config['permission_callback'] ?? '__return_true';
        return $permission_callback;
    }

    /**
     * Serialize the route.
     *
     * @return string
     */
    public function __serialize()
    {
        return [
            'path' => $this->path,
            'extra_vars' => $this->getExtraVars()
        ];
    }

    /**
     * Unserialize the route.
     *
     * @param array
     */
    public function __unserialize($data)
    {
        $this->path = $data['path'];
        $this->config['extra_vars'] = $data['extra_vars'];
    }
}
