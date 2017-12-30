<?php

namespace AlexWells\ApiDocsGenerator;

class Helpers
{
    /**
     * Clears \n characters (and similar) in the string.
     *
     * @param string $string
     * @return string
     */
    public static function clearNewlines($string)
    {
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Converts any case text into normal string (see tests for examples).
     *
     * @param string $string
     * @return string
     */
    public static function functionNameToText($string)
    {
        // Firstly we need to make sure that the string is in camelCase,
        // otherwise splitting into words will not work
        $string = camel_case($string);

        // Then convert camelCase into normal text
        $string = static::camelCaseToLabelCase($string);

        // After that we need to make sure that each word starts with lower case
        // character (except for abbreviations)
        $string = static::lcwords($string);

        // Finally, we convert first character of the string into upper case
        return ucfirst($string);
    }

    /**
     * Replaces camelCase string with label case (normal text), preserving abbreviations.
     *
     * @param string $string
     * @return string
     */
    public static function camelCaseToLabelCase($string)
    {
        $re = '/(?#! splitCamelCase Rev:20140412)
            # Split camelCase "words". Two global alternatives. Either g1of2:
              (?<=[a-z])      # Position is after a lowercase,
              (?=[A-Z])       # and before an uppercase letter.
            | (?<=[A-Z])      # Or g2of2; Position is after uppercase,
              (?=[A-Z][a-z])  # and before upper-then-lower case.
            /x';

        return preg_replace($re, ' ', $string);
    }



    /**
     * Works similar to ucwords (replaces first character of each word to lower case).
     *
     * @param string $string
     * @return string
     */
    public static function lcwords($string)
    {
        return preg_replace_callback('/\b([A-Z])(?(?=\s)(\s)|([a-z]))/', function($match) {
            return str_replace($match[1], strtolower($match[1]), $match[0]);
        }, $string);
    }
}