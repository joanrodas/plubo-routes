<p align="center">
  <img src='https://github.com/joanrodas/plubo-docs/blob/main/src/.vuepress/public/images/plubo-routes-banner.png?raw=true' alt='Plubo Routes' />
</p>

[![GitHub stars](https://img.shields.io/github/stars/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/stargazers)
![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/joanrodas/plubo-routes?style=for-the-badge)

WordPress routes made simple.


✔️  No need to write rewrite rules and tags manually\
✔️  Automatically flush rewrite rules when the routes change\
✔️  Custom redirects and action routes\
✔️  Easily extendable with hooks\
✔️  Easy to use with blade templates


<br/>

## Getting started

`composer require joanrodas/plubo-routes`

> You can also install Plubo Routes as a standalone WordPress plugin, simply downloading the zip and placing it in the plugins folder.

<br/>

## Adding new routes

```php
<?php
use PluboRoutes\RoutesProcessor;
use PluboRoutes\Route\Route;
use PluboRoutes\Route\RedirectRoute;
use PluboRoutes\Route\ActionRoute;

RoutesProcessor::init();

add_filter('plubo/routes', function($routes) {

  //Simple Route (template)
  $routes[] = new Route(
    'my_template',
    'newsletter/{creation:year}',
    'template_name'
  );

  //Route with a custom function (template)
  $routes[] = new Route(
    'clients',
    'client/{client_id:number}',
    function($matches) {
      $client_id = intval($matches['client_id']);
      $client = get_user_by('id', 1);
      //Do some stuff...
      return locate_template( app('sage.finder')->locate('client') ); //SAGE 10 example
    }
  );

  //External redirect, using parameters
  $routes[] = new RedirectRoute(
    'city/{city:word}',
    function($matches) {
      return 'https://www.google.com/search?q=' . $matches['city'];
    },
    array(
      'status' => 301,
      'external' => true //Default false
    )
  );

  // Action only route
  $routes[] = new ActionRoute(
    'sendEmail',
    function($matches) {
      $to = get_option( 'admin_email' );
      $subject = 'Hello world';
      $message = 'This is an email!';
      $headers = array( 'Content-Type: text/html; charset=UTF-8' );
      wp_mail( $to, $subject, $message, $headers );
    }
  );

  return $routes;
}); ?>
```

### Available syntax:
* number (numbers only)
* word (a-Z only)
* slug (a valid WordPress slug)
* date (yyyy-mm-dd date)
* year (4 digits)
* month (01-12)
* day (01-31)
* digit (single digit 0-9)
* jwt (JWT token)
* ip (IPv4)

> You can also use custom regex patterns using the format **{variable_name:regex_patter}** like **{example:([a-z0-9-]+)}**

<br/>

## Useful hooks

You can execute custom functions in named routes:

```php
<?php add_action('plubo/route_{route_name}', function() {
  //Execute code
}); ?>
```

Modify the template path to suit your theme:

```php
<?php add_filter( 'plubo/template', function($template) {
  //Do some stuff
  return $template;
}); ?>
```

Modify the query arg name that specifies the route name:

```php
<?php add_filter( 'plubo/route_variable', function($route_variable) {
  $route_variable = 'custom_route';
  return $route_variable;
}); ?>
```

<br>

## Contributions
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub issues](https://img.shields.io/github/issues/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub license](https://img.shields.io/github/license/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/blob/main/LICENSE)


Feel free to contribute to the project, suggesting improvements, reporting bugs and coding.
