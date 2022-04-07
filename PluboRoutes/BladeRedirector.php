<?php
namespace PluboRoutes;

use Jenssegers\Blade\Blade;

global $wp_query;

$blade = new Blade('/app/wp-content/plugins/plubo/' . 'resources/views', '/app/wp-content/plugins/plubo/' . 'resources/cache');
$args = $wp_query->query_vars;
echo $blade->render('test', $args );
