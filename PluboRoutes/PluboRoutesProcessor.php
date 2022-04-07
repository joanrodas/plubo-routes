<?php
namespace PluboRoutes;

use PluboRoutes\Router;
use PluboRoutes\Route;

/**
 * The Processor is in charge of the interaction between the routing system and
 * the rest of WordPress.
 *
 * @author Joan Rodas <joan@sirvelia.com>
 * Based on the work of Carl Alexander <contact@carlalexander.ca>
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
     * @param Route[] $routes
     */
    public static function init()
    {
        $self = new self( new Router() );

        add_action('init', array($self, 'add_routes'));
        add_action('parse_request', array($self, 'match_route_request'));
        add_action('template_include', array($self, 'route_template_include'));
        add_action('template_redirect', array($self, 'route_action'));
    }

    /**
     * Checks to see if a route was found. If there's one, it calls the route hook.
     */
    public function route_action()
    {
        if ( $this->matched_route instanceof Route ) {
          status_header( 200 );
          do_action( $this->matched_route->get_hook() );
        }
    }

    /**
     * Checks to see if a route was found. If there's one, it loads the route template.
     *
     * @param string $template
     *
     * @return string
     */
    public function route_template_include($template)
    {
        if ( !$this->matched_route instanceof Route ) return $template;

        $template = apply_filters( 'plubo/template', $this->matched_route->get_template() );

        // $route_template = plugin_dir_path( __FILE__ ) . 'BladeRedirector.php';
        // if ( !empty($route_template) ) {
        //     $template = $route_template;
        // }
        // error_log('TEMPLATE', true);
        // error_log($template, true);

        return $template;
    }

    /**
     * Attempts to match the current request to a route.
     *
     * @param WP $wp
     */
    public function match_route_request(\WP $wp)
    {
        $matched_route = $this->router->match($wp->query_vars);

        if ($matched_route instanceof Route) {
            $this->matched_route =  apply_filters( 'plubo/matched_route', $matched_route, $wp->query_vars );
        }

        if ($matched_route instanceof \WP_Error && in_array('route_not_found', $matched_route->get_error_codes())) {
            wp_die($matched_route, 'Route Not Found', array('response' => 404));
        }
    }

    /**
     * Register all our routes into WordPress.
     */
    public function add_routes()
    {

        $routes = apply_filters('plubo/routes', array() );
        foreach ($routes as $route) {
            $this->router->add_route($route);
        }

        $this->router->compile();

        $routes_hash = md5( serialize($routes) );
        if ($routes_hash != get_option('plubo-routes-hash')) {
            flush_rewrite_rules();
            update_option('plubo-routes-hash', $routes_hash);
        }
    }
}
