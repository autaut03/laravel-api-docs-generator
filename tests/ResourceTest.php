<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestDefaultResourceController;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestResourceController;

class ResourceTest extends BaseRouteTestingClass
{
    public function testThatParsedRouteAutomaticallyUsesControllerResourceIfNotDefined()
    {
        $route = $this->wrappedRoute(TestResourceController::class . '@hasResource');

        $this->assertEquals('Some resource', $route->getResourceName());
    }

    public function testThatParsedRouteOverwritesResource()
    {
        $route = $this->wrappedRoute(TestResourceController::class . '@overwriteResource');

        $this->assertEquals('Overwritten resource', $route->getResourceName());
    }

    public function testThatInvalidResourceNameFailsWithInvalidTagFormatError()
    {
        $this->expectException(InvalidTagFormat::class);

        $this->wrappedRoute(TestResourceController::class . '@invalid')->getResourceName();
    }

    public function testThatParsedRouteHasDefaultResource()
    {
        $route = $this->wrappedRoute(TestDefaultResourceController::class . '@defaultResource');

        $this->assertEquals('Unclassified routes', $route->getResourceName());
    }
}