<?php

namespace App\Http\Requests\Mg\Safra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SafraStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'safra' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'anoplantio' => [
                'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('tblsafra', 'anoplantio')->where('codcultura', $this->input('codcultura')),
            ],
            'anocolheita' => ['required', 'integer', 'min:2000', 'max:2100'],
            // Safra de onde copiar os talhoes (TASK-201) — nao e coluna da safra.
            'codsafraorigem' => ['nullable', 'exists:tblsafra,codsafra'],
        ];
    }

    public function messages()
    {
        return [
            'anoplantio.unique' => 'Já existe uma safra dessa cultura para esse ano de plantio.',
        ];
    }
}
