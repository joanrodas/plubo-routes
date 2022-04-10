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

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

PluboRoutes\PluboRoutesProcessor::init();

add_filter('plubo/routes', function ($routes) {
    $routes[] = new PluboRoutes\Route\Route(
        'clients',
        'client/{client_id:number}',
        function () {
            //Do some stuff...
            return locate_template(app('sage.finder')->locate('client')); //SAGE 10 example
        }
    );
    $routes[] = new PluboRoutes\Route\RedirectRoute(
        'city/{city:word}',
        function ($matches) {
            return 'https://www.google.com/search?q=' . $matches['city']; //SAGE 10 example
        },
        array(
            'status' => 302,
            'external' => true
        )
    );
    $routes[] = new PluboRoutes\Route\ActionRoute(
        'sendEmail',
        function () {
            $to = get_option( 'admin_email' );
            $subject = 'Hello world';
            $message = 'Wow!';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $message, $headers);
        }
    );
    return $routes;
});
