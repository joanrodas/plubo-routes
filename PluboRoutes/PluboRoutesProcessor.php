<?php
namespace PluboRoutes;

use PluboRoutes\Router;

use PluboRoutes\Route\Route;
use PluboRoutes\Route\RedirectRoute;
use PluboRoutes\Route\ActionRoute;


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
    public static function init() {
      $self = new self( new Router() );

      add_action('init', array($self, 'add_routes'));
      add_action('parse_request', array($self, 'match_route_request'));
      add_action('template_redirect', array($self, 'route_action'));
      add_action('template_include', array($self, 'route_template_include'));
    }

    /**
     * Step 1: Register all our routes into WordPress. Flush rewrite rules if the routes changed.
     */
    public function add_routes() {

        $routes = apply_filters('plubo/routes', array() );
        foreach ($routes as $route) {
          $this->router->add_route($route);
        }

        $this->router->compile();

        // die(print_r( json_encode($routes) ));
        // $routes_hash = md5( $routes.toString() );
        // if ($routes_hash != get_option('plubo-routes-hash')) {
          flush_rewrite_rules(); //TODO: Alternativa
          // update_option('plubo-routes-hash', $routes_hash);
        // }
    }

    /**
     * Step 2: Attempts to match the current request to an added route.
     *
     * @param WP $wp
     */
    public function match_route_request(\WP $wp) {
      $all_query_vars = $wp->query_vars;
      $matched_args = array();
      $matched_route = $this->router->match($all_query_vars);

      if ($matched_route instanceof Route || $matched_route instanceof RedirectRoute || $matched_route instanceof ActionRoute) {
        $args_names = $matched_route->getArgs();
        foreach ($args_names as $arg_name) {
          $matched_args[$arg_name] = $all_query_vars[$arg_name] ?? false;
        }
        $this->matched_route =  apply_filters( 'plubo/matched_route', $matched_route, $matched_args );
        $this->matched_args = $matched_args;
      }

      if ($matched_route instanceof \WP_Error && in_array('route_not_found', $matched_route->get_error_codes())) {
          wp_die($matched_route, 'Route Not Found', array('response' => 404));
      }
    }

    /**
     * Step 3: If a route was found, execute the route's action. Or redirect if RedirectRoute.
     */
    public function route_action() {
      if ( $this->matched_route instanceof Route ) {
        status_header( 200 );
        do_action( $this->matched_route->getAction(), $this->matched_args );
      }
      else if ( $this->matched_route instanceof ActionRoute ) {
        // status_header( 200 );
        // $status = $this->matched_route->getStatus();
        $isCallable = $this->matched_route->hasCallback();
        $action = $this->matched_route->getAction();
        if($isCallable) $action = call_user_func( $action, $this->matched_args );
        do_action( $action );
      }
      else if ( $this->matched_route instanceof RedirectRoute ) {
        $status = $this->matched_route->getStatus();
        $isCallable = $this->matched_route->hasCallback();
        $redirect_to = $this->matched_route->getAction();

        if($isCallable) $redirect_to = call_user_func( $redirect_to, $this->matched_args );

        if($this->matched_route->isExternal()) {
          wp_redirect($redirect_to, $status);
          die;
        }
        else {
          wp_safe_redirect( home_url($redirect_to), $status );
          exit;
        }
      }
    }

    /**
     * Step 4: If a route of type Route was found, load the route template.
     *
     * @param string $template
     *
     * @return string
     */
    public function route_template_include($template) {
        if ( !$this->matched_route instanceof Route ) return $template;

        if($this->matched_route->hasTemplateCallback()) {
          $template_func = $this->matched_route->getTemplate();
          $template = call_user_func( $template_func, $this->matched_args );
        }
        else $template = apply_filters( 'plubo/template', locate_template( $this->matched_route->getTemplate() ) );

        // $route_template = plugin_dir_path( __FILE__ ) . 'BladeRedirector.php';
        // if ( !empty($route_template) ) {
        //     $template = $route_template;
        // }
        // error_log('TEMPLATE', true);
        // error_log($template, true);

        return $template;
    }



}
