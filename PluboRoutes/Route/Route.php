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
        $this->args = array();
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
}
