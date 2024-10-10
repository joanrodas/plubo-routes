<?php

/**
 * @wordpress-plugin
 * Plugin Name:       PLUBO Routes
 * Plugin URI:        https://sirvelia.com/
 * Description:       WordPress routes made simple.
 * Version:           1.0.0
 * Author:            Joan Rodas - Sirvelia
 * Author URI:        https://sirvelia.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       plubo-routes
 * Domain Path:       /languages
 */

use PluboRoutes\Middleware\Cors;
use PluboRoutes\Middleware\Cache;
use PluboRoutes\Middleware\RateLimit;
use PluboRoutes\Middleware\JwtValidation;
use PluboRoutes\Middleware\SchemaValidator;


define('PLUBO_ROUTES_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once PLUBO_ROUTES_PLUGIN_DIR . 'vendor/autoload.php';

PluboRoutes\RoutesProcessor::init();

// add_filter('plubo/routes', function ($routes) {
//   $test_route = new PluboRoutes\Route\Route(
//     'testing',
//     function ($matches) {
//       echo 'TEST';
//     },
//     [
//       'render' => true,
//       'name' => 'test-route'
//     ]
//   );

//   // $test_route->useMiddleware('jwtMiddleware');

//   $routes[] = $test_route;
//   return $routes;
// });

// function loggingMiddleware($request, $next)
// {
//   // Log the request data
//   $route = $_SERVER['REQUEST_URI'] ?? 'unknown';
//   $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';

//   error_log("Accessing route: {$route} with method: {$method} at " . date('Y-m-d H:i:s'));

//   // Proceed to the next middleware or main route action
//   return $next();
// }

// add_filter('plubo/endpoints', function ($routes) {
//   $test_route = new PluboRoutes\Endpoint\PostEndpoint(
//     'sirvelia/v1',
//     'test',
//     function ($request) {
//       return ['test' => 'TEST'];
//     }
//   );

//   $schemaPath = PLUBO_ROUTES_PLUGIN_DIR . 'test.json';
//   $schema = json_decode(file_get_contents($schemaPath));
//   $test_route->useMiddleware(new SchemaValidator($schema));

//   $test_route->useMiddleware(new Cors('*', ['GET', 'POST'], ['Content-Type', 'Authorization']));
//   $test_route->useMiddleware(new JwtValidation('secret')); // 10 minutes
//   $test_route->useMiddleware(new Cache(600)); // 10 minutes
//   $test_route->useMiddleware(new RateLimit(1, 30)); // 1 requests per 30 seconds

//   $routes[] = $test_route;
//   return $routes;
// });
