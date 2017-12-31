<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestParameterDefaultsController;

class ParameterDefaultsTest extends BaseRouteTestingClass
{
    public function testThatDefaultValuesAreParsedCorrectly()
    {
        $parser = $this->wrappedRoute(TestParameterDefaultsController::class . '@withDefaults')->getDefaultTagsParser();

        $this->assertEquals('2', $parser->get('query', 'weekDay'));
        $this->assertEquals('789', $parser->get('path', 'id'));
    }

    public function testThatInvalidTagFormatExceptionIsThrown()
    {
        $this->expectException(InvalidTagFormat::class);

        $this->wrappedRoute(TestParameterDefaultsController::class . '@invalid')->getDefaultTagsParser()->all();
    }
}