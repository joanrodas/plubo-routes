<?php
namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class Route implements RouteInterface
{
    use RouteTrait;

    /**
     * The template that the route wants to load or a callable.
     *
     * @var string\callable
     */
    private $template;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string|callable $template
     * @param array $config
     */
    public function __construct(string $path, $template, array $config = [])
    {
        $this->path = $path;
        $this->template = $template;
        $this->config = $config;
        $this->args = [];
    }

    /**
     * Get the action to be executed when this route is matched.
     *
     * @return string
     */
    public function getAction()
    {
        return "plubo/route_{$this->getName()}";
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function hasCallback()
    {
        return false;
    }

    /**
     * Check if the template is a callable.
     *
     * @return boolean
     */
    public function hasTemplateCallback()
    {
        return is_callable($this->template);
    }

    /**
     * Get the template to be loaded when this route is matched.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
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
        $redirect = $this->config['redirect'] ?? true;
        return filter_var(($redirect != false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the http status if access not allowed.
     *
     * @return int
     */
    public function getStatus()
    {
        $status = $this->hasRedirect() ? 302 : 403;
        return $this->config['status'] ?? $status;
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
     * @return boolean
     */
    public function getPermissionCallback()
    {
        $permission_callback = $this->config['permission_callback'] ?? false;
        return $permission_callback;
    }

    /**
     * Check if route has basic auth.
     *
     * @return boolean
     */
    public function hasBasicAuth()
    {
        $basic_auth = $this->config['basic_auth'] ?? [];
        return is_array($basic_auth) && !empty($basic_auth);
    }

    /**
     * Get basic auth.
     *
     * @return array
     */
    public function getBasicAuth()
    {
        $basic_auth = $this->config['basic_auth'] ?? [];
        return $basic_auth;
    }

    /**
     * Renders the html.
     *
     * @return boolean
     */
    public function isRender()
    {
        $render = $this->config['render'] ?? false;
        return filter_var(($render != false), FILTER_VALIDATE_BOOLEAN);
    }
}
