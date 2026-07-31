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
        // mesmo colaborador. O `numero` e' IMUTAVEL: nunca se sobrescreve o
        // numero gravado; cartao errado se resolve inativando e cadastrando
        // outro. Se vier na request, `prohibited` rejeita com 422.
        return [
            'numero'      => 'prohibited',
            'validademes' => 'sometimes|required|integer|between:1,12',
            'validadeano' => 'sometimes|required|integer|between:0,99',
            'email'       => 'nullable|email|max:255',
            'observacao'  => 'nullable|string',
        ];
    }
}
