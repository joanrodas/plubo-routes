<?php
namespace PluboRoutes;

/**
 * A Route describes a route and its parameters.
 *
 */
class Route
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
     * The template that the route wants to load.
     *
     * @var string
     */
    private $template;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $path
     * @param string $template
     */
    public function __construct($name, $path, $template = '')
    {
        $this->name = $name;
        $this->path = $path;
        $this->template = $template;
    }

    /**
     * Get the hook called when this route is matched.
     *
     * @return string
     */
    public function get_hook()
    {
        return "plubo/route_{$this->get_name()}";
    }

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Get the URL path that the route needs to match.
     *
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * Get the template that the route wants to load.
     *
     * @return string
     */
    public function get_template()
    {
        return $this->template;
    }

}
