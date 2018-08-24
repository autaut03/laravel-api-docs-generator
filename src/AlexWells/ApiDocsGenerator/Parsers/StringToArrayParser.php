<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

class StringToArrayParser extends AbstractStepTransformer
{
    /**
     * Steps that should be taken to transform the string from raw to end result.
     *
     * @return array
     */
    protected static function getTransformerSteps()
    {
        return ['wrapInQuotes', 'wrapInArrayBrackets', 'decode'];
    }

    /**
     * Replace `string with "quotes" inside` with `"string with \"quotes\" inside"`.
     *
     * @param string $string
     *
     * @return string
     */
    protected static function transformWrapInQuotes(string $content) {
        // If string is already wrapped in quotes, we do nothing and
        // assume that it's a valid JSON formatted string or array of strings.
        if(starts_with($content, '"') && ends_with($content, '"')) {
            return $content;
        }

        // If it's not, then we should escape the string and wrap in quotes.
        // json_encode fits perfectly there.
        return json_encode($content);
    }

    /**
     * Replace `"string", "another"` with `["string", "another"]`.
     *
     * @param string $content
     *
     * @return string
     */
    protected static function transformWrapInArrayBrackets(string $content) {
        // Once again, if it's already wrapped we do nothing and
        // assume it's correct.
        if(starts_with($content, '[') && ends_with($content, ']')) {
            return $content;
        }

        return "[$content]";
    }

    /**
     * Decode JSON string into PHP array.
     *
     * @param $content
     *
     * @return array|null
     */
    protected static function transformDecode(string $content) {
        return json_decode($content, true);
    }
}
