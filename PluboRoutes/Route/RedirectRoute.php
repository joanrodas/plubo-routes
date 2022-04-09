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
    public function __construct(string $path, $action, array $config=[]) {
      $this->path = $path;
      $this->action = $action;
      $this->config = $config;
      $this->args = array();
    }

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName() {
      return md5($this->path);
    }

    /**
     * Get the action to be called when this route is matched.
     *
     * @return string|callable
     */
    public function getAction() {
      return $this->action;
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function hasCallback() {
      return is_callable($this->action);
    }

    /**
     * Get the hook called when this route is matched.
     *
     * @return string
     */
    public function getHook() {
      return "plubo/route_{$this->getName()}";
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function isExternal() {
      return empty($this->config['external']) ? false : filter_var($this->config['external'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function getStatus() {
      $status = $this->config['status'] ?? 302;
      in_array((int)$status, range(300, 308), true) or $status = 302;
      return $status;
    }

}
