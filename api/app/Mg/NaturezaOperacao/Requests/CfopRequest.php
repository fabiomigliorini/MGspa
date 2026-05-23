<?php

namespace Mg\NaturezaOperacao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CfopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['cfop' => 'required|string|max:500'];
        if ($this->isMethod('POST')) {
            $rules['codcfop'] = 'required|digits:4|unique:tblcfop,codcfop';
        }
        return $rules;
    }
}
