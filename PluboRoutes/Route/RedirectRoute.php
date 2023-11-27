<?php

namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class RedirectRoute implements RouteInterface
{
    use RouteTrait;

    /**
     * The action that the route wants to execute.
     *
     * @var string\callable
     */
    private $action;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string|callable $action
     * @param array $config
     */
    public function __construct(string $path, $action, array $config = [])
    {
        $this->path = $path;
        $this->action = $action;
        $this->config = $config;
        $this->args = [];
    }

    /**
     * Get the action to be called when this route is matched.
     *
     * @return string|callable
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function hasCallback()
    {
        return is_callable($this->action);
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function isExternal()
    {
        $is_external = $this->config['external'] ?? false;
        return filter_var($is_external, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the status.
     *
     * @return int
     */
    public function getStatus()
    {
        $status = $this->config['status'] ?? 301;
        in_array((int)$status, range(300, 308), true) or $status = 301;
        return $status;
    }
}
