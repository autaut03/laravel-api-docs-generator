<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

class TestParameterDescriptionsController
{
    /**
     * @describe query weekDay Week day number
     * @describe path id It's an ID of your model
     */
    public function described() {}

    /**
     * @describe year path A year number
     */
    public function invalidDescribed() {}
}