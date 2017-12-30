<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestParameterDescriptionsController;

class ParameterDescriptionsTest extends BaseRouteTestingClass
{
    public function testThatDescriptionsAreParsedCorrectly()
    {
        $route = $this->wrappedRoute(
            TestParameterDescriptionsController::class . '@described',
            null,
            '/endpoint/{id}'
        );

        $this->assertEquals('It\'s an ID of your model', $route->getParameterDescription('path', 'id'));
        $this->assertEquals('Week day number', $route->getParameterDescription('query', 'weekDay'));
    }

    public function testThatInvalidTagFormatExceptionIsThrown()
    {
        $this->expectException(InvalidTagFormat::class);

        $this->wrappedRoute(TestParameterDescriptionsController::class . '@invalidDescribed')->getParameterDescriptions();
    }
}