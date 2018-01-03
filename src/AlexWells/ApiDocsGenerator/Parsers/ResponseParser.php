<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Helpers;

class ResponseParser
{
    /**
     * Steps that should be taken to transform the string from raw to end result.
     *
     * @var array
     */
    protected const TRANSFORM_STEPS = ['newlines', 'shortArray', 'keyNames', 'varsOfType', 'types', 'repeatedObject', 'decode'];

    /**
     * Content to be parsed.
     *
     * @var string
     */
    protected $content;

    /**
     * ResponseParser constructor.
     *
     * @param string $content Content to be parsed.
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Parse the content.
     *
     * @return array
     * @throws InvalidTagFormat
     */
    public function parse()
    {
        $content = $this->content;

        foreach (static::TRANSFORM_STEPS as $step) {
            $content = static::callTransformer($step, $content);

            if($content === null) {
                throw new InvalidTagFormat("Response tag format is invalid. Failed at step: $step");
            }

            if(empty($content)) {
                return $content;
            }
        }

        return $content;
    }

    /**
     * Replace \n and similar symbols with nothing.
     *
     * @param $content
     * @return string
     */
    protected static function transformNewlines($content)
    {
        return Helpers::clearNewlines($content);
    }

    /**
     * Replace `int[]` with `[ :: int ]`
     *
     * @param $content
     * @return string|null
     */
    protected static function transformShortArray($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\[\]"), '[ :: $1 ]', $content);
    }

    /**
     * Replace `nested: {}` with `"nested": {}`
     *
     * @param $content
     * @return string|null
     */
    protected static function transformKeyNames($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\s*:[^:]"), '"$1": ', $content);
    }

    /**
     * Replace `year :: int` with `"year": {"$ref": "int"}`
     *
     * @param $content
     * @return string|null
     */
    protected static function transformVarsOfType($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("(\w+)\s*::\s*(\w+)"), '"$1": {"$ref": "$2"}', $content);
    }

    /**
     * Replace `:: int` with `{"$ref": "int"}`
     *
     * @param $content
     * @return string|null
     */
    protected static function transformTypes($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("\s*::\s*(\w+)"), '{"$ref": "$1"}', $content);
    }

    /**
     * Replace `:: {}` with `{"$ref": {}}`
     *
     * @param $content
     * @return string|null
     */
    protected static function transformRepeatedObject($content)
    {
        return preg_replace(Helpers::regexExcludeInQuotes("::\s*{(.*)}"), '{"$ref": {$1}}', $content);
    }

    /**
     * Decode JSON string into PHP 2d array.
     *
     * @param $content
     *
     * @return array|null
     */
    protected static function transformDecode($content)
    {
        return json_decode($content, true);
    }

    /**
     * Call transformer method by it's partial name.
     *
     * @param string $name Transformer name
     *
     * @return string|array
     */
    protected static function callTransformer($name, $content)
    {
        $transformer = static::getTransformerMethodName($name);

        if(! method_exists(static::class, $transformer)) {
            return $content;
        }

        return static::$transformer($content);
    }

    /**
     * Get method name for transformer by it's partial name.
     *
     * @param string $name Transformer name
     *
     * @return string
     */
    protected static function getTransformerMethodName($name)
    {
        // Convert snake_case (if present) to camelCase
        $name = camel_case($name);

        // Capitalize first letter
        $name = ucfirst($name);

        return "transform$name";
    }
}