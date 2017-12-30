<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class RulesFormRequest extends FormRequest
{
    public function rules()
    {
        return [
            'year' => 'required|integer|min:2017',
            'weekDay' => [
                'integer',
                'between:1,7'
            ]
        ];
    }
}