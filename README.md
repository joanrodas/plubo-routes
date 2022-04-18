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
✔️  Easy to use with Sage 10


<br/>

## Getting started

`composer require joanrodas/plubo-routes`

> You can also install Plubo Routes as a standalone WordPress plugin, simply downloading the zip and placing it in the plugins folder.

<br/>

[Read the Docs](https://www.plubo.dev/docs/routing/)

<br>

## Adding new routes

There are different types of routes:

- [Route (template)](https://www.plubo.dev/docs/routing/)
- [RedirectRoute](https://www.plubo.dev/docs/routing/redirect-routes.html)
- [ActionRoute](https://www.plubo.dev/docs/routing/action-routes.html)
- [PageRoute](https://www.plubo.dev/docs/routing/page-routes.html)

<br>

## How to add a new route

You can add new routes using the following filter:

```php
PluboRoutes\RoutesProcessor::init();

add_filter( 'plubo/routes', array($this, 'add_routes') );
public function add_routes( $routes ) {
    //Your routes
    return $routes;
}
```

<br>

## Basic routes

Basic routes take 3 parameters:

| Parameter  | Type |
| ------------- | ------------- |
| **Route Path**  | String  |
| **Template file name**  | String \| Callable  |
| **Config**  | Array (optional)  |

Examples:

```php
use PluboRoutes\Route\Route;

add_filter( 'plubo/routes', array($this, 'add_routes') );
public function add_routes( $routes ) {
    $routes[] = new Route('clients/list', 'template_name');

    //SAGE 10 example
    $routes[] = new Route(
        'dashboard/{subpage:slug}',
        function($matches) {
            $subpage = 'dashboard/' . $matches['subpage'];
            return locate_template( app('sage.finder')->locate($subpage) );
        },
        [
            'name' => 'my-route'
        ]
    );
    return $routes;
}
```

<br>

## Available syntax

You can use the format ***{variable_name:type}*** with any of the available types:

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

> You can also use custom regex patterns using the format ***{variable_name:regex_pattern}*** like ***{author:([a-z0-9-]+)}***

<br>

## Changing general template path

By default, Plubo Routes will search the template inside your theme, but you can use a hook to chenge the default path.

If you use Sage 10, you could add something like this:

```php
add_filter( 'plubo/template', function($template) {
    return app('sage.finder')->locate($template);
});
```

<br>

## Custom Actions

Named routes provide a hook to execute your custom actions:

```php
add_action('plubo/route_{route_name}', function($matches) {
    //Do something
});
```

<br>

## Contributions
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub issues](https://img.shields.io/github/issues/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub license](https://img.shields.io/github/license/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/blob/main/LICENSE)


Feel free to contribute to the project, suggesting improvements, reporting bugs and coding.
