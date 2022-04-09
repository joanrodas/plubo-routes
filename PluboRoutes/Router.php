<?php
namespace PluboRoutes;

use PluboRoutes\Route\RouteInterface;
use PluboRoutes\Route\Route;
use PluboRoutes\Route\RedirectRoute;
use PluboRoutes\Route\ActionRoute;

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
        $regex_path = $this->clean_path( $route->getPath() );
        $index_string = 'index.php?'.$this->route_variable.'='.$route->getName();
        $route_args = array();

        preg_match_all('#\{(.+?)\}#', $regex_path, $matches);

        if( isset($matches[1]) ) {
          $patterns = $matches[1];
          foreach ($patterns as $key => $pattern) {
            $pattern_array = explode(':', $pattern);
            if( count($pattern_array) >= 2) {
              $name = $pattern_array[0];
              $type = $pattern_array[1];
              $match_num = $key+1;

              $regex_code = $this->get_regex_by_type($type);

              $regex_path = str_replace($matches[0][$key], $regex_code, $regex_path);
              add_rewrite_tag('%'.$name.'%', $regex_code);
              $index_string .= "&$name=\$matches[$match_num]";
              $route_args[] = $name;
            }
          }
        }

        $regex_path = '^'.$regex_path.'$';
        add_rewrite_rule($regex_path, $index_string, $position);
        $route->setArgs($route_args);
    }

    private function clean_path($path) {
      return ltrim( trim($path), '/' );
    }

    private function get_regex_by_type($type) {

      switch ($type) {
        case 'number':
          $type = '([0-9]+)';
          break;
        case 'word':
          $type = '([a-zA-Z]+)';
          break;
        case 'date':
          $type = '(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))';
          break;
        case 'slug':
          $type = '([a-z0-9-]+)';
          break;
        case 'digit':
          $type = '([0-9])';
          break;
        case 'year':
          $type = '(\d{4})';
          break;
        case 'month':
          $type = '(0[1-9]|1[0-2])';
          break;
        case 'day':
          $type = '(0[1-9]|[12][0-9]|3[01])';
          break;
        case 'jwt':
          $type = '((?:[\w-]*\.){2}[\w-]*)';
          break;
        case 'ip':
          $type = '(([0-9]{1,3}\.){3}[0-9]{1,3})';
          break;
        default: //Allow custom regex
          break;
      }
      return $type;
    }

}
