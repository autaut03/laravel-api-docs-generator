<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestResourceController;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestDefaultResourceController;

class ResourceTest extends BaseRouteTestingClass
{
    public function testThatParsedRouteAutomaticallyUsesControllerResourceIfNotDefined()
    {
        $route = $this->wrappedRoute(TestResourceController::class . '@has');

        $this->assertEquals(['Some resource'], $route->getResource());
    }

    public function testThatParsedRouteOverwritesResource()
    {
        $route = $this->wrappedRoute(TestResourceController::class . '@overwritten');

        $this->assertEquals(['Overwritten resource'], $route->getResource());
    }

    public function testThatNestedResourceIsParsedCorrectly()
    {
        $route = $this->wrappedRoute(TestResourceController::class . '@nested');

        $this->assertEquals(['General', 'Sub-category', 'Last one'], $route->getResource());
    }

    public function testThatInvalidResourceNameFailsWithInvalidTagFormatError()
    {
        $this->expectException(InvalidFormat::class);

        $this->wrappedRoute(TestResourceController::class . '@invalid')->getResource();
    }

    public function testThatParsedRouteHasDefaultResource()
    {
        $route = $this->wrappedRoute(TestDefaultResourceController::class . '@default');

        $this->assertEquals(['Unclassified routes'], $route->getResource());
    }
}
