## Laravel API documentation generator

> Badges here

# BETA VERSION

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