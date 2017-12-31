<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Parser\RouteWrapper;
use AlexWells\ApiDocsGenerator\Exceptions\NoTypeSpecifiedException;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestPathParametersController;

class PathParametersTest extends BaseRouteTestingClass
{
    public function testThatSimpleTypeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@simpleType',
            null,
            '/endpoint/{id}'
        )->getPathParameters();

        $this->assertCount(1, $parameters);

        $parameter = $parameters[0];

        $this->assertArrayHasKey('name', $parameter);
        $this->assertArrayHasKey('required', $parameter);
        $this->assertArrayHasKey('type', $parameter);
        $this->assertArrayHasKey('default', $parameter);
        $this->assertArrayHasKey('regex', $parameter);
        $this->assertArrayHasKey('description', $parameter);

        $this->assertEquals('id', $parameter['name']);
        $this->assertEquals(true, $parameter['required']);
        $this->assertEquals('int', $parameter['type']);
        $this->assertEquals(null, $parameter['default']);
        $this->assertEquals(null, $parameter['regex']);
        $this->assertEquals('', $parameter['description']);
    }

    public function testThatClassTypeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@classType',
            null,
            '/endpoint/{class}'
        )->getPathParameters();

        $this->assertEquals('SimpleClass', $parameters[0]['type']);
    }

    public function testThatModelTypeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@modelType',
            null,
            '/endpoint/{model}'
        )->getPathParameters();

        $this->assertEquals('model_id', $parameters[0]['type']);
        //$this->assertEquals('`SimpleEloquentModel` id', $parameters[0]['description']);
    }

    public function testThatRequiredAttributeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@simpleType',
            null,
            '/endpoint/{id?}'
        )->getPathParameters();

        $this->assertEquals(false, $parameters[0]['required']);
    }

    public function testThatDefaultAttributeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@withDefaultValue',
            null,
            '/endpoint/{id?}'
        )->getPathParameters();

        $this->assertEquals('123', $parameters[0]['default']);
    }

    public function testThatOverwrittenDefaultAttributeIsParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@overwrittenDefaultValue',
            null,
            '/endpoint/{id?}'
        )->getPathParameters();

        $this->assertEquals('789', $parameters[0]['default']);
    }

    public function testThatOrderOfArgumentsIsNotNecessary()
    {
        $parameters = $this->wrappedRoute(
            TestPathParametersController::class . '@mixedOrder',
            null,
            '/products/{product}/version/{version}'
        )->getPathParameters();

        $this->assertEquals('product', $parameters[0]['name']);
        $this->assertEquals('int', $parameters[0]['type']);

        $this->assertEquals('version', $parameters[1]['name']);
        $this->assertEquals('string', $parameters[1]['type']);
    }

    public function testThatNoTypeSpecifiedExceptionIsThrown()
    {
        $this->expectException(NoTypeSpecifiedException::class);

        $this->wrappedRoute(
            TestPathParametersController::class . '@noTypeSpecified',
            null,
            '/articles/{id}',
            ['noTypeChecks' => false]
        )->getPathParameters();
    }

    public function testThatNoTypeSpecifiedExceptionIsNotThrown()
    {
        $this->wrappedRoute(
            TestPathParametersController::class . '@noTypeSpecified',
            null,
            '/articles/{id}',
            ['noTypeChecks' => true]
        )->getPathParameters();

        $this->assertTrue(true);
    }

    public function testThatWhereConditionsAreParsedCorrectly()
    {
        $route = $this->createRoute(
            TestPathParametersController::class . '@simpleType',
            null,
            '/endpoint/{id}'
        )->where('id', '[0-9]+');

        $route = new RouteWrapper($route, [
            'noTypeChecks' => true
        ]);

        $parameter = $route->getPathParameters()[0];

        $this->assertEquals('[0-9]+', $parameter['regex']);
    }
}
