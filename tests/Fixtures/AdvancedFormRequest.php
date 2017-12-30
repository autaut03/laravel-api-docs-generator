<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Foundation\Http\FormRequest;

class AdvancedFormRequest extends FormRequest
{
    public function __construct(Container $app)
    {
        parent::__construct();
    }

    public function rules(Factory $validationFactory)
    {
        return [];
    }
}