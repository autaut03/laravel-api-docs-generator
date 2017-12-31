<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\ClosureRouteException;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestMiscController;

class MiscTest extends BaseRouteTestingClass
{
    public function testConsoleCommandNeedsAtLeastOneRouteMask()
    {
        $output = $this->artisan('api-docs:generate');

        $this->assertEquals('You must provide at least one route mask.' . PHP_EOL, $output);
    }

    public function testConsoleCommandWorks()
    {
        $output = $this->artisan('api-docs:generate', ['--masks' => ['*']]);

        $this->assertFileExists('public/docs/index.html');
    }

    public function testThatItDoesNotWorkWithClosure()
    {
        $this->expectException(ClosureRouteException::class);

        $this->wrappedRoute(function () {})->getSummary();
    }

    public function testThatItParsesTitleAndDescription()
    {
        $route = $this->wrappedRoute(TestMiscController::class . '@titleAndDescription');

        $this->assertEquals('Endpoint title', $route->getTitle());
        $this->assertEquals('And a long description on many, many, many lines.', $route->getDescription());
    }

    public function testThatItParsesTitlesFromMethodNameIfNotSpecified()
    {
        $route = $this->wrappedRoute(TestMiscController::class . '@automaticTitle');

        $this->assertEquals('Automatic title', $route->getTitle());
    }

    public function testThatItHidesFromDocs()
    {
        $route = $this->wrappedRoute(TestMiscController::class . '@hidden');

        $this->assertEquals(true, $route->isHiddenFromDocs());
    }
}
