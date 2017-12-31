<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestParameterDescriptionsController;

class ParameterDescriptionsTest extends BaseRouteTestingClass
{
    public function testThatDescriptionsAreParsedCorrectly()
    {
        $parser = $this->wrappedRoute(TestParameterDescriptionsController::class . '@described')->getDescribeTagsParser();

        $this->assertEquals('It\'s an ID of your model', $parser->get('path', 'id'));
        $this->assertEquals('Week day number', $parser->get('query', 'weekDay'));
    }

    public function testThatInvalidTagFormatExceptionIsThrown()
    {
        $this->expectException(InvalidTagFormat::class);

        $this->wrappedRoute(TestParameterDescriptionsController::class . '@invalid')->getDescribeTagsParser()->all();
    }
}
