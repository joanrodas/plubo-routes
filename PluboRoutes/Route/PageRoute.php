<?php
namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class PageRoute implements RouteInterface
{
    use RouteTrait;

    /**
     * The page_id that the route wants to show.
     *
     * @var string\callable
     */
    private $page_id;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string|callable $action
     * @param array $config
     */
    public function __construct(string $path, int $page_id, array $config = [])
    {
        $this->path = $path;
        $this->page_id = $page_id;
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
     * Returns the page URI.
     *
     * @return string
     */
    public function getPageUri()
    {
        return get_page_uri($this->page_id);
    }

    /**
     * Returns the page ID.
     *
     * @return string
     */
    public function getPageId()
    {
        return $this->page_id;
    }
}
