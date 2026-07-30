<?php

namespace Mg\Colaborador;

use Illuminate\Foundation\Http\FormRequest;

class ColaboradorCartaoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // O vinculo (codcolaborador) nao muda na edicao — o cartao continua no
        // mesmo colaborador. `numero` so' vem quando o usuario troca (vazio =
        // mantem o gravado); o front omite a chave nesse caso.
        return [
            'numero'      => 'sometimes|string|min:13|max:19',
            'validademes' => 'sometimes|required|integer|between:1,12',
            'validadeano' => 'sometimes|required|integer|between:0,99',
            'email'       => 'nullable|email|max:255',
            'observacao'  => 'nullable|string',
        ];
    }
}
