<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class TestPathParametersController extends Controller
{
    public function simpleType(int $id) {}

    public function classType(SimpleClass $class) {}

    public function modelType(SimpleEloquentModel $model) {}

    public function mixedOrder(string $version, int $product) {}

    public function noTypeSpecified($id) {}

    public function withDefaultValue($id = 123) {}
}