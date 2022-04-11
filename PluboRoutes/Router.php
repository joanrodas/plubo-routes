<?php
namespace PluboRoutes;

use PluboRoutes\Route\RouteInterface;
use PluboRoutes\Endpoint\EndpointInterface;
use PluboRoutes\Helpers\RegexHelper;

/**
 * The Router manages routes using the WordPress rewrite API.
 *
 */
class Router
{
    /**
     * All registered routes.
     *
     * @var RouteInterface[]
     */
    private $routes;

    /**
     * All registered endpoints.
     *
     * @var EndpointInterface[]
     */
    private $endpoints;

    /**
     * Query variable used to identify routes.
     *
     * @var string
     */
    private $route_variable;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->routes = array();
        $this->endpoints = array();
        $this->route_variable = apply_filters('plubo/route_variable', 'route_name');
    }

    /**
     * Add a route to the router.
     *
     * @param RouteInterface  $route
     */
    public function addRoute(RouteInterface $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Add an endpoint to the router.
     *
     * @param EndpointInterface  $route
     */
    public function addEndpoint(EndpointInterface $endpoint)
    {
        $this->endpoints[] = $endpoint;
    }

    /**
     * Compiles the router into WordPress rewrite rules.
     */
    public function compileRoutes()
    {
        add_rewrite_tag('%'.$this->route_variable.'%', '(.+)');
        foreach ($this->routes as $route) {
            $this->addRule($route);
        }
    }

    /**
     * Compiles the router into WordPress endpoints.
     */
    public function compileEndpoints()
    {
        foreach ($this->endpoints as $endpoint) {
            $path = $this->getEndpointPath($endpoint->getPath());
            register_rest_route($endpoint->getNamespace(), $path, array(
              'methods' => $endpoint->getMethod(),
              'callback' => $endpoint->getConfig(),
              'permission_callback' => $endpoint->getPermissionCallback()
            ));
        }
    }

    /**
     * Tries to find a matching route using the given query variables. Returns the matching route
     * or a WP_Error.
     *
     * @param array $query_variables
     *
     * @return RouteInterface|WP_Error
     */
    public function match(array $query_variables)
    {
        if (empty($query_variables[$this->route_variable])) {
            return new \WP_Error('missing_route_variable');
        }
        $route_name = $query_variables[$this->route_variable];
        foreach ($this->routes as $route) {
            if ($route->getName() === $route_name) {
                return $route;
            }
        }
        return new \WP_Error('route_not_found');
    }

    /**
     * Adds a new WordPress rewrite rule for the given Route.
     *
     * @param RouteInterface  $route
     * @param string $position
     */
    private function addRule(RouteInterface $route, $position = 'top')
    {
        $regex_path = RegexHelper::cleanPath($route->getPath());
        $matches = RegexHelper::getRegexMatches($regex_path);
        $index_string = 'index.php?' . $this->route_variable . '=' . $route->getName();
        if ($matches) {
            foreach ($matches[1] as $key => $pattern) {
                $pattern = explode(':', $pattern);
                if (count($pattern) > 1) {
                    $name = $pattern[0];
                    $num_arg = $key+1;
                    $regex_code = RegexHelper::getRegex($pattern[1]);
                    $regex_path = str_replace($matches[0][$key], $regex_code, $regex_path);
                    add_rewrite_tag("%$name%", $regex_code);
                    $index_string .= "&$name=\$matches[$num_arg]";
                    $route->addArg($name);
                }
            }
        }
        add_rewrite_rule("^$regex_path$", $index_string, $position);
    }

    /**
     * Get translated Regex path for an endpoint route.
     *
     * @param string $path
     */
    private function getEndpointPath(string $path)
    {
        $regex_path = RegexHelper::cleanPath($path);
        $matches = RegexHelper::getRegexMatches($regex_path);
        if ($matches) {
            foreach ($matches[1] as $key => $pattern) {
                $pattern = explode(':', $pattern);
                if (count($pattern) > 1) {
                    $regex_code = RegexHelper::getRegex($pattern[1]);
                    $regex_code = "(?P<$pattern[0]>$regex_code)";
                    $regex_path = str_replace($matches[0][$key], $regex_code, $regex_path);
                }
            }
        }
        return $regex_path;
    }
}
