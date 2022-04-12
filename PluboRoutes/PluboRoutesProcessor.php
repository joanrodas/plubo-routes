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
        add_action('init', [$self, 'addRoutes']);
        add_action('parse_request', [$self, 'matchRouteRequest']);
        add_action('rest_api_init', [$self, 'addEndpoints']);
        add_action('template_redirect', [$self, 'doRouteActions']);
        add_action('template_include', [$self, 'includeRouteTemplate']);
        add_filter('body_class', [$self, 'addBodyClasses']);
        add_filter('query_vars', [$self, 'addExtraVars']);
    }

    /**
     * Step 1: Register all our routes into WordPress. Flush rewrite rules if the routes changed.
     */
    public function addRoutes()
    {
        $routes = apply_filters('plubo/routes', []);
        foreach ($routes as $route) {
            $this->router->addRoute($route);
        }
        $this->router->compileRoutes();
        $this->maybeFlushRewriteRules($routes, 'plubo-routes-hash');
    }

    /**
     * Step 1 alt: Register all our endpoints into WordPress. Flush rewrite rules if the endpoints changed.
     */
    public function addEndpoints()
    {
        $endpoints = apply_filters('plubo/endpoints', []);
        foreach ($endpoints as $endpoint) {
            $this->router->addEndpoint($endpoint);
        }
        $this->router->compileEndpoints();
        $this->maybeFlushRewriteRules($endpoints, 'plubo-endpoints-hash');
    }

    /**
     * Flush if needed.
     */
    public function maybeFlushRewriteRules(array $values, string $option_name)
    {
        $hash = md5(serialize($values));
        if ($hash != get_option($option_name)) {
            flush_rewrite_rules();
            update_option($option_name, $hash);
        }
    }

    /**
     * Step 2: Attempts to match the current request to an added route.
     *
     * @param WP $env
     */
    public function matchRouteRequest(\WP $env)
    {
        $found_route = $this->router->match($env->query_vars);
        if ($found_route instanceof RouteInterface) {
            $found_args = [];
            $args_names = $found_route->getArgs();
            foreach ($args_names as $arg_name) {
                $found_args[$arg_name] = $env->query_vars[$arg_name] ?? false;
            }
            $this->matched_route =  $found_route;
            $this->matched_args = $found_args;
        }
        if ($found_route instanceof \WP_Error &&
          in_array('route_not_found', $found_route->get_error_codes())) {
            wp_die($found_route, 'Route Not Found', ['response' => 404]);
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
        if ($this->matched_route->isPrivate()) {
            $user = wp_get_current_user();
            $this->checkLoggedIn($user);
            $this->checkRoles($user);
            $this->checkCapabilities($user);
        }
        status_header(200);
        do_action($this->matched_route->getAction(), $this->matched_args);
    }

    private function checkLoggedIn($user)
    {
        if (!$user->exists()) {
            $this->forbidAccess();
        }
    }

    private function checkRoles($user)
    {
        $allowed_roles = $this->matched_route->getRoles();
        if ($this->matched_route->hasRolesCallback()) {
            $allowed_roles = call_user_func($allowed_roles, $this->matched_args);
        }
        if ($allowed_roles && !array_intersect((array)$user->roles, (array)$allowed_roles)) {
            $this->forbidAccess();
        }
    }

    private function checkCapabilities($user)
    {
        $allowed_caps = $this->getAllowedCapabilities();
        $is_allowed = $allowed_caps ? false : true;
        foreach ((array)$allowed_caps as $allowed_cap) {
            if ($user->has_cap($allowed_cap)) {
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            $this->forbidAccess();
        }
    }

    private function getAllowedCapabilities()
    {
        $allowed_caps = $this->matched_route->getCapabilities();
        if ($this->matched_route->hasCapabilitiesCallback()) {
            $allowed_caps = call_user_func($allowed_caps, $this->matched_args);
        }
        return $allowed_caps;
    }

    private function forbidAccess()
    {
        if ($this->matched_route->hasRedirect()) {
            wp_redirect($this->matched_route->getRedirect(), $this->matched_route->getStatus());
            exit;
        }
        status_header($this->matched_route->getStatus());
        exit;
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
            $template = locate_template(apply_filters('plubo/template', $this->matched_route->getTemplate()));
        }
        return $template;
    }

    /**
     * Filter: If a route was found, add name as body tag.
     */
    public function addBodyClasses($classes)
    {
        if ($this->matched_route instanceof Route) {
            $route_name = $this->matched_route->getName();
            $classes[] = "route-$route_name";
            foreach ($this->matched_args as $arg_name => $arg_value) {
              $classes[] = sanitize_title("$arg_name-$arg_value");
            }
            $classes = apply_filters('plubo/body_classes', $classes, $route_name, $this->matched_args);
        }
        return $classes;
    }

    /**
     * Filter: Add extra static query vars.
     */
    public function addExtraVars($query_vars)
    {
        if ($this->matched_route instanceof Route) {
            $route_extra_vars = $this->matched_route->getExtraVars();
            foreach ($route_extra_vars as $extra_var) {
                $query_vars[] = $extra_var;
            }
        }
        return $query_vars;
    }
}
