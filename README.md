<p align="center">
  <img src='https://raw.githubusercontent.com/joanrodas/plubo-docs/main/images/plubo.png' alt='Plubo' />
</p>

[![GitHub stars](https://img.shields.io/github/stars/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/stargazers)


WordPress routes made simple.


✔️  No need to write rewrite rules and tags manually\
✔️  Easily extendable with hooks\
✔️  Ready to use with blade templates


<br/>

## Getting started

`composer require joanrodas/plubo-routes`

> You can also install Plubo Routes as a standalone WordPress plugin, simply downloading the zip and placing it in the plugins folder.

<br/>

## Adding new routes

```php
<?php add_filter('plubo/routes', function($routes) {
  $routes[] = new PluboRoutes\Route('route_name', 'example/{city:word}/{id:number}', 'template_path');
  return $routes;
}); ?>
```
 
### Available syntax:
* number (numbers only)
* word (a-Z only)
* text (any valid url characters)
* slug (valid WordPress slug)
* date (Y-m-d format)

<br/>

## Route Actions

You can execute your custom functions:

```php
<?php add_action('plubo/route_{route_name}', function() {
  #Execute code
}); ?>
```

<br>

## Contributions
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub issues](https://img.shields.io/github/issues/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/issues)
[![GitHub license](https://img.shields.io/github/license/joanrodas/plubo-routes?style=for-the-badge)](https://github.com/joanrodas/plubo-routes/blob/main/LICENSE)


Feel free to contribute to the project, suggesting improvements, reporting bugs and coding.
