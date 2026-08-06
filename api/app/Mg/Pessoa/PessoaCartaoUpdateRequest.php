<?php

namespace Mg\Pessoa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PessoaCartaoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // O titular (codpessoa) nao muda na edicao — o cartao continua na mesma
        // pessoa. O `numero` e' IMUTAVEL: nunca se sobrescreve o numero gravado;
        // cartao errado se resolve inativando e cadastrando outro. Se vier na
        // request, `prohibited` rejeita com 422. O `tipo`, ao contrario, pode ser
        // corrigido — nao e' dado sensivel.
        return [
            'numero'      => 'prohibited',
            'tipo'        => ['sometimes', 'required', Rule::in(array_keys(PessoaCartao::TIPO_DESCRICAO))],
            'codfilial'   => 'sometimes|required|integer|exists:tblfilial,codfilial',
            'validademes' => 'sometimes|required|integer|between:1,12',
            'validadeano' => 'sometimes|required|integer|between:0,99',
            'email'       => 'nullable|email|max:255',
            'observacao'  => 'nullable|string',
        ];
    }
}
