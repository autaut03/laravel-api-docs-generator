## [WIP] Laravel API documentation generator

[![Latest Stable Version](https://img.shields.io/packagist/v/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://packagist.org/packages/autaut03/laravel-api-docs-generator)
[![Downloads](https://img.shields.io/packagist/dt/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://packagist.org/packages/autaut03/laravel-api-docs-generator)
[![Travis Build](https://img.shields.io/travis/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://travis-ci.org/autaut03/laravel-api-docs-generator)
[![StyleCI](https://styleci.io/repos/115812998/shield)](https://styleci.io/repos/115812998)
[![PHP Eye](http://php-eye.com/badge/autaut03/laravel-api-docs-generator/tested.svg?style=flat-square)](https://php-eye.com/package/autaut03/laravel-api-docs-generator)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://scrutinizer-ci.com/g/autaut03/laravel-api-docs-generator/?branch=master)
[![codecov](https://img.shields.io/codecov/c/github/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://codecov.io/gh/autaut03/laravel-api-docs-generator)
[![Dependency Status](https://www.versioneye.com/user/projects/5a47db5f0fb24f005043f898/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/5a47db5f0fb24f005043f898)
[![License](https://img.shields.io/packagist/l/autaut03/laravel-api-docs-generator.svg?style=flat-square)](https://packagist.org/packages/autaut03/laravel-api-docs-generator)

# BETA VERSION

NO HTML GENERATION AVAILABLE YET.

Automatically generate your API documentation from your existing Laravel routes. No example available for now.

## Installation

Require this package with composer using the following command:

```sh
$ composer require autaut03/laravel-api-docs-generator
```
Using Laravel < 5.5? Go to your `config/app.php` and add the service provider:

```php
AlexWells\ApiDocsGenerator\PackageServiceProvider::class,
```

## Publish vendor

If you want to modify HTML template or make some changes to the assets (img, css, js) then publish vendor files:

```sh
$ php artisan vendor:publish
```

After that views and assets will appear inside `resources/vendor/api-docs` directory and will be used instead of default.

## Usage

To generate your API documentation, use the `api-docs:generate` artisan command.

```sh
$ php artisan api-docs:generate -m="api/v2/*" -m="non-api/another" -m="manual/{p}"
```

> Use -h flag to get a list of available options

## How does it work?

This package uses these resources to generate the API documentation:

### TODO: use test cases & test fixtures as examples for now.