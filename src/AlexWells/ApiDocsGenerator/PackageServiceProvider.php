<?php

namespace AlexWells\ApiDocsGenerator;

use AlexWells\ApiDocsGenerator\Commands\GenerateDocumentation;
use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views/', 'api-docs');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/api-docs')
        ], 'views');

        $this->publishes([
            __DIR__ . './../resources/assets' => resource_path('assets/vendor/api-docs')
        ], 'assets');
    }

    /**
     * Register API documentation generator commands.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            GenerateDocumentation::class
        ]);
    }
}
