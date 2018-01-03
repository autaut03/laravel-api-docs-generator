<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

class DescribeParameterTagsParser extends AbstractParameterTagsParser
{
    protected function getTagName()
    {
        return 'describe';
    }
}