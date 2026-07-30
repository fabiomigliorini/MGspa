<?php

namespace Mg\Colaborador;

use Illuminate\Foundation\Http\FormRequest;

class ColaboradorCartaoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorizacao e' do Autorizador no controller (padrao Mg\Rh).
        return true;
    }

    public function rules(): array
    {
        return [
            'codcolaborador' => 'required|integer|exists:tblcolaborador,codcolaborador',
            'numero'         => 'required|string|min:13|max:19',   // so' digitos (front manda unmasked)
            'validademes'    => 'required|integer|between:1,12',
            'validadeano'    => 'required|integer|between:0,99',
            'email'          => 'nullable|email|max:255',
            'observacao'     => 'nullable|string',
        ];
    }
}
