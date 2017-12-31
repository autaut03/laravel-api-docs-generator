<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

class TestParameterDefaultsController
{
    /**
     * @default query weekDay 2
     * @default path id 789
     */
    public function withDefaults() {}

    /**
     * @default year path 123
     */
    public function invalid() {}
}