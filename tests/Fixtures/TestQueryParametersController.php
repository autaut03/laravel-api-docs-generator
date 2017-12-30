<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class TestQueryParametersController extends Controller
{
    public function simple(RulesFormRequest $request) {}

    public function withContainer(AdvancedFormRequest $request) {}
}