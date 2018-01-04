<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

class DefaultParameterTagsParser extends AbstractParameterTagsParser
{
    protected function getTagName()
    {
        return 'default';
    }
}
