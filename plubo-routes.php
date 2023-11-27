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

define('PLUBO_ROUTES_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once PLUBO_ROUTES_PLUGIN_DIR . 'vendor/autoload.php';

PluboRoutes\RoutesProcessor::init();
