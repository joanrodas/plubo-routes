<?php
namespace PluboRoutes;

use PluboRoutes\Route\RouteInterface;
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
     * @var Route[]
     */
    private $routes;

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
        $this->route_variable = apply_filters('plubo/route_variable', 'route_name');
    }

    /**
     * Add a route to the router.
     *
     * @param RouteInterface  $route
     */
    public function add_route(RouteInterface $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Compiles the router into WordPress rewrite rules.
     */
    public function compile()
    {
        add_rewrite_tag('%'.$this->route_variable.'%', '(.+)');

        foreach ($this->routes as $route) {
            $this->add_rule($route);
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
        foreach ($this->routes as $key => $route) {
          if($route->getName() === $route_name) return $route;
        }

        return new \WP_Error('route_not_found');

    }

    /**
     * Adds a new WordPress rewrite rule for the given Route.
     *
     * @param RouteInterface  $route
     * @param string $position
     */
    private function add_rule(RouteInterface $route, $position = 'top') {
      $regex_path = ltrim( trim( $route->getPath() ), '/' );
      $index_string = 'index.php?' . $this->route_variable . '=' . $route->getName();
      if( preg_match_all('#\{(.+?)\}#', $regex_path, $matches) ) {
        $patterns = $matches[1];
        foreach ($patterns as $key => $pattern) {
          $pattern = explode(':', $pattern);
          if(count($pattern) < 2) continue;
          $name = $pattern[0];
          $num_arg = $key+1;
          $regex_code = RegexHelper::getRegex( $pattern[1] );
          $regex_path = str_replace($matches[0][$key], $regex_code, $regex_path);
          add_rewrite_tag("%$name%", $regex_code);
          $index_string .= "&$name=\$matches[$num_arg]";
          $route->addArg($name);
        }
      }
      add_rewrite_rule("^$regex_path$", $index_string, $position);
    }

}
