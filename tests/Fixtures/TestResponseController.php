<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class TestResponseController extends Controller
{
    /**
     * @response
     */
    public function nothing() {}

    /**
     * @response :: int
     */
    public function justType() {}

    /**
     * @response [ :: int ]
     */
    public function justTypedArray() {}

    /**
     * @response int[]
     */
    public function anotherSyntaxOfTypedArray() {}

    /**
     * @response {
     *   year :: int
     * }
     */
    public function simple() {}

    /**
     * @response {
     *   nested: {
     *     two: {
     *       times :: int
     *     }
     *   }
     * }
     */
    public function nestedTwoTimes() {}

    /**
     * @response {
     *   array: []
     * }
     */
    public function emptyArray() {}

    /**
     * @response [ :: {
     *   id :: int
     * } ]
     */
    public function arrayOfCustomObjects() {}

    /**
     * @response "this is \"escaped\" quote inside a string"
     */
    public function escapedQuoteInsideString() {}

    /**
     * @response "a 'quote' inside"
     */
    public function quoteInsideString() {}

    /**
     * @response ":: int, [ :: int ], int[], { prop: 123 }, { prop :: int }, [ :: { } ]"
     */
    public function shortcutsInsideString() {}

    /**
     * @response { 123 : 'Invalid JSON with unsupported shortcuts' ]
     */
    public function invalid() {}
}