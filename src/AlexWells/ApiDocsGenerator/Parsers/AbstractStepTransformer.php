<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidFormat;

abstract class AbstractStepTransformer
{
    /**
     * Steps that should be taken to transform the string from raw to end result.
     *
     * @return array
     */
    abstract protected static function getTransformerSteps();

    /**
     * Content to be parsed.
     *
     * @var string
     */
    protected $content;

    /**
     * AbstractStep constructor.
     *
     * @param string $content content to be parsed
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Parse the content.
     *
     * @throws InvalidFormat
     *
     * @return array
     */
    public function parse()
    {
        $content = $this->content;

        foreach (static::getTransformerSteps() as $step) {
            $content = static::callTransformer($step, $content);

            if($content === null) {
                throw new InvalidFormat("Format is invalid. Failed at step: $step");
            }

            if(empty($content)) {
                return $content;
            }
        }

        return $content;
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
