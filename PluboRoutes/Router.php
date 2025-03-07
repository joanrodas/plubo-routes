<?php

namespace PluboRoutes;

use PluboRoutes\Route\RouteInterface;
use PluboRoutes\Route\Route;
use PluboRoutes\Route\PageRoute;
use PluboRoutes\Endpoint\EndpointInterface;
use PluboRoutes\Helpers\RegexHelperRoutes;
use PluboRoutes\Helpers\RegexHelperEndpoints;
use PluboRoutes\Middleware\MiddlewareInterface;

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
     * Regex Helper for routes.
     *
     * @var RegexHelperRoutes
     */
    private $regex_routes;

    /**
     * Regex Helper for Endpoints.
     *
     * @var RegexHelperEndpoints
     */
    private $regex_endpoints;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->routes = [];
        $this->endpoints = [];
        $this->route_variable = apply_filters('plubo/route_variable', 'route_name');
        $this->regex_routes = new RegexHelperRoutes();
        $this->regex_endpoints = new RegexHelperEndpoints();
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
        add_rewrite_tag('%' . $this->route_variable . '%', '(.+)');

        $rules = [];

        foreach ($this->routes as $route) {
            if ($route instanceof PageRoute) {
                $this->addPageRule($route);
                continue;
            }

            $rules = array_merge($rules, $this->addRule($route));
        }

        // Apply custom filter for Polylang
        $rules = apply_filters('plubo_routes_rewrite_rules', $rules);

        // Add the rules to Polylang's filter
        add_filter('pll_rewrite_rules', function ($pll_rules) use ($rules) {
            return array_merge($pll_rules, $rules);
        });
    }

    /**
     * Compiles the router into WordPress endpoints.
     */
    public function compileEndpoints()
    {
        foreach ($this->endpoints as $endpoint) {
            $path = $this->getEndpointPath($endpoint->getPath());
            $namespace = $endpoint->getNamespace();
            $method = $endpoint->getMethod();

            // Wrap the main callback with middleware processing
            $callback = function ($request) use ($endpoint) {
                return $this->runEndpointMiddlewareStack($endpoint, $request);
            };

            register_rest_route($namespace, $path, [
                'methods' => $method,
                'callback' => $callback,
                'permission_callback' => $endpoint->getPermissionCallback()
            ]);
        }
    }

    /**
     * Run the middleware stack for the endpoint and then call the endpoint's config callback.
     *
     * @param EndpointInterface $endpoint
     * @param \WP_REST_Request $request
     * @return mixed
     */
    private function runEndpointMiddlewareStack(EndpointInterface $endpoint, \WP_REST_Request $request)
    {
        $middlewareStack = $endpoint->getMiddlewareStack();
        $index = 0;

        $runNext = function () use (&$index, $middlewareStack, $endpoint, $request, &$runNext) {
            if ($index < count($middlewareStack)) {
                $middleware = $middlewareStack[$index];
                $index++;

                return is_object($middleware) && $middleware instanceof MiddlewareInterface
                ? $middleware->handle($request, $runNext)
                : $middleware($request, $runNext); // For function-based middleware


            } else {
                // Call the main callback if all middleware passed
                $configCallback = $endpoint->getConfig();
                $response = $configCallback($request);
                return $response instanceof \WP_REST_Response ? $response : new \WP_REST_Response($response);
            }
        };

        return $runNext();
    }

    /**
     * Tries to find a matching route using the given query variables. Returns the matching route
     * or a WP_Error.
     *
     * @param array $query_variables
     *
     * @return RouteInterface|\WP_Error
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
        $regex_path = $this->regex_routes->cleanPath($route->getPath());
        $matches = $this->regex_routes->getRegexMatches($regex_path);
        $index_string = 'index.php?' . $this->route_variable . '=' . $route->getName();
        $rules = [];

        if (!$matches) {
            return $rules;
        }


        // Add language param if Polylang is active
        if (function_exists('pll_current_language')) {
            $languages = \pll_languages_list();
            $language_regex = implode('|', $languages);
            // Use non-capturing group for the slash and capture only the language code
            $regex_path = "(?:($language_regex)/)?" . $regex_path;
            $index_string .= "&lang=\$matches[1]";
        }

        foreach ($matches[1] as $key => $pattern) {
            $pattern = explode(':', $pattern);
            if (count($pattern) > 1) {
                $name = $pattern[0];
                $num_arg = function_exists('pll_current_language') ? $key + 2 : $key + 1;
                $regex_code = $this->regex_routes->getRegex($pattern[1]);
                $regex_path = str_replace($matches[0][$key], $regex_code, $regex_path);
                add_rewrite_tag("%$name%", $regex_code);
                $index_string .= "&$name=\$matches[$num_arg]";
                $route->addArg($name);
            }
        }
        if ($route instanceof Route) {
            $index_string = $this->addExtraVars($route, $index_string);
        }

        add_rewrite_rule("^$regex_path$", $index_string, $position);

        $rules["^$regex_path$"] = $index_string;
        return $rules;
    }

    /**
     * Adds a new WordPress rewrite rule for the given PageRoute.
     *
     * @param PageRoute  $route
     * @param string $position
     */
    private function addPageRule(PageRoute $route, $position = 'top')
    {
        $index_string = 'index.php?pagename=' . $route->getPageUri();
        $page_path = $route->getPath();

        add_rewrite_rule("^$page_path$", $index_string, $position);
        add_filter('page_link', function ($link, $post_id) use ($route) {
            if ($post_id === $route->getPageId()) {
                $link = home_url($route->getPath());
            }
            return $link;
        }, 10, 2);
    }

    /**
     * Get translated Regex path for an endpoint route.
     *
     * @param string $path
     *
     * @return string
     */
    private function getEndpointPath(string $path)
    {
        $regex_path = $this->regex_endpoints->cleanPath($path);
        $matches = $this->regex_endpoints->getRegexMatches($regex_path);

        if ($matches) {
            foreach ($matches[1] as $key => $pattern) {
                $regex_path = $this->getEndpointPatternPath($regex_path, $key, $pattern, $matches);
            }
        }

        return $regex_path;
    }

    /**
     * Get translated Regex path for an endpoint pattern.
     *
     * @param string $path
     * @param int $key
     * @param string $pattern
     * @param array $matches
     *
     * @return string
     */
    private function getEndpointPatternPath(string $path, int $key, string $pattern, array $matches)
    {
        $pattern = explode(':', $pattern);

        if (count($pattern) > 1) {
            $regex_code = $this->regex_endpoints->getRegex($pattern);
            $path = str_replace($matches[0][$key], $regex_code, $path);
        }

        return $path;
    }

    /**
     * Add extra query vars.
     *
     * @param Route $route
     * @param string $index_string
     *
     * @return string
     */
    private function addExtraVars(Route $route, string $index_string)
    {
        $extra_vars = $route->getExtraVars();

        foreach ($extra_vars as $var_name => $var_value) {
            $index_string .= "&$var_name=$var_value";
            $route->addArg($var_name);
            add_rewrite_tag("%$var_name%", '([a-z0-9-]+)');
        }

        return $index_string;
    }
}
