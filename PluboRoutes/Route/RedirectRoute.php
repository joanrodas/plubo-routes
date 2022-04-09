<?php
namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class RedirectRoute implements RouteInterface, \Serializable
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
     * @param string\callable $action
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

    public function serialize() {
      return serialize( array($this->path, $this->args) );
    }

    public function unserialize($data) {
      $data = unserialize($data);
      $this->path = $data['path'];
      $this->args = $data['args'];
    }

}
