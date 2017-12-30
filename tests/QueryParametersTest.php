<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestQueryParametersController;

class QueryParametersTest extends BaseRouteTestingClass
{
    public function testThatSimpleFormRequestRulesAreParsedCorrectly()
    {
        $parameters = $this->wrappedRoute(TestQueryParametersController::class . '@simple')->getQueryParameters();

        $this->assertCount(2, $parameters);

        $this->assertArrayHasKey('name', $parameters[0]);
        $this->assertArrayHasKey('default', $parameters[0]);
        $this->assertArrayHasKey('rules', $parameters[0]);
        $this->assertArrayHasKey('description', $parameters[0]);

        $this->assertEquals('year', $parameters[0]['name']);
        $this->assertEquals(['required', 'integer', 'min:2017'], $parameters[0]['rules']);

        $this->assertEquals('weekDay', $parameters[1]['name']);
        $this->assertEquals(['integer', 'between:1,7'], $parameters[1]['rules']);
    }

    public function testThatFormRequestWithContainerIsParsedCorrectly()
    {
        $this->wrappedRoute(TestQueryParametersController::class . '@withContainer')->getQueryParameters();

        $this->assertTrue(true);
    }
}
