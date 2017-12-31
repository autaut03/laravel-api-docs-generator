<?php

namespace AlexWells\ApiDocsGenerator\Parser;

class DefaultParameterTagsParser extends AbstractParameterTagsParser
{
    protected function getTagName()
    {
        return 'default';
    }
}