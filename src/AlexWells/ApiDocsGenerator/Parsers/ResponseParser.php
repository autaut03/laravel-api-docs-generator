<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use AlexWells\ApiDocsGenerator\Helpers;
use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;

class ResponseParser extends AbstractStepTransformer
{
    /**
     * Steps that should be taken to transform the string from raw to end result.
     *
     * @return array
     */
    protected static function getTransformerSteps()
    {
        return ['newlines', 'shortArray', 'keyNames', 'varsOfType', 'types', 'repeatedObject', 'decode'];
    }

    /**
     * Replace \n and similar symbols with nothing.
     *
     * @param $content
     *
     * @return string
     */
    protected static function transformNewlines($content)
    {
        return Helpers::clearNewlines($content);
    }

    /**
     * Replace `int[]` with `[ :: int ]`.
     *
     * @param $content
     *
     * @return string|null
     */
    protected static function transformShortArray($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\[\]"), '[ :: $1 ]', $content);
    }

    /**
     * Replace `nested: {}` with `"nested": {}`.
     *
     * @param $content
     *
     * @return string|null
     */
    protected static function transformKeyNames($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\s*:[^:]"), '"$1": ', $content);
    }

    /**
     * Replace `year :: int` with `"year": {"$ref": "int"}`.
     *
     * @param $content
     *
     * @return string|null
     */
    protected static function transformVarsOfType($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\s*::\s*(\w+)"), '"$1": {"$ref": "$2"}', $content);
    }

    /**
     * Replace `:: int` with `{"$ref": "int"}`.
     *
     * @param $content
     *
     * @return string|null
     */
    protected static function transformTypes($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("\s*::\s*(\w+)"), '{"$ref": "$1"}', $content);
    }

    /**
     * Replace `:: {}` with `{"$ref": {}}`.
     *
     * @param $content
     *
     * @return string|null
     */
    protected static function transformRepeatedObject($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("::\s*{(.*)}"), '{"$ref": {$1}}', $content);
    }

    /**
     * Decode JSON string into PHP array.
     *
     * @param $content
     *
     * @return array|null
     */
    protected static function transformDecode($content)
    {
        return json_decode($content, true);
    }
}
