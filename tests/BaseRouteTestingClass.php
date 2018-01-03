<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\PackageServiceProvider;
use AlexWells\ApiDocsGenerator\Parsers\RouteWrapper;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;

class BaseRouteTestingClass extends TestCase
{
    /**
     * A variable used to always be sure that generated wrapperRoute's uri is unique.
     *
     * @var int
     */
    protected $endpointNameCounter;

    /**
     * Creates new route.
     *
     * @param  \Closure|array|string  $action
     * @param  array|string  $methods
     * @param  string  $uri
     *
     * @return Route
     */
    public function createRoute($action, $methods = null, $uri = null)
    {
        if(is_string($action)) {
            $action = [
                'controller' => $action,
                'uses' => $action
            ];
        }

        $methods = $methods ?: 'GET';

        if(! $uri) {
            $uri = '/endpoint' . $this->endpointNameCounter;
            $this->endpointNameCounter++;
        }

        return new Route($methods, $uri, $action);
    }

    /**
     * Creates a wrapped route (shortcut).
     *
     * @param  \Closure|array|string  $action
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  array|null  $options
     *
     * @return RouteWrapper
     */
    public function wrappedRoute($action, $methods = null, $uri = null, $options = null)
    {
        $route = $this->createRoute($action, $methods, $uri);

        $options = $options ?: [
            'noTypeChecks' => true
        ];

        return new RouteWrapper($route, $options);
    }

    /**
     * Call artisan command.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    public function artisan($command, $parameters = [])
    {
        $this->app[Kernel::class]->call($command, $parameters);

        return $this->app[Kernel::class]->output();
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->endpointNameCounter = 1;
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PackageServiceProvider::class
        ];
    }
}
