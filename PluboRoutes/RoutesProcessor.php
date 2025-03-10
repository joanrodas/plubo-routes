<?php

namespace PluboRoutes;

use PluboRoutes\Route\Route;
use PluboRoutes\Route\RedirectRoute;
use PluboRoutes\Route\ActionRoute;
use PluboRoutes\Route\RouteInterface;
use PluboRoutes\Middleware\MiddlewareInterface;
/**
 * The Processor is in charge of the interaction between the routing system and
 * the rest of WordPress.
 *
 * @author Joan Rodas <joan@sirvelia.com>
 *
 */
class RoutesProcessor
{
    /**
     * The matched route found by the router.
     *
     * @var Route|RedirectRote|ActionRoute|null
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
     * The router instance.
     *
     * @var RoutesProcessor|null
     */
    private static $instance = NULL;

    /**
     * Constructor.
     *
     * @param Router  $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->initHooks();
    }

    /**
     * Initialize hooks with WordPress.
     */
    private function initHooks()
    {
        add_action('init', [$this, 'addRoutes']);
        add_action('parse_request', [$this, 'matchRouteRequest']);
        add_action('send_headers', [$this, 'basicAuth']);
        add_action('rest_api_init', [$this, 'addEndpoints']);
        add_action('template_redirect', [$this, 'doRouteActions']);
        add_filter('template_include', [$this, 'includeRouteTemplate']);
        add_filter('body_class', [$this, 'addBodyClasses']);
        add_filter('document_title_parts', [$this, 'modifyTitle']);
    }

    /**
     * Clone not allowed.
     *
     */
    private function __clone() {}

    /**
     * Initialize processor with WordPress.
     *
     */
    public static function init($plugin_dir_path = '', $namespace = '')
    {
        if (self::$instance === null) {
            self::$instance = new self(new Router());
        }

        if (!empty($plugin_dir_path) && !empty($namespace)) {
            $dynamic_routes = self::$instance->getDynamicRoutes($plugin_dir_path, $namespace);
            
            foreach($dynamic_routes as $dynamic_route) {
                self::$instance->router->addRoute($dynamic_route);
            }

            self::$instance->maybeFlushRewriteRules($dynamic_routes, 'plubo-dynamic-' . strtolower($namespace));
        }
        
        // Custom action for router initialization
        do_action('plubo/router_init');
        return self::$instance;
    }

    public function getDynamicRoutes($plugin_dir_path = '', $namespace = '')
    {
        global $wp_filesystem;

        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();

        $routes_json_path = $plugin_dir_path . 'dist/routes.json';

        $routes_content = $wp_filesystem->get_contents($routes_json_path);
        $routes_info = json_decode($routes_content, true);

        foreach ($routes_info as $route) {
            $route_path = $route['route'];
            unset($route['route']);

            $template_name = str_replace('/', '.', $route_path);
            $controller = function ($matches) use ($wp_filesystem, $template_name, $route, $plugin_dir_path) {

                $file_path = $plugin_dir_path . $route['path'];
                $html = $wp_filesystem->get_contents($file_path);
                $html = preg_replace('/@configRoute\s*\(\s*(\{[\s\S]*?\})\s*\)/s', '', $html);

                return apply_filters('plubo/get_route_html', $html, $template_name, $matches);
            };

            // Parse controller
            $route_controller = ($route['controller'] ?? false) ? str_replace('.', '::', $route['controller']) : false;
            if ($route_controller && is_callable("\\$namespace\\Controllers\\$route_controller")) {
                $controller = "\\$namespace\\Controllers\\$route_controller";
            }

            // Parse roles callback
            if (isset($route['allowed_roles']) && is_string($route['allowed_roles']) && str_contains($route['allowed_roles'], '.')) {
                $roles_controller = str_replace('.', '::', $route['allowed_roles']);
                if (is_callable($roles_controller)) {
                    $route['allowed_roles'] = "\\$namespace\\Controllers\\$roles_controller";
                }
            }

            // Parse capabilities callback
            if (isset($route['allowed_capabilities']) && is_string($route['allowed_capabilities']) && str_contains($route['allowed_capabilities'], '.')) {
                $capabilities_controller = str_replace('.', '::', $route['allowed_capabilities']);
                if (is_callable($capabilities_controller)) {
                    $route['allowed_capabilities'] = "\\$namespace\\Controllers\\$capabilities_controller";
                }
            }

            $routes[] = new Route($route_path, $controller, wp_parse_args($route, ['render' => true]));
        }

        return $routes;
    }

    /**
     * Step 1: Register all our routes into WordPress. Flush rewrite rules if the routes changed.
     */
    public function addRoutes()
    {
        $routes = apply_filters('plubo/routes', []);
        $routes = is_array($routes) ? $routes : [];

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
        $endpoints = is_array($endpoints) ? $endpoints : [];

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
        if (!is_array($values)) {
            return;
        }

        $hash = md5(serialize($values));
        if ($hash != get_option($option_name)) {
            flush_rewrite_rules();
            update_option($option_name, $hash);
        }
    }

    /**
     * Step 2: Attempts to match the current request to an added route.
     *
     * @param \WP $env
     */
    public function matchRouteRequest(\WP $env)
    {
        // Ensure the language is set in query variables if Polylang is active
        if (function_exists('pll_current_language') && !isset($env->query_vars['lang'])) {
            $env->query_vars['lang'] = \pll_current_language();
        }

        $found_route = $this->router->match($env->query_vars);

        if ($found_route instanceof RouteInterface) {
            $this->processMatchedRoute($found_route, $env);
        }

        if ($found_route instanceof \WP_Error && in_array('route_not_found', $found_route->get_error_codes())) {
            $this->handleRouteNotFound();
        }
    }

    /**
     * Process matched route and set matched route and args.
     *
     * @param RouteInterface $found_route
     * @param \WP $env
     */
    private function processMatchedRoute(RouteInterface $found_route, \WP $env)
    {
        $this->matched_route = $found_route;
        $this->matched_args = $this->extractArgs($found_route, $env);

        // Action hook after matching route request
        do_action('plubo/after_matching_route_request', $this->matched_route, $this->matched_args, $env);
    }


    /**
     * Handle route not found error.
     */
    private function extractArgs(RouteInterface $route, \WP $env)
    {
        $args = [];
        $extra_args = $route->getExtraVars();

        foreach ($route->getArgs() as $arg_name) {
            $query_value = $env->query_vars[$arg_name] ?? ($extra_args[$arg_name] ?? false);
            $args[$arg_name] = $query_value;
        }

        return $args;
    }

    /**
     * Handle route not found error.
     */
    private function handleRouteNotFound()
    {
        wp_die(esc_html('Route Not Found'), esc_html('Route Not Found'), ['response' => 404]);
    }

    /**
     * Step 3: Check if route has basic Auth enabled.
     */
    public function basicAuth()
    {
        if ($this->matched_route instanceof Route && $this->matched_route->hasBasicAuth()) {
            $this->checkBasicAuth();
        }
    }

    /**
     * Check basic authentication for the matched route.
     */
    private function checkBasicAuth()
    {
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $basic_auth = $this->matched_route->getBasicAuth();
        $auth_user = isset($_SERVER['PHP_AUTH_USER']) ? wp_unslash(sanitize_text_field($_SERVER['PHP_AUTH_USER'])) : '';
        $auth_pass = isset($_SERVER['PHP_AUTH_PW']) ? wp_unslash(sanitize_text_field($_SERVER['PHP_AUTH_PW'])) : '';

        if (
            empty($auth_user) || empty($auth_pass)
            || !array_key_exists($auth_user, $basic_auth)
            || $auth_pass != $basic_auth[$auth_user]
        ) {
            $this->unauthorized();
        }
    }

    /**
     * Handle unauthorized access.
     */
    private function unauthorized()
    {
        wp_die(esc_html('Unauthorized Access'), esc_html('Unauthorized Access'), ['response' => 401]);
    }

    /**
     * Step 4: If a route was found, execute the route's action. Or redirect if RedirectRoute.
     */
    public function doRouteActions()
    {
        if ($this->matched_route instanceof Route || $this->matched_route instanceof ActionRoute || $this->matched_route instanceof RedirectRoute) {
            if ($this->matched_route->getMiddlewareStack()) {
                $this->runMiddlewareStack($this->matched_route);
            } else {
                $this->executeRouteActions();
            }            
        }
    }
    
    /**
     * Execute middleware before route actions.
     */
    private function runMiddlewareStack(RouteInterface $route)
    {
        $middlewareStack = $route->getMiddlewareStack();
        $index = 0;
        
        $runNext = function () use (&$index, $middlewareStack, $route, &$runNext) {
            if ($index < count($middlewareStack)) {
                $middleware = $middlewareStack[$index];
                $index++;

                return is_object($middleware) && $middleware instanceof MiddlewareInterface
                ? $middleware->handle($this->matched_args, $runNext)
                : $middleware($this->matched_args, $runNext); // For function-based middleware
            } else {
                $this->executeRouteActions(); // All middleware passed, execute route
            }
        };
        
        $runNext();
    }
    
    /**
     * Execute actions based on the type of matched route.
     */
    private function executeRouteActions()
    {
        // Action hook before executing route actions
        do_action('plubo/before_executing_route_actions', $this->matched_route, $this->matched_args);
        
        $permission_checker = new PermissionChecker($this->matched_route, $this->matched_args);
        $permission_checker->checkPermissions();

        if ($this->matched_route instanceof Route) {
            $this->executeRouteHook();
        } elseif ($this->matched_route instanceof ActionRoute) {
            $this->executeRouteFunction();
        } elseif ($this->matched_route instanceof RedirectRoute) {
            $this->executeRedirect();
        }
    }

    /**
     * Execute route hook action.
     */
    private function executeRouteHook()
    {
        status_header($this->matched_route->getStatus());
        do_action($this->matched_route->getAction(), $this->matched_args);
    }

    /**
     * Execute route function action.
     */
    private function executeRouteFunction()
    {
        status_header($this->matched_route->getStatus());
        $action = $this->matched_route->getAction();
        if ($this->matched_route->hasCallback()) {
            $action = $action($this->matched_args);
        }
    }

    /**
     * Execute redirect action.
     */
    private function executeRedirect()
    {
        if (!$this->matched_route instanceof RedirectRoute) {
            exit;
        }

        $redirect_to = $this->matched_route->getAction();
        if ($this->matched_route->hasCallback()) {
            $redirect_to = $redirect_to($this->matched_args);
        }
        nocache_headers();
        if ($this->matched_route->isExternal()) {
            wp_redirect(esc_url_raw($redirect_to), $this->matched_route->getStatus());
            exit;
        }
        wp_safe_redirect(home_url($redirect_to), $this->matched_route->getStatus());
        exit;
    }

    /**
     * Step 5: If a route of type Route was found, load the route template.
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

        $matched_template = $this->matched_route->getTemplate();
        if ($this->matched_route->isRender()) {
            $template = $this->createTempFile(sys_get_temp_dir(), $this->matched_route->getName(), '.blade.php');
            if ($this->matched_route->hasTemplateCallback()) {
                $matched_template = $matched_template($this->matched_args);
            }
            file_put_contents($template, $matched_template);
            return $template;
        }
        if ($this->matched_route->hasTemplateCallback()) {
            $template_func = $this->matched_route->getTemplate();
            return $template_func($this->matched_args);
        }

        return $matched_template;
    }

    /**
     * Create a temporary file.
     *
     * @param string $dir
     * @param string $prefix
     * @param string $postfix
     *
     * @return string|false
     */
    private function createTempFile(string $dir, string $prefix, string $postfix)
    {
        // Trim trailing slashes from $dir.
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        // If we don't have permission to create a directory, fail, otherwise we will be stuck in an endless loop.
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        // Make sure characters in prefix and postfix are safe.
        if ((strpbrk($prefix, '\\/:*?"<>|') !== false) || (strpbrk($postfix, '\\/:*?"<>|') !== false)) {
            return false;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $prefix . $postfix;
        $tmp_file = @fopen($path, 'x+');

        if ($tmp_file) {
            fclose($tmp_file);
        }

        return $path;
    }

    /**
     * Filter: If a route was found, add name as body tag.
     *
     * @param array $classes
     *
     * @return array
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
     * Filter: If a route was found, modify the title.
     *
     * @param array $title_parts
     *
     * @return array
     */
    public function modifyTitle($title_parts)
    {
        if ($this->matched_route instanceof Route) {
            $route_title = $this->matched_route->getTitle();
            $route_title = is_callable($route_title) ? $route_title($this->matched_args) : $route_title;
            $title_parts['title'] = $route_title ?? get_bloginfo('name');
            $title_parts = apply_filters('plubo/title_parts', $title_parts, $route_title, $this->matched_args);
        }

        return $title_parts;
    }
}
