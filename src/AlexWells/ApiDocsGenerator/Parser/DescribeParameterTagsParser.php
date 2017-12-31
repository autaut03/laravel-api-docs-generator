<?php

namespace AlexWells\ApiDocsGenerator\Parser;

class DescribeParameterTagsParser extends AbstractParameterTagsParser
{
    protected function getTagName()
    {
        return 'describe';
    }
}