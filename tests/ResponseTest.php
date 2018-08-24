<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidFormat;
use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Tests\Fixtures\TestResponseController;

class ResponseTest extends BaseRouteTestingClass
{
    public function testThatEmptyResponseTagIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@nothing')->getResponses();

        $this->assertCount(0, $responses);
    }

    public function testThatASimpleTypeIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@justType')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals(['$ref' => 'int'], $responses[0]);
    }

    public function testThatASimpleTypedArrayIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@justTypedArray')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            ['$ref' => 'int']
        ], $responses[0]);
    }

    public function testThatAnotherSyntaxOfTypedArrayIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@anotherSyntaxOfTypedArray')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            ['$ref' => 'int']
        ], $responses[0]);
    }

    public function testThatSimpleObjectIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@simple')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            'year' => ['$ref' => 'int']
        ], $responses[0]);
    }

    public function testThatNestedObjectIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@nestedTwoTimes')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            'nested' => [
                'two' => [
                    'times' => ['$ref' => 'int']
                ]
            ]
        ], $responses[0]);
    }

    public function testThatEmptyArrayIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@emptyArray')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            'array' => []
        ], $responses[0]);
    }

    public function testThatArrayOfCustomObjectsIsParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@arrayOfCustomObjects')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals([
            ['$ref' => [
                'id' => ['$ref' => 'int']
            ]]
        ], $responses[0]);
    }

    public function testThatShortcutsDoNotWorkInsideStrings()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@shortcutsInsideString')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals(':: int, [ :: int ], int[], { prop: 123 }, { prop :: int }, [ :: { } ]', $responses[0]);
    }

    public function testThatEscapedQuotesInsideStringsAreParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@escapedQuoteInsideString')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals('this is "escaped" quote inside a string', $responses[0]);
    }

    public function testThatQuotesInsideOtherQuotesAreParsedCorrectly()
    {
        $responses = $this->wrappedRoute(TestResponseController::class . '@quoteInsideString')->getResponses();

        $this->assertCount(1, $responses);
        $this->assertEquals("a 'quote' inside", $responses[0]);
    }

    public function testThatInvalidTagFormatExceptionIsThrown()
    {
        $this->expectException(InvalidFormat::class);

        $this->wrappedRoute(TestResponseController::class . '@invalid')->getResponses();
    }
}
