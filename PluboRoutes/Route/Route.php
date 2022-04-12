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
     * The name of the route.
     *
     * @var string
    */
    private $name;

    /**
     * The template that the route wants to load or a callable.
     *
     * @var string\callable
     */
    private $template;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $path
     * @param string|callable $template
     * @param array $config
     */
    public function __construct(string $name, string $path, $template, array $config = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->template = $template;
        $this->config = $config;
        $this->args = [];
    }

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Check if the route is a private route (logged users only).
     *
     * @return boolean
     */
    public function isPrivate()
    {
        $is_private = $this->config['private'] ?? false;
        return filter_var($is_private, FILTER_VALIDATE_BOOLEAN);
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
        $status = $this->isPrivate() ? $status : 200;
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
        $roles = $this->config['allowed_roles'] ?? [];
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
        $capabilities = $this->config['allowed_caps'] ?? [];
        return $capabilities;
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
}
