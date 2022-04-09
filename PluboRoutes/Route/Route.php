<?php
namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class Route implements RouteInterface
{

    /**
     * The name of the route.
     *
     * @var string
     */
   private $name;

    /**
     * The URL path that the route needs to match.
     *
     * @var string
     */
    private $path;

    /**
     * The template that the route wants to load or a callable.
     *
     * @var string\callable
     */
    private $template;

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
     * @param string $name
     * @param string $path
     * @param string|callable $template
     * @param array $config
     */
    public function __construct(string $name, string $path, $template, array $config=[]) {
      $this->name = $name;
      $this->path = $path;
      $this->template = $template;
      $this->config = $config;
      $this->args = array();
    }

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName() {
      return $this->name;
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
     * Get the action to be executed when this route is matched.
     *
     * @return string
     */
    public function getAction() {
      return "plubo/route_{$this->getName()}";
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
      return false;
    }

    /**
     * Check if the template is a callable.
     *
     * @return boolean
     */
    public function hasTemplateCallback() {
      return is_callable($this->template);
    }

    /**
     * Get the template to be loaded when this route is matched.
     *
     * @return string
     */
    public function getTemplate() {
      return $this->template;
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
