<?php
namespace PluboRoutes;

use PluboRoutes\Router;

use PluboRoutes\Route\Route;
use PluboRoutes\Route\RedirectRoute;
use PluboRoutes\Route\ActionRoute;
use PluboRoutes\Route\RouteInterface;

/**
 * The Processor is in charge of the interaction between the routing system and
 * the rest of WordPress.
 *
 * @author Joan Rodas <joan@sirvelia.com>
 *
 */
class PluboRoutesProcessor
{
    /**
     * The matched route found by the router.
     *
     * @var Route
     */
    private $matched_route;

    /**
     * The matched args found by the router.
     *
     * @var array
     */
    private $matched_args;

    /**
     * The router.
     *
     * @var Router
     */
    private $router;

    /**
     * Constructor.
     *
     * @param Router  $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Initialize processor with WordPress.
     *
     */
    public static function init()
    {
        $self = new self(new Router());
        add_action('init', array($self, 'addRoutes'));
        add_action('parse_request', array($self, 'matchRouteRequest'));
        add_action('template_redirect', array($self, 'doRouteActions'));
        add_action('template_include', array($self, 'includeRouteTemplate'));
    }

    /**
     * Step 1: Register all our routes into WordPress. Flush rewrite rules if the routes changed.
     */
    public function addRoutes()
    {
        $routes = apply_filters('plubo/routes', array());
        foreach ($routes as $route) {
            $this->router->addRoute($route);
        }
        $this->router->compile();
        $routes_hash = md5(serialize($routes));
        if ($routes_hash != get_option('plubo-routes-hash')) {
            flush_rewrite_rules();
            update_option('plubo-routes-hash', $routes_hash);
        }
    }

    /**
     * Step 2: Attempts to match the current request to an added route.
     *
     * @param WP $wp
     */
    public function matchRouteRequest(\WP $wp)
    {
        $found_route = $this->router->match($wp->query_vars);
        if ($found_route instanceof RouteInterface) {
            $found_args = array();
            $args_names = $found_route->getArgs();
            foreach ($args_names as $arg_name) {
                $found_args[$arg_name] = $wp->query_vars[$arg_name] ?? false;
            }
            $this->matched_route =  $found_route;
            $this->matched_args = $found_args;
        }
        if ($found_route instanceof \WP_Error &&
          in_array('route_not_found', $found_route->get_error_codes())) {
            wp_die($found_route, 'Route Not Found', array('response' => 404));
        }
    }

    /**
     * Step 3: If a route was found, execute the route's action. Or redirect if RedirectRoute.
     */
    public function doRouteActions()
    {
        if ($this->matched_route instanceof Route) {
            $this->executeRouteHook();
        } elseif ($this->matched_route instanceof ActionRoute) {
            $this->executeRouteFunction();
        } elseif ($this->matched_route instanceof RedirectRoute) {
            $this->executeRedirect();
        }
    }

    private function executeRouteHook()
    {
        status_header(200);
        do_action($this->matched_route->getAction(), $this->matched_args);
    }

    private function executeRouteFunction()
    {
        $action = $this->matched_route->getAction();
        if ($this->matched_route->hasCallback()) {
            $action = call_user_func($action, $this->matched_args);
        }
    }

    private function executeRedirect()
    {
        $redirect_to = $this->matched_route->getAction();
        if ($this->matched_route->hasCallback()) {
            $redirect_to = call_user_func($redirect_to, $this->matched_args);
        }
        nocache_headers();
        if ($this->matched_route->isExternal()) {
            wp_redirect($redirect_to, $this->matched_route->getStatus());
            exit;
        }
        wp_safe_redirect(home_url($redirect_to), $this->matched_route->getStatus());
        exit;
    }

    /**
     * Step 4: If a route of type Route was found, load the route template.
     *
     * @param string $template
     *
     * @return string
     */
    public function includeRouteTemplate($template)
    {
        if (!$this->matched_route instanceof Route) {
            return $template;
        }
        if ($this->matched_route->hasTemplateCallback()) {
            $template_func = $this->matched_route->getTemplate();
            $template = call_user_func($template_func, $this->matched_args);
        } else {
            $template = apply_filters('plubo/template', locate_template($this->matched_route->getTemplate()));
        }
        return $template;
    }
}
