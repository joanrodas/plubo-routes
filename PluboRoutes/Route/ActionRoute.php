<?php
namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class ActionRoute implements RouteInterface
{

    /**
     * The URL path that the route needs to match.
     *
     * @var string
     */
    private $path;

    /**
     * The action that the route wants to execute.
     *
     * @var string\callable
     */
    private $action;

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
     * Get the path to be matched.
     *
     * @return string
     */
    public function getPath() {
      return $this->path;
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
     * Get the config parameters of the route.
     *
     * @return array
     */
    public function getConfig() {
      return $this->config;
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
     * Set the matches of the route.
     *
     * @param array
     */
    public function setArgs($args) {
      $this->args = $args;
    }

    /**
     * Get the matches of the route.
     *
     * @return array
     */
    public function getArgs() {
      return $this->args;
    }

}
