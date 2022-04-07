<?php

/**
 * The plugin bootstrap file
 *
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

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

PluboRoutes\PluboRoutesProcessor::init();

// add_filter('plubo/routes', function($routes) {
//   $routes[] = new PluboRoutes\Route('testing', 'testing/{year:number}/{city:word}', 'test');
//   return $routes;
// });
