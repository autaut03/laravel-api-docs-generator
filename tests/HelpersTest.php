<?php

namespace AlexWells\ApiDocsGenerator\Tests;

use Orchestra\Testbench\TestCase;
use AlexWells\ApiDocsGenerator\Helpers;

class HelpersTest extends TestCase
{
    public function testFunctionNameToTextFunction()
    {
        $this->assertEquals('This is NASA module', Helpers::functionNameToText('thisIsNASAModule'));
        $this->assertEquals('Simple string with a ball', Helpers::functionNameToText('simpleStringWithABall'));
        $this->assertEquals('Snake case should also work', Helpers::functionNameToText('snake_case_should_also_work'));
        $this->assertEquals('What if there is too many characters', Helpers::functionNameToText('what_if_there_is_too___many_characters'));
        $this->assertEquals('Or some words are missing', Helpers::functionNameToText('or_some_words_are_missing_'));
    }
}
