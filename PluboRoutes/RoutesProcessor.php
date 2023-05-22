<?php
namespace PluboRoutes;

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
class RoutesProcessor
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
     * The router instance.
     *
     * @var Router
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
        add_action('init', [$this, 'addRoutes']);
        add_action('parse_request', [$this, 'matchRouteRequest']);
        add_action('send_headers', [$this, 'basicAuth']);
        add_action('rest_api_init', [$this, 'addEndpoints']);
        add_action('template_redirect', [$this, 'doRouteActions']);
        add_action('template_include', [$this, 'includeRouteTemplate']);
        add_filter('body_class', [$this, 'addBodyClasses']);
        add_filter('document_title_parts', [$this, 'modifyTitle']);
    }

    /**
     * Clone not allowed.
     *
     */
	private function __clone()
	{
	}    

    /**
     * Initialize processor with WordPress.
     *
     */
    public static function init()
    {
        if (is_null(self::$instance)) {
			self::$instance = new self(new Router());
		}
		return self::$instance;        
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
            $extra_args = $found_route->getExtraVars();
            foreach ($args_names as $arg_name) {
                $query_value = $env->query_vars[$arg_name] ?? ($extra_args[$arg_name] ?? false);
                $found_args[$arg_name] = $query_value;
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
     * Step 3: Check if route has basic Auth enabled.
     */
    public function basicAuth()
    {
        if ($this->matched_route instanceof Route) {
            if (!$this->matched_route->hasBasicAuth()) {
                return;
            }
            $this->checkBasicAuth();
        }
    }

    private function checkBasicAuth()
    {
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $basic_auth = $this->matched_route->getBasicAuth();
        $auth_user = $_SERVER['PHP_AUTH_USER'] ?? '';
        $auth_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
        if (empty($auth_user) || empty($auth_pass)) {
            $this->unauthorized();
        }
        if (!array_key_exists($auth_user, $basic_auth)) {
            $this->unauthorized();
        }
        if ($auth_pass != $basic_auth[$auth_user]) {
            $this->unauthorized();
        }
    }

    private function unauthorized()
    {
        header('HTTP/1.1 401 Authorization Required');
        header('WWW-Authenticate: Basic realm="Access denied"');
        exit;
    }

    /**
     * Step 4: If a route was found, execute the route's action. Or redirect if RedirectRoute.
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
        $this->checkPermissionCallback();
        $user = wp_get_current_user();
        if ($this->checkLoggedIn($user)) {
            $this->checkRoles($user);
            $this->checkCapabilities($user);
        }
        status_header(200);
        do_action($this->matched_route->getAction(), $this->matched_args);
    }

    private function checkPermissionCallback()
    {
        $permission_callback = $this->matched_route->getPermissionCallback();
        if (!$permission_callback || !is_callable($permission_callback)) {
            return;
        }
        $has_access = call_user_func($permission_callback, $this->matched_args);
        if (!$has_access) {
            $this->forbidAccess();
        }
    }

    private function checkLoggedIn($user)
    {
        $is_logged_in = $user->exists();
        if (!$this->matched_route->guestHasAccess() && !$is_logged_in
          || !$this->matched_route->memberHasAccess() && $is_logged_in) {
            $this->forbidAccess();
        }
        return $is_logged_in;
    }

    private function checkRoles($user)
    {
        $allowed_roles = $this->matched_route->getRoles();
        if ($this->matched_route->hasRolesCallback()) {
            $allowed_roles = call_user_func($allowed_roles, $this->matched_args);
        }
        if ($allowed_roles !== false && !array_intersect((array)$user->roles, (array)$allowed_roles)) {
            $this->forbidAccess();
        }
    }

    private function checkCapabilities($user)
    {
        $allowed_caps = $this->getAllowedCapabilities();
        if($allowed_caps === false) {
            return;
        }
        $is_allowed = false;
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
                $content = call_user_func($matched_template, $this->matched_args);
                file_put_contents($template, $content);
            } else {
                file_put_contents($template, $matched_template);
            }
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

    private function createTempFile($dir, $prefix, $postfix)
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
        $path = $dir.DIRECTORY_SEPARATOR.$prefix.$postfix;
        $fp = @fopen($path, 'x+');
        if ($fp) {
            fclose($fp);
        }
        return $path;
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
     * Filter: If a route was found, add name as body tag.
     */
    public function modifyTitle($title_parts)
    {
        if ($this->matched_route instanceof Route) {
            $route_title = $this->matched_route->getTitle();
            if (is_callable($route_title)) {
                $title_parts['title'] = call_user_func($route_title, $this->matched_args);
                return $title_parts;
            }
            $title_parts['title'] = $route_title ?? get_bloginfo( 'name' );
        }
        return $title_parts;
    }
}
