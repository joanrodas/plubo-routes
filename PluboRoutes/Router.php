<?php
namespace PluboRoutes;

use PluboRoutes\Route;

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
     * @param Route  $route
     */
    public function add_route(Route $route)
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
     * @return Route|WP_Error
     */
    public function match(array $query_variables)
    {
        if (empty($query_variables[$this->route_variable])) {
            return new \WP_Error('missing_route_variable');
        }

        $route_name = $query_variables[$this->route_variable];
        $key = array_search($route_name, array_column($this->routes, 0));

        if ($key === FALSE) {
          // die( print_r($this->routes[$key], true) );
          return $this->routes[$key];
        }

        return new \WP_Error('route_not_found');

    }

    /**
     * Adds a new WordPress rewrite rule for the given Route.
     *
     * @param Route  $route
     * @param string $position
     */
    private function add_rule(Route $route, $position = 'top')
    {
        $regex_path = ltrim( trim($route->get_path() ), '/' );
        $index_string = 'index.php?'.$this->route_variable.'='.$route->get_name();

        preg_match_all('#\{(.+?)\}#', $regex_path, $matches);

        if( isset($matches[1]) ) {
          $patterns = $matches[1];
          foreach ($patterns as $key => $pattern) {
            $pattern_array = explode(':', $pattern);
            if( count($pattern_array) === 2) {
              $name = $pattern_array[0];
              $type = $pattern_array[1];
              $match_num = $key+1;

              switch ($type) {
                case 'number':
                  $regex_path = str_replace($matches[0][$key], "([0-9]+)", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '([0-9]+)');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                case 'word':
                  $regex_path = str_replace($matches[0][$key], "([a-zA-Z]+)", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '([a-zA-Z]+)');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                case 'date':
                  $regex_path = str_replace($matches[0][$key], "([a-z0-9-]+)", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                case 'slug':
                  $regex_path = str_replace($matches[0][$key], "([a-z0-9-]+)", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '([a-z0-9-]+)');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                case: 'digit':
                  $regex_path = str_replace($matches[0][$key], "([0-9])", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '([0-9])');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                case: 'jwt':
                  $regex_path = str_replace($matches[0][$key], "((?:[\w-]*\.){2}[\w-]*)", $regex_path);
                  add_rewrite_tag('%'.$name.'%', '((?:[\w-]*\.){2}[\w-]*)');
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;

                default: //Allow custom regex
                  $regex_path = str_replace($matches[0][$key], $type, $regex_path);
                  add_rewrite_tag('%'.$name.'%', $type);
                  $index_string .= "&$name=\$matches[$match_num]";
                  break;
              }
            }
          }
        }

        $regex_path = '^'.$regex_path.'$';
        add_rewrite_rule($regex_path, $index_string, $position);
    }


}
