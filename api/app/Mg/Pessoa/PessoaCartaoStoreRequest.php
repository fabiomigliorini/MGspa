<?php

namespace Mg\Pessoa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PessoaCartaoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorizacao e' do Autorizador no controller (padrao Mg\Rh).
        return true;
    }

    public function rules(): array
    {
        // O titular (codpessoa) vem da rota, nao do payload — quem valida que a
        // pessoa e' colaborador ou filial e' o controller.
        return [
            'tipo'        => ['required', Rule::in(array_keys(PessoaCartao::TIPO_DESCRICAO))],
            'numero'      => 'required|string|min:13|max:19',   // so' digitos (front manda unmasked)
            'validademes' => 'required|integer|between:1,12',
            'validadeano' => 'required|integer|between:0,99',
            'email'       => 'nullable|email|max:255',
            'observacao'  => 'nullable|string',
        ];
    }
}
